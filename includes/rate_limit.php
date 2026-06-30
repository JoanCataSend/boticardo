<?php
declare(strict_types=1);

/**
 * Rate limit sencillo por archivos para frenar fuerza bruta y spam.
 * No requiere tocar la base de datos.
 */
function rateLimitStorageDir(): string
{
    $primary = __DIR__ . '/../storage/rate_limit';

    if (!is_dir($primary)) {
        @mkdir($primary, 0755, true);
    }

    if (is_dir($primary) && is_writable($primary)) {
        return $primary;
    }

    $fallback = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'boticardo_rate_limit';
    if (!is_dir($fallback)) {
        @mkdir($fallback, 0755, true);
    }

    return $fallback;
}

function rateLimitClientIp(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $ip = trim($ip);

    if ($ip === '' || strlen($ip) > 64) {
        return 'unknown';
    }

    return $ip;
}

function rateLimitIdentifier(?string $identifier, string $fallback = 'anonymous'): string
{
    $identifier = strtolower(trim((string) $identifier));

    return $identifier !== '' ? $identifier : $fallback;
}

function rateLimitFilePath(string $scope, string $identifier): string
{
    $safeScope = preg_replace('/[^a-z0-9_-]/i', '-', $scope) ?: 'scope';
    $hash = hash('sha256', $safeScope . '|' . $identifier);

    return rateLimitStorageDir() . DIRECTORY_SEPARATOR . $safeScope . '_' . $hash . '.json';
}

function rateLimitConsume(string $scope, string $identifier, int $maxAttempts, int $windowSeconds): array
{
    $maxAttempts = max(1, $maxAttempts);
    $windowSeconds = max(60, $windowSeconds);
    $identifier = rateLimitIdentifier($identifier);
    $filePath = rateLimitFilePath($scope, $identifier);
    $now = time();

    $handle = @fopen($filePath, 'c+');
    if ($handle === false) {
        // Si el servidor no permite escribir, no bloqueamos la web.
        error_log('Boticardo rate limit: no se pudo abrir ' . $filePath);
        return [
            'ok' => true,
            'remaining' => $maxAttempts,
            'retry_after' => 0,
        ];
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            return [
                'ok' => true,
                'remaining' => $maxAttempts,
                'retry_after' => 0,
            ];
        }

        rewind($handle);
        $raw = stream_get_contents($handle);
        $data = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
        $attempts = is_array($data['attempts'] ?? null) ? $data['attempts'] : [];

        $attempts = array_values(array_filter($attempts, static function ($timestamp) use ($now, $windowSeconds): bool {
            $timestamp = (int) $timestamp;
            return $timestamp > 0 && $timestamp >= ($now - $windowSeconds);
        }));

        if (count($attempts) >= $maxAttempts) {
            $oldestAttempt = min($attempts);
            $retryAfter = max(1, $windowSeconds - ($now - $oldestAttempt));

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode(['attempts' => $attempts], JSON_THROW_ON_ERROR));
            fflush($handle);
            flock($handle, LOCK_UN);

            return [
                'ok' => false,
                'remaining' => 0,
                'retry_after' => $retryAfter,
            ];
        }

        $attempts[] = $now;
        $remaining = max(0, $maxAttempts - count($attempts));

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode(['attempts' => $attempts], JSON_THROW_ON_ERROR));
        fflush($handle);
        flock($handle, LOCK_UN);

        return [
            'ok' => true,
            'remaining' => $remaining,
            'retry_after' => 0,
        ];
    } catch (Throwable $error) {
        error_log('Boticardo rate limit: ' . $error->getMessage());
        @flock($handle, LOCK_UN);

        return [
            'ok' => true,
            'remaining' => $maxAttempts,
            'retry_after' => 0,
        ];
    } finally {
        fclose($handle);
    }
}

function rateLimitReset(string $scope, string $identifier): void
{
    $filePath = rateLimitFilePath($scope, rateLimitIdentifier($identifier));

    if (is_file($filePath)) {
        @unlink($filePath);
    }
}

function rateLimitHumanTime(int $seconds): string
{
    $seconds = max(1, $seconds);

    if ($seconds < 60) {
        return 'unos segundos';
    }

    if ($seconds < 3600) {
        $minutes = (int) ceil($seconds / 60);
        return $minutes === 1 ? '1 minuto' : $minutes . ' minutos';
    }

    $hours = (int) ceil($seconds / 3600);
    return $hours === 1 ? '1 hora' : $hours . ' horas';
}

function rateLimitMessage(string $action, int $retryAfter): string
{
    return 'Has realizado demasiados intentos de ' . $action . '. Vuelve a intentarlo en ' . rateLimitHumanTime($retryAfter) . '.';
}
