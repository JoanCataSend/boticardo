<?php
declare(strict_types=1);

function mailerEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function mailerFormatMoney(float $amount, string $currency = 'EUR'): string
{
    return number_format($amount, 2, ',', '.') . ' ' . strtoupper($currency);
}

function mailerSendHtml(string $to, string $subject, string $html, ?string $plainText = null): bool
{
    $to = trim($to);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log('Boticardo - ADMIN_ORDER_EMAIL no es válido: ' . $to);
        return false;
    }

    $fromEmail = defined('MAIL_FROM_EMAIL') ? (string) MAIL_FROM_EMAIL : 'no-reply@boticardo.es';
    $fromName = defined('MAIL_FROM_NAME') ? (string) MAIL_FROM_NAME : 'Boticardo';

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . mb_encode_mimeheader($fromName, 'UTF-8') . ' <' . $fromEmail . '>';
    $headers[] = 'Reply-To: ' . $fromEmail;
    $headers[] = 'X-Mailer: Boticardo';

    $encodedSubject = mb_encode_mimeheader($subject, 'UTF-8');

    $ok = mail($to, $encodedSubject, $html, implode("\r\n", $headers));

    if (!$ok) {
        error_log('Boticardo - No se pudo enviar el correo: ' . $subject . ' a ' . $to);
    }

    return $ok;
}


function mailerBuildEmailVerificationHtml(array $user, string $code): string
{
    $name = trim((string) ($user['nombre'] ?? ''));
    $email = (string) ($user['email'] ?? '');
    $verifyUrl = '';

    if (defined('APP_BASE_URL') && APP_BASE_URL !== '') {
        $verifyUrl = rtrim((string) APP_BASE_URL, '/') . '/verificar-email.php?email=' . rawurlencode($email);
    }

    $hello = $name !== '' ? 'Hola ' . mailerEscape($name) . ',' : 'Hola,';
    $verifyButton = $verifyUrl !== ''
        ? '<p style="margin:24px 0 0;"><a href="' . mailerEscape($verifyUrl) . '" style="display:inline-block;background:#087f7a;color:#fff;text-decoration:none;padding:12px 18px;border-radius:999px;font-weight:700;">Verificar mi correo</a></p>'
        : '';

    return '<!doctype html><html lang="es"><body style="margin:0;background:#f4f7f6;font-family:Arial,sans-serif;color:#1f2937;">'
        . '<div style="max-width:680px;margin:0 auto;padding:24px;">'
        . '<div style="background:#ffffff;border-radius:18px;padding:26px;border:1px solid #dbe7e4;">'
        . '<p style="margin:0 0 8px;color:#087f7a;font-weight:700;text-transform:uppercase;letter-spacing:.08em;font-size:12px;">Verificación de cuenta</p>'
        . '<h1 style="margin:0 0 18px;font-size:26px;color:#123c3a;">Confirma tu correo en Boticardo</h1>'
        . '<p style="font-size:16px;line-height:1.55;margin:0 0 14px;">' . $hello . '</p>'
        . '<p style="font-size:16px;line-height:1.55;margin:0 0 18px;">Introduce este código en la web para verificar que este correo te pertenece:</p>'
        . '<div style="font-size:34px;font-weight:900;letter-spacing:8px;text-align:center;color:#087f7a;background:#ecf7f5;border:1px solid #bfe4de;border-radius:16px;padding:18px;margin:18px 0;">' . mailerEscape($code) . '</div>'
        . '<p style="font-size:14px;line-height:1.55;color:#4b5563;margin:0;">Este código caduca en 30 minutos. Si no has creado una cuenta en Boticardo, puedes ignorar este correo.</p>'
        . $verifyButton
        . '</div></div></body></html>';
}

function mailerSendEmailVerificationCode(array $user, string $code): bool
{
    $to = (string) ($user['email'] ?? '');
    $subject = 'Tu código de verificación de Boticardo';
    $html = mailerBuildEmailVerificationHtml($user, $code);
    $plain = 'Tu código de verificación de Boticardo es: ' . $code . '. Caduca en 30 minutos.';

    return mailerSendHtml($to, $subject, $html, $plain);
}

function mailerBuildNewOrderHtml(array $order, array $items): string
{
    $orderNumber = (string) ($order['public_id'] ?? $order['id'] ?? '');
    $currency = (string) ($order['moneda'] ?? 'EUR');
    $adminUrl = '';

    if (defined('APP_BASE_URL') && APP_BASE_URL !== '') {
        $adminUrl = rtrim((string) APP_BASE_URL, '/') . '/admin/pedido.php?id=' . (int) ($order['id'] ?? 0);
    }

    $rows = '';
    foreach ($items as $item) {
        $rows .= '<tr>'
            . '<td style="padding:10px;border-bottom:1px solid #e5e7eb;">' . mailerEscape((string) ($item['nombre_producto'] ?? 'Producto')) . '</td>'
            . '<td style="padding:10px;border-bottom:1px solid #e5e7eb;text-align:center;">' . (int) ($item['cantidad'] ?? 1) . '</td>'
            . '<td style="padding:10px;border-bottom:1px solid #e5e7eb;text-align:right;">' . mailerEscape(mailerFormatMoney((float) ($item['precio_unitario'] ?? 0), $currency)) . '</td>'
            . '<td style="padding:10px;border-bottom:1px solid #e5e7eb;text-align:right;font-weight:700;">' . mailerEscape(mailerFormatMoney((float) ($item['subtotal'] ?? 0), $currency)) . '</td>'
            . '</tr>';
    }

    $adminButton = $adminUrl !== ''
        ? '<p style="margin:24px 0 0;"><a href="' . mailerEscape($adminUrl) . '" style="display:inline-block;background:#087f7a;color:#fff;text-decoration:none;padding:12px 18px;border-radius:999px;font-weight:700;">Ver pedido en el panel</a></p>'
        : '';

    return '<!doctype html><html lang="es"><body style="margin:0;background:#f4f7f6;font-family:Arial,sans-serif;color:#1f2937;">'
        . '<div style="max-width:760px;margin:0 auto;padding:24px;">'
        . '<div style="background:#ffffff;border-radius:18px;padding:26px;border:1px solid #dbe7e4;">'
        . '<p style="margin:0 0 8px;color:#087f7a;font-weight:700;text-transform:uppercase;letter-spacing:.08em;font-size:12px;">Nuevo pedido pagado</p>'
        . '<h1 style="margin:0 0 18px;font-size:26px;color:#123c3a;">Pedido #' . mailerEscape($orderNumber) . '</h1>'
        . '<p style="font-size:16px;line-height:1.55;margin:0 0 20px;">Se ha confirmado un pago en Boticardo. Revisa el pedido y prepáralo manualmente desde el panel de administración.</p>'
        . '<h2 style="font-size:18px;margin:24px 0 10px;color:#123c3a;">Datos de envío</h2>'
        . '<div style="background:#f8faf9;border-radius:14px;padding:16px;line-height:1.55;">'
        . '<strong>' . mailerEscape((string) ($order['nombre_envio'] ?? '')) . '</strong><br>'
        . mailerEscape((string) ($order['direccion_envio'] ?? '')) . '<br>'
        . mailerEscape((string) ($order['codigo_postal'] ?? '')) . ' ' . mailerEscape((string) ($order['localidad'] ?? '')) . ', ' . mailerEscape((string) ($order['provincia'] ?? '')) . '<br>'
        . 'Teléfono: ' . mailerEscape((string) ($order['telefono_envio'] ?? '')) . '<br>'
        . 'Email: ' . mailerEscape((string) ($order['email_envio'] ?? ''))
        . '</div>'
        . '<h2 style="font-size:18px;margin:24px 0 10px;color:#123c3a;">Productos</h2>'
        . '<table role="presentation" style="width:100%;border-collapse:collapse;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">'
        . '<thead><tr style="background:#ecf7f5;color:#123c3a;">'
        . '<th style="padding:10px;text-align:left;">Producto</th><th style="padding:10px;text-align:center;">Cant.</th><th style="padding:10px;text-align:right;">Precio</th><th style="padding:10px;text-align:right;">Subtotal</th>'
        . '</tr></thead><tbody>' . $rows . '</tbody></table>'
        . '<p style="text-align:right;font-size:20px;font-weight:700;color:#123c3a;margin:20px 0 0;">Total: ' . mailerEscape(mailerFormatMoney((float) ($order['total'] ?? 0), $currency)) . '</p>'
        . $adminButton
        . '</div></div></body></html>';
}

function mailerSendNewOrderAdmin(array $order, array $items): bool
{
    $to = defined('ADMIN_ORDER_EMAIL') ? (string) ADMIN_ORDER_EMAIL : '';
    $subject = 'Nuevo pedido pagado en Boticardo #' . (string) ($order['public_id'] ?? $order['id'] ?? '');
    $html = mailerBuildNewOrderHtml($order, $items);

    return mailerSendHtml($to, $subject, $html);
}
