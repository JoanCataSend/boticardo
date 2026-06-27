<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

authEnsureUsuariosTable($conn);

$provider = strtolower((string) ($_GET['provider'] ?? $_POST['provider'] ?? ''));
$state = (string) ($_GET['state'] ?? $_POST['state'] ?? '');
$code = (string) ($_GET['code'] ?? $_POST['code'] ?? '');
$error = (string) ($_GET['error'] ?? $_POST['error'] ?? '');

try {
    $redirect = authValidateSocialState($provider, $state);

    if ($error !== '') {
        throw new RuntimeException('El proveedor canceló el acceso: ' . $error);
    }

    if ($code === '') {
        throw new RuntimeException('No se recibió código de autenticación.');
    }

    if ($provider === 'google') {
        authLoginWithGoogle($conn, $code);
    } elseif ($provider === 'apple') {
        authLoginWithApple($conn, $code, isset($_POST['user']) ? (string) $_POST['user'] : null);
    } else {
        throw new RuntimeException('Proveedor no válido.');
    }

    $conn->close();
    header('Location: ' . $redirect);
    exit;
} catch (Throwable $error) {
    error_log('Boticardo - Error en callback social: ' . $error->getMessage());
    $conn->close();
    header('Location: login.php?error=social');
    exit;
}
