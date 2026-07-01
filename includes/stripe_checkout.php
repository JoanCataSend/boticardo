<?php
declare(strict_types=1);

function stripeIsConfigured(): bool
{
    return defined('STRIPE_SECRET_KEY') && STRIPE_SECRET_KEY !== '';
}

function stripeWebhookIsConfigured(): bool
{
    return defined('STRIPE_WEBHOOK_SECRET') && STRIPE_WEBHOOK_SECRET !== '';
}

function stripeBuildCheckoutPayload(array $order, array $shipping): array
{
    $lineItems = [];

    foreach ($order['items'] as $item) {
        $name = (string) ($item['nombre'] ?? 'Producto Boticardo');
        $brand = trim((string) ($item['marca'] ?? ''));
        $description = $brand !== '' ? 'Marca: ' . $brand : 'Producto Boticardo';
        $unitAmount = paymentMoneyToCents((float) $item['precio']);
        $quantity = max(1, (int) $item['quantity']);

        $lineItems[] = [
            'price_data' => [
                'currency' => STRIPE_CURRENCY,
                'product_data' => [
                    'name' => mb_substr($name, 0, 190, 'UTF-8'),
                    'description' => mb_substr($description, 0, 250, 'UTF-8'),
                ],
                'unit_amount' => $unitAmount,
            ],
            'quantity' => $quantity,
        ];
    }

    $shippingAmount = paymentMoneyToCents((float) ($order['envio'] ?? 0));
    if ($shippingAmount > 0) {
        $lineItems[] = [
            'price_data' => [
                'currency' => STRIPE_CURRENCY,
                'product_data' => [
                    'name' => 'Gastos de envío',
                    'description' => 'Envío a domicilio',
                ],
                'unit_amount' => $shippingAmount,
            ],
            'quantity' => 1,
        ];
    }

    $successUrl = APP_BASE_URL . '/pedido-confirmado.php?pedido=' . rawurlencode((string) $order['public_id']) . '&session_id={CHECKOUT_SESSION_ID}';
    $cancelUrl = APP_BASE_URL . '/pedido-cancelado.php?pedido=' . rawurlencode((string) $order['public_id']);

    return [
        'mode' => 'payment',
        'locale' => 'es',
        'payment_method_types' => defined('STRIPE_PAYMENT_METHODS') ? STRIPE_PAYMENT_METHODS : ['card', 'bizum'],
        'line_items' => $lineItems,
        'client_reference_id' => (string) $order['public_id'],
        'customer_email' => (string) $shipping['email_envio'],
        'success_url' => $successUrl,
        'cancel_url' => $cancelUrl,
        'metadata' => [
            'pedido_id' => (string) $order['id'],
            'public_id' => (string) $order['public_id'],
            'metodo_entrega' => (string) ($order['metodo_entrega'] ?? 'domicilio'),
        ],
        'payment_intent_data' => [
            'metadata' => [
                'pedido_id' => (string) $order['id'],
                'public_id' => (string) $order['public_id'],
                'metodo_entrega' => (string) ($order['metodo_entrega'] ?? 'domicilio'),
            ],
        ],
        'billing_address_collection' => 'auto',
        'submit_type' => 'pay',
    ];
}

function stripeApiPost(string $endpoint, array $payload): array
{
    if (!stripeIsConfigured()) {
        throw new RuntimeException('Stripe no está configurado. Falta STRIPE_SECRET_KEY.');
    }

    if (!function_exists('curl_init')) {
        throw new RuntimeException('El servidor no tiene activa la extensión cURL de PHP.');
    }

    $ch = curl_init('https://api.stripe.com/v1/' . ltrim($endpoint, '/'));

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => STRIPE_SECRET_KEY . ':',
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
        ],
    ]);

    $raw = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false || $raw === '') {
        throw new RuntimeException('No se ha podido conectar con Stripe. ' . $error);
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Stripe ha devuelto una respuesta no válida.');
    }

    if ($status < 200 || $status >= 300) {
        $message = (string) ($decoded['error']['message'] ?? 'Stripe ha rechazado la operación.');
        throw new RuntimeException($message);
    }

    return $decoded;
}

function stripeCreateCheckoutSession(array $order, array $shipping): array
{
    return stripeApiPost('checkout/sessions', stripeBuildCheckoutPayload($order, $shipping));
}

function stripeVerifyWebhookSignature(string $payload, string $signatureHeader, string $endpointSecret, int $toleranceSeconds = 300): bool
{
    if ($endpointSecret === '' || $signatureHeader === '') {
        return false;
    }

    $parts = [];
    foreach (explode(',', $signatureHeader) as $piece) {
        [$key, $value] = array_pad(explode('=', trim($piece), 2), 2, '');
        if ($key !== '' && $value !== '') {
            $parts[$key][] = $value;
        }
    }

    $timestamp = isset($parts['t'][0]) ? (int) $parts['t'][0] : 0;
    $signatures = $parts['v1'] ?? [];

    if ($timestamp <= 0 || $signatures === []) {
        return false;
    }

    if (abs(time() - $timestamp) > $toleranceSeconds) {
        return false;
    }

    $signedPayload = $timestamp . '.' . $payload;
    $expected = hash_hmac('sha256', $signedPayload, $endpointSecret);

    foreach ($signatures as $signature) {
        if (hash_equals($expected, $signature)) {
            return true;
        }
    }

    return false;
}
