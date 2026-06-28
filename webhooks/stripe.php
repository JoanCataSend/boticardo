<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/orders.php';
require_once __DIR__ . '/../includes/stripe_checkout.php';
require_once __DIR__ . '/../includes/db.php';

$payload = file_get_contents('php://input') ?: '';
$signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (!stripeWebhookIsConfigured()) {
    error_log('Boticardo - STRIPE_WEBHOOK_SECRET no está configurado.');
    http_response_code(500);
    exit('Webhook no configurado.');
}

if (!stripeVerifyWebhookSignature($payload, $signature, STRIPE_WEBHOOK_SECRET)) {
    error_log('Boticardo - Firma de webhook Stripe inválida.');
    http_response_code(400);
    exit('Firma inválida.');
}

$event = json_decode($payload, true);
if (!is_array($event) || empty($event['id']) || empty($event['type'])) {
    http_response_code(400);
    exit('Evento inválido.');
}

$eventId = (string) $event['id'];
$eventType = (string) $event['type'];
$session = $event['data']['object'] ?? [];

try {
    $isNewEvent = orderRecordStripeEvent($conn, $eventId, $eventType);

    if (!$isNewEvent) {
        http_response_code(200);
        exit('Evento ya procesado.');
    }

    if (in_array($eventType, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
        if (is_array($session)) {
            orderMarkPaidFromStripeSession($conn, $session, $eventId);
        }
    }

    http_response_code(200);
    echo 'ok';
} catch (Throwable $error) {
    error_log('Boticardo - Error procesando webhook Stripe: ' . $error->getMessage());
    http_response_code(500);
    echo 'Error interno.';
}
