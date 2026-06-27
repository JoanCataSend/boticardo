<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/auth.php';

authLogout();

$redirect = authSafeRedirect($_GET['redirect'] ?? 'index.php');
header('Location: ' . $redirect);
exit;
