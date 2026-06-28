<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart.php';
require_once __DIR__ . '/../includes/orders.php';
require_once __DIR__ . '/../includes/stripe_checkout.php';
require_once __DIR__ . '/../includes/db.php';

function checkoutRedirectError(string $message): never
{
    header('Location: ../checkout.php?error=' . rawurlencode($message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

$currentUser = authCurrentUser();
if (!$currentUser) {
    header('Location: ../login.php?redirect=checkout.php');
    exit;
}

if (!authValidateCsrf((string) ($_POST['csrf_token'] ?? ''))) {
    checkoutRedirectError('La sesión ha caducado. Recarga la página y vuelve a intentarlo.');
}

if (!stripeIsConfigured()) {
    checkoutRedirectError('Stripe todavía no está configurado. Falta STRIPE_SECRET_KEY.');
}

if (empty($_POST['accept_terms'])) {
    checkoutRedirectError('Debes confirmar los datos del pedido antes de pagar.');
}

[$shipping, $shippingErrors] = orderValidateShipping($_POST);
if ($shippingErrors !== []) {
    checkoutRedirectError($shippingErrors[0]);
}

$cartSummary = cartSummary($conn);
if (($cartSummary['items'] ?? []) === []) {
    header('Location: ../carrito.php');
    exit;
}

$order = null;

try {
    $order = orderCreatePendingFromCart($conn, (int) $currentUser['id'], $shipping, $cartSummary);
    $stripeSession = stripeCreateCheckoutSession($order, $shipping);

    $sessionId = (string) ($stripeSession['id'] ?? '');
    $sessionUrl = (string) ($stripeSession['url'] ?? '');

    if ($sessionId === '' || $sessionUrl === '') {
        throw new RuntimeException('Stripe no ha devuelto una URL de pago válida.');
    }

    orderUpdateStripeSession($conn, (int) $order['id'], $sessionId);

    header('Location: ' . $sessionUrl, true, 303);
    exit;
} catch (Throwable $error) {
    error_log('Boticardo - Error creando sesión de pago Stripe: ' . $error->getMessage());

    if (is_array($order) && !empty($order['id'])) {
        orderMarkFailed($conn, (int) $order['id']);
    }

    checkoutRedirectError('No se ha podido iniciar el pago. Revisa los datos o inténtalo de nuevo.');
}
