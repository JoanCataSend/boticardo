<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/auth.php';

$provider = strtolower((string) ($_GET['provider'] ?? ''));
$redirect = authSafeRedirect($_GET['redirect'] ?? 'index.php');

try {
    $url = authStartSocialLogin($provider, $redirect);
    header('Location: ' . $url);
    exit;
} catch (Throwable $error) {
    error_log('Boticardo - Error iniciando login social: ' . $error->getMessage());
    header('Location: login.php?error=social_config&redirect=' . rawurlencode($redirect));
    exit;
}
