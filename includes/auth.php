<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';
boticardoStartSession();

const BOTICARDO_USER_SESSION_KEY = 'boticardo_user';
const BOTICARDO_AUTH_STATE_KEY = 'boticardo_auth_state';

function authEnsureUsuariosTable(mysqli $conn): void
{
    $conn->query("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL,
            rol ENUM('cliente','admin') NOT NULL DEFAULT 'cliente',
            password_hash VARCHAR(255) NULL,
            google_sub VARCHAR(255) NULL,
            apple_sub VARCHAR(255) NULL,
            email_verificado TINYINT(1) NOT NULL DEFAULT 0,
            email_verification_code_hash VARCHAR(255) NULL,
            email_verification_expires_at DATETIME NULL,
            email_verified_at DATETIME NULL,
            ultimo_login DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_usuarios_email (email),
            UNIQUE KEY uq_usuarios_google_sub (google_sub),
            UNIQUE KEY uq_usuarios_apple_sub (apple_sub)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    authEnsureUsuariosColumns($conn);
}

function authUsuarioColumnExists(mysqli $conn, string $column): bool
{
    $stmt = $conn->prepare('
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = "usuarios"
          AND COLUMN_NAME = ?
    ');
    $stmt->bind_param('s', $column);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $result->free();
    $stmt->close();

    return ((int) ($row['total'] ?? 0)) > 0;
}

function authEnsureUsuariosColumns(mysqli $conn): void
{
    if (!authUsuarioColumnExists($conn, 'rol')) {
        $conn->query("ALTER TABLE usuarios ADD COLUMN rol ENUM('cliente','admin') NOT NULL DEFAULT 'cliente' AFTER email");
    }

    if (!authUsuarioColumnExists($conn, 'email_verificado')) {
        // Los usuarios antiguos se marcan como verificados para no bloquear cuentas ya existentes.
        $conn->query("ALTER TABLE usuarios ADD COLUMN email_verificado TINYINT(1) NOT NULL DEFAULT 1 AFTER apple_sub");
    }

    if (!authUsuarioColumnExists($conn, 'email_verification_code_hash')) {
        $conn->query("ALTER TABLE usuarios ADD COLUMN email_verification_code_hash VARCHAR(255) NULL AFTER email_verificado");
    }

    if (!authUsuarioColumnExists($conn, 'email_verification_expires_at')) {
        $conn->query("ALTER TABLE usuarios ADD COLUMN email_verification_expires_at DATETIME NULL AFTER email_verification_code_hash");
    }

    if (!authUsuarioColumnExists($conn, 'email_verified_at')) {
        $conn->query("ALTER TABLE usuarios ADD COLUMN email_verified_at DATETIME NULL AFTER email_verification_expires_at");
    }
}

function authCsrfToken(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function authValidateCsrf(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function authSafeRedirect(?string $target, string $fallback = 'index.php'): string
{
    $target = trim((string) $target);

    if ($target === '') {
        return $fallback;
    }

    if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $target) || str_starts_with($target, '//')) {
        return $fallback;
    }

    if (str_contains($target, "\n") || str_contains($target, "\r")) {
        return $fallback;
    }

    return ltrim($target, '/');
}

function authCurrentUser(): ?array
{
    if (!isset($_SESSION[BOTICARDO_USER_SESSION_KEY]) || !is_array($_SESSION[BOTICARDO_USER_SESSION_KEY])) {
        return null;
    }

    $user = $_SESSION[BOTICARDO_USER_SESSION_KEY];

    if (empty($user['id']) || empty($user['email'])) {
        return null;
    }

    return $user;
}

function authIsLoggedIn(): bool
{
    return authCurrentUser() !== null;
}

function authIsAdmin(): bool
{
    $user = authCurrentUser();
    return $user !== null && ($user['rol'] ?? 'cliente') === 'admin';
}

function authRequireLogin(string $redirect = 'login.php'): void
{
    if (authIsLoggedIn()) {
        return;
    }

    $current = $_SERVER['REQUEST_URI'] ?? 'index.php';
    header('Location: ' . $redirect . '?redirect=' . rawurlencode(ltrim($current, '/')));
    exit;
}

function authRequireAdmin(string $loginPath = 'login.php'): void
{
    if (!authIsLoggedIn()) {
        $current = $_SERVER['REQUEST_URI'] ?? 'admin/index.php';
        header('Location: ' . $loginPath . '?redirect=' . rawurlencode(ltrim($current, '/')));
        exit;
    }

    if (!authIsAdmin()) {
        http_response_code(403);
        echo 'Acceso denegado. Esta zona es solo para administradores.';
        exit;
    }
}

if (!function_exists('isUserLoggedIn')) {
    function isUserLoggedIn(): bool
    {
        return authIsLoggedIn();
    }
}

function authSetSession(array $user): void
{
    boticardoRegenerateSession();

    $userId = (int) $user['id'];
    unset($_SESSION['boticardo_account_synced_' . $userId]);

    $_SESSION[BOTICARDO_USER_SESSION_KEY] = [
        'id' => $userId,
        'nombre' => (string) ($user['nombre'] ?? ''),
        'email' => (string) ($user['email'] ?? ''),
        'rol' => (string) ($user['rol'] ?? 'cliente'),
        'email_verificado' => (int) ($user['email_verificado'] ?? 1),
    ];

    // Compatibilidad con comprobaciones antiguas del carrito.
    $_SESSION['usuario_id'] = $userId;
    $_SESSION['user_id'] = $userId;
    $_SESSION['logged_in'] = true;
}

function authLogout(): void
{
    foreach (array_keys($_SESSION) as $key) {
        if (is_string($key) && str_starts_with($key, 'boticardo_account_synced_')) {
            unset($_SESSION[$key]);
        }
    }

    unset(
        $_SESSION[BOTICARDO_USER_SESSION_KEY],
        $_SESSION['usuario_id'],
        $_SESSION['user_id'],
        $_SESSION['cliente_id'],
        $_SESSION['logged_in']
    );

    // Evita reutilizar el mismo ID de sesión después de cerrar sesión.
    boticardoRegenerateSession();
}

function authFindUserByEmail(mysqli $conn, string $email): ?array
{
    $stmt = $conn->prepare('SELECT * FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc() ?: null;
    $result->free();
    $stmt->close();

    return $user;
}

function authUserEmailVerified(array $user): bool
{
    return !array_key_exists('email_verificado', $user) || (int) $user['email_verificado'] === 1;
}

function authGenerateEmailVerificationCode(): string
{
    return (string) random_int(100000, 999999);
}

function authSaveEmailVerificationCode(mysqli $conn, int $userId, string $code): void
{
    $codeHash = password_hash($code, PASSWORD_DEFAULT);

    $stmt = $conn->prepare('
        UPDATE usuarios
        SET email_verificado = 0,
            email_verification_code_hash = ?,
            email_verification_expires_at = DATE_ADD(NOW(), INTERVAL 30 MINUTE),
            email_verified_at = NULL
        WHERE id = ?
    ');
    $stmt->bind_param('si', $codeHash, $userId);
    $stmt->execute();
    $stmt->close();
}

function authSendEmailVerificationCode(array $user, string $code): bool
{
    require_once __DIR__ . '/mailer.php';

    return mailerSendEmailVerificationCode($user, $code);
}

function authCreateAndSendEmailVerificationCode(mysqli $conn, array $user): bool
{
    $code = authGenerateEmailVerificationCode();
    authSaveEmailVerificationCode($conn, (int) $user['id'], $code);

    return authSendEmailVerificationCode($user, $code);
}

function authResendEmailVerificationCode(mysqli $conn, string $email): array
{
    $email = strtolower(trim($email));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'message' => 'Escribe un email válido para reenviar el código.'];
    }

    $user = authFindUserByEmail($conn, $email);

    if (!$user) {
        return ['ok' => false, 'message' => 'No existe ninguna cuenta pendiente con ese email.'];
    }

    if (authUserEmailVerified($user)) {
        return ['ok' => false, 'message' => 'Este email ya está verificado. Puedes iniciar sesión.'];
    }

    $mailSent = authCreateAndSendEmailVerificationCode($conn, $user);

    if (!$mailSent) {
        return ['ok' => false, 'message' => 'No se pudo enviar el correo. Revisa la configuración de email del servidor.'];
    }

    return ['ok' => true, 'message' => 'Te hemos enviado un nuevo código de verificación.'];
}

function authVerifyEmailCode(mysqli $conn, string $email, string $code): array
{
    $email = strtolower(trim($email));
    $code = trim($code);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'message' => 'Escribe el email con el que te registraste.'];
    }

    if (!preg_match('/^\d{6}$/', $code)) {
        return ['ok' => false, 'message' => 'El código debe tener 6 números.'];
    }

    $user = authFindUserByEmail($conn, $email);

    if (!$user) {
        return ['ok' => false, 'message' => 'No existe ninguna cuenta con ese email.'];
    }

    if (authUserEmailVerified($user)) {
        authSetSession($user);
        return ['ok' => true, 'message' => 'Tu correo ya estaba verificado.'];
    }

    $savedHash = (string) ($user['email_verification_code_hash'] ?? '');
    $expiresAt = (string) ($user['email_verification_expires_at'] ?? '');

    if ($savedHash === '') {
        return ['ok' => false, 'message' => 'No hay ningún código activo. Solicita uno nuevo.'];
    }

    if ($expiresAt === '' || strtotime($expiresAt) < time()) {
        return ['ok' => false, 'message' => 'El código ha caducado. Solicita uno nuevo.'];
    }

    if (!password_verify($code, $savedHash)) {
        return ['ok' => false, 'message' => 'El código no es correcto.'];
    }

    $userId = (int) $user['id'];
    $stmt = $conn->prepare('
        UPDATE usuarios
        SET email_verificado = 1,
            email_verification_code_hash = NULL,
            email_verification_expires_at = NULL,
            email_verified_at = NOW(),
            ultimo_login = NOW()
        WHERE id = ?
    ');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();

    $verifiedUser = authFindUserByEmail($conn, $email);
    if ($verifiedUser) {
        authSetSession($verifiedUser);
    }

    return ['ok' => true, 'message' => 'Correo verificado correctamente.'];
}

function authLoginWithPassword(mysqli $conn, string $email, string $password): array
{
    $email = strtolower(trim($email));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        return ['ok' => false, 'message' => 'Revisa el email y la contraseña.'];
    }

    $user = authFindUserByEmail($conn, $email);

    if (!$user || empty($user['password_hash']) || !password_verify($password, (string) $user['password_hash'])) {
        return ['ok' => false, 'message' => 'Email o contraseña incorrectos.'];
    }

    if (!authUserEmailVerified($user)) {
        return [
            'ok' => false,
            'needs_verification' => true,
            'email' => $email,
            'message' => 'Antes de iniciar sesión tienes que verificar tu correo.',
        ];
    }

    $stmt = $conn->prepare('UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?');
    $userId = (int) $user['id'];
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();

    authSetSession($user);

    return ['ok' => true, 'message' => 'Sesión iniciada correctamente.'];
}

function authRegisterWithPassword(mysqli $conn, string $nombre, string $email, string $password, string $passwordConfirm): array
{
    $nombre = trim($nombre);
    $email = strtolower(trim($email));

    if (strlen($nombre) < 2) {
        return ['ok' => false, 'message' => 'Escribe tu nombre.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'message' => 'Escribe un email válido.'];
    }

    if (strlen($password) < 8) {
        return ['ok' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres.'];
    }

    if ($password !== $passwordConfirm) {
        return ['ok' => false, 'message' => 'Las contraseñas no coinciden.'];
    }

    if (authFindUserByEmail($conn, $email)) {
        return ['ok' => false, 'message' => 'Ya existe una cuenta con ese email. Inicia sesión.'];
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare('
        INSERT INTO usuarios (nombre, email, password_hash, email_verificado, ultimo_login)
        VALUES (?, ?, ?, 0, NULL)
    ');
    $stmt->bind_param('sss', $nombre, $email, $passwordHash);
    $stmt->execute();
    $userId = (int) $stmt->insert_id;
    $stmt->close();

    $user = [
        'id' => $userId,
        'nombre' => $nombre,
        'email' => $email,
        'rol' => 'cliente',
        'email_verificado' => 0,
    ];

    $mailSent = authCreateAndSendEmailVerificationCode($conn, $user);

    return [
        'ok' => true,
        'requires_verification' => true,
        'email' => $email,
        'mail_sent' => $mailSent,
        'message' => $mailSent
            ? 'Cuenta creada. Te hemos enviado un código de verificación por email.'
            : 'Cuenta creada, pero no se pudo enviar el email de verificación. Revisa la configuración de correo.',
    ];
}

function authIsGoogleConfigured(): bool
{
    return defined('GOOGLE_CLIENT_ID')
        && defined('GOOGLE_CLIENT_SECRET')
        && GOOGLE_CLIENT_ID !== ''
        && GOOGLE_CLIENT_SECRET !== '';
}

function authIsAppleConfigured(): bool
{
    return defined('APPLE_CLIENT_ID')
        && defined('APPLE_TEAM_ID')
        && defined('APPLE_KEY_ID')
        && defined('APPLE_PRIVATE_KEY')
        && APPLE_CLIENT_ID !== ''
        && APPLE_TEAM_ID !== ''
        && APPLE_KEY_ID !== ''
        && APPLE_PRIVATE_KEY !== '';
}

function authProviderConfigured(string $provider): bool
{
    return match ($provider) {
        'google' => authIsGoogleConfigured(),
        'apple' => authIsAppleConfigured(),
        default => false,
    };
}

function authProviderName(string $provider): string
{
    return match ($provider) {
        'google' => 'Google',
        'apple' => 'Apple',
        default => 'Proveedor',
    };
}

function authRedirectUri(string $provider): string
{
    $baseUrl = defined('APP_BASE_URL') ? rtrim(APP_BASE_URL, '/') : '';

    return match ($provider) {
        'google' => defined('GOOGLE_REDIRECT_URI') && GOOGLE_REDIRECT_URI !== '' ? GOOGLE_REDIRECT_URI : $baseUrl . '/auth_callback.php?provider=google',
        'apple' => defined('APPLE_REDIRECT_URI') && APPLE_REDIRECT_URI !== '' ? APPLE_REDIRECT_URI : $baseUrl . '/auth_callback.php?provider=apple',
        default => $baseUrl . '/auth_callback.php',
    };
}

function authStartSocialLogin(string $provider, string $redirect = 'index.php'): string
{
    $provider = strtolower($provider);

    if (!authProviderConfigured($provider)) {
        throw new RuntimeException('Falta configurar ' . authProviderName($provider) . '.');
    }

    $state = bin2hex(random_bytes(24));
    $_SESSION[BOTICARDO_AUTH_STATE_KEY] = [
        'state' => $state,
        'provider' => $provider,
        'redirect' => authSafeRedirect($redirect),
        'created_at' => time(),
    ];

    if ($provider === 'google') {
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'response_type' => 'code',
            'client_id' => GOOGLE_CLIENT_ID,
            'redirect_uri' => authRedirectUri('google'),
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ]);
    }

    if ($provider === 'apple') {
        return 'https://appleid.apple.com/auth/authorize?' . http_build_query([
            'response_type' => 'code id_token',
            'response_mode' => 'form_post',
            'client_id' => APPLE_CLIENT_ID,
            'redirect_uri' => authRedirectUri('apple'),
            'scope' => 'name email',
            'state' => $state,
        ]);
    }

    throw new RuntimeException('Proveedor no válido.');
}

function authValidateSocialState(string $provider, ?string $state): string
{
    $saved = $_SESSION[BOTICARDO_AUTH_STATE_KEY] ?? null;
    unset($_SESSION[BOTICARDO_AUTH_STATE_KEY]);

    if (!is_array($saved) || empty($saved['state']) || empty($saved['provider'])) {
        throw new RuntimeException('La sesión de autenticación ha caducado.');
    }

    if (($saved['provider'] ?? '') !== $provider || !is_string($state) || !hash_equals((string) $saved['state'], $state)) {
        throw new RuntimeException('No se pudo validar la autenticación.');
    }

    if ((int) ($saved['created_at'] ?? 0) < time() - 900) {
        throw new RuntimeException('La autenticación ha caducado. Vuelve a intentarlo.');
    }

    return authSafeRedirect((string) ($saved['redirect'] ?? 'index.php'));
}

function authHttpPost(string $url, array $data): array
{
    $body = http_build_query($data);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $status >= 400) {
            throw new RuntimeException('Error de comunicación con el proveedor. ' . $error);
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $body,
                'timeout' => 15,
            ],
        ]);
        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            throw new RuntimeException('Error de comunicación con el proveedor.');
        }
    }

    $json = json_decode((string) $response, true);

    if (!is_array($json)) {
        throw new RuntimeException('Respuesta no válida del proveedor.');
    }

    return $json;
}

function authHttpGetJson(string $url): array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $status >= 400) {
            throw new RuntimeException('Error de validación con el proveedor. ' . $error);
        }
    } else {
        $response = file_get_contents($url);

        if ($response === false) {
            throw new RuntimeException('Error de validación con el proveedor.');
        }
    }

    $json = json_decode((string) $response, true);

    if (!is_array($json)) {
        throw new RuntimeException('Respuesta no válida del proveedor.');
    }

    return $json;
}

function authDecodeJwtPayload(string $jwt): array
{
    $parts = explode('.', $jwt);

    if (count($parts) < 2) {
        throw new RuntimeException('Token no válido.');
    }

    $payload = strtr($parts[1], '-_', '+/');
    $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
    $decoded = base64_decode($payload, true);

    if ($decoded === false) {
        throw new RuntimeException('Token no válido.');
    }

    $claims = json_decode($decoded, true);

    if (!is_array($claims)) {
        throw new RuntimeException('Token no válido.');
    }

    return $claims;
}

function authLoginWithGoogle(mysqli $conn, string $code): array
{
    $token = authHttpPost('https://oauth2.googleapis.com/token', [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => authRedirectUri('google'),
        'grant_type' => 'authorization_code',
    ]);

    if (empty($token['id_token'])) {
        throw new RuntimeException('Google no devolvió un token válido.');
    }

    $claims = authHttpGetJson('https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode((string) $token['id_token']));

    if (($claims['aud'] ?? '') !== GOOGLE_CLIENT_ID) {
        throw new RuntimeException('El token de Google no pertenece a esta aplicación.');
    }

    if (empty($claims['email']) || (($claims['email_verified'] ?? 'false') !== 'true' && ($claims['email_verified'] ?? false) !== true)) {
        throw new RuntimeException('Google no pudo verificar el email.');
    }

    return authLoginOrCreateSocialUser(
        $conn,
        'google',
        (string) $claims['sub'],
        (string) $claims['email'],
        (string) ($claims['name'] ?? explode('@', (string) $claims['email'])[0])
    );
}

function authBase64UrlEncode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function authDerToJoseSignature(string $derSignature, int $partLength = 32): string
{
    $offset = 0;

    if (ord($derSignature[$offset++]) !== 0x30) {
        throw new RuntimeException('Firma Apple no válida.');
    }

    $sequenceLength = ord($derSignature[$offset++]);
    if ($sequenceLength > 0x80) {
        $bytes = $sequenceLength - 0x80;
        $sequenceLength = 0;
        for ($i = 0; $i < $bytes; $i++) {
            $sequenceLength = ($sequenceLength << 8) + ord($derSignature[$offset++]);
        }
    }

    if (ord($derSignature[$offset++]) !== 0x02) {
        throw new RuntimeException('Firma Apple no válida.');
    }

    $rLength = ord($derSignature[$offset++]);
    $r = substr($derSignature, $offset, $rLength);
    $offset += $rLength;

    if (ord($derSignature[$offset++]) !== 0x02) {
        throw new RuntimeException('Firma Apple no válida.');
    }

    $sLength = ord($derSignature[$offset++]);
    $s = substr($derSignature, $offset, $sLength);

    $r = str_pad(ltrim($r, "\x00"), $partLength, "\x00", STR_PAD_LEFT);
    $s = str_pad(ltrim($s, "\x00"), $partLength, "\x00", STR_PAD_LEFT);

    return $r . $s;
}

function authAppleClientSecret(): string
{
    $privateKey = str_replace('\\n', "\n", APPLE_PRIVATE_KEY);

    $header = [
        'alg' => 'ES256',
        'kid' => APPLE_KEY_ID,
    ];

    $payload = [
        'iss' => APPLE_TEAM_ID,
        'iat' => time(),
        'exp' => time() + 86400 * 30,
        'aud' => 'https://appleid.apple.com',
        'sub' => APPLE_CLIENT_ID,
    ];

    $unsignedToken = authBase64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES))
        . '.' . authBase64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));

    $key = openssl_pkey_get_private($privateKey);

    if (!$key) {
        throw new RuntimeException('La clave privada de Apple no es válida.');
    }

    $signature = '';
    $ok = openssl_sign($unsignedToken, $signature, $key, OPENSSL_ALGO_SHA256);

    if (!$ok) {
        throw new RuntimeException('No se pudo firmar el token de Apple.');
    }

    return $unsignedToken . '.' . authBase64UrlEncode(authDerToJoseSignature($signature));
}

function authLoginWithApple(mysqli $conn, string $code, ?string $postedUserJson = null): array
{
    $token = authHttpPost('https://appleid.apple.com/auth/token', [
        'client_id' => APPLE_CLIENT_ID,
        'client_secret' => authAppleClientSecret(),
        'code' => $code,
        'grant_type' => 'authorization_code',
        'redirect_uri' => authRedirectUri('apple'),
    ]);

    if (empty($token['id_token'])) {
        throw new RuntimeException('Apple no devolvió un token válido.');
    }

    $claims = authDecodeJwtPayload((string) $token['id_token']);

    if (($claims['iss'] ?? '') !== 'https://appleid.apple.com' || ($claims['aud'] ?? '') !== APPLE_CLIENT_ID || (int) ($claims['exp'] ?? 0) < time()) {
        throw new RuntimeException('El token de Apple no es válido para esta aplicación.');
    }

    $email = (string) ($claims['email'] ?? '');
    $name = $email !== '' ? explode('@', $email)[0] : 'Cliente Apple';

    if ($postedUserJson) {
        $postedUser = json_decode($postedUserJson, true);
        if (is_array($postedUser)) {
            $firstName = trim((string) ($postedUser['name']['firstName'] ?? ''));
            $lastName = trim((string) ($postedUser['name']['lastName'] ?? ''));
            $fullName = trim($firstName . ' ' . $lastName);
            if ($fullName !== '') {
                $name = $fullName;
            }
        }
    }

    if ($email === '') {
        throw new RuntimeException('Apple no devolvió email. Prueba con otro método de acceso.');
    }

    return authLoginOrCreateSocialUser($conn, 'apple', (string) $claims['sub'], $email, $name);
}

function authLoginOrCreateSocialUser(mysqli $conn, string $provider, string $providerSub, string $email, string $name): array
{
    $provider = strtolower($provider);
    $email = strtolower(trim($email));
    $name = trim($name) !== '' ? trim($name) : explode('@', $email)[0];
    $column = $provider === 'google' ? 'google_sub' : 'apple_sub';

    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE $column = ? LIMIT 1");
    $stmt->bind_param('s', $providerSub);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc() ?: null;
    $result->free();
    $stmt->close();

    if (!$user) {
        $user = authFindUserByEmail($conn, $email);

        if ($user) {
            $stmt = $conn->prepare("
                UPDATE usuarios
                SET $column = ?,
                    email_verificado = 1,
                    email_verification_code_hash = NULL,
                    email_verification_expires_at = NULL,
                    email_verified_at = COALESCE(email_verified_at, NOW()),
                    ultimo_login = NOW()
                WHERE id = ?
            ");
            $userId = (int) $user['id'];
            $stmt->bind_param('si', $providerSub, $userId);
            $stmt->execute();
            $stmt->close();
            $user[$column] = $providerSub;
            $user['email_verificado'] = 1;
        } else {
            $stmt = $conn->prepare("
                INSERT INTO usuarios (nombre, email, $column, email_verificado, email_verified_at, ultimo_login)
                VALUES (?, ?, ?, 1, NOW(), NOW())
            ");
            $stmt->bind_param('sss', $name, $email, $providerSub);
            $stmt->execute();
            $userId = (int) $stmt->insert_id;
            $stmt->close();

            $user = [
                'id' => $userId,
                'nombre' => $name,
                'email' => $email,
                'rol' => 'cliente',
                'email_verificado' => 1,
                $column => $providerSub,
            ];
        }
    } else {
        $stmt = $conn->prepare('
            UPDATE usuarios
            SET email_verificado = 1,
                email_verification_code_hash = NULL,
                email_verification_expires_at = NULL,
                email_verified_at = COALESCE(email_verified_at, NOW()),
                ultimo_login = NOW()
            WHERE id = ?
        ');
        $userId = (int) $user['id'];
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
        $user['email_verificado'] = 1;
    }

    authSetSession($user);

    return ['ok' => true, 'message' => 'Sesión iniciada con ' . authProviderName($provider) . '.'];
}
