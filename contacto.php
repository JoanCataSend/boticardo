<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/rate_limit.php';

$pageTitle = 'Contacto | Boticardo';
$pageDescription = 'Contacta con Boticardo, farmacia en Manzanera. Resuelve dudas sobre productos, pedidos, envíos o consejo farmacéutico.';
$canonicalUrl = $siteUrl . '/contacto.php';

$contactSubjects = [
    'Consulta sobre producto' => 'Consulta sobre producto',
    'Pedido online' => 'Pedido online',
    'Consejo farmacéutico' => 'Consejo farmacéutico',
    'Envíos o devoluciones' => 'Envíos o devoluciones',
    'Otro' => 'Otro',
];

$formData = [
    'nombre' => '',
    'email' => '',
    'telefono' => '',
    'asunto' => 'Consulta sobre producto',
    'mensaje' => '',
];

$errors = [];
$successMessage = '';

function contactoCleanInput(?string $value): string
{
    return trim((string) $value);
}

function contactoHasHeaderInjection(string $value): bool
{
    return str_contains($value, "\n") || str_contains($value, "\r");
}

function contactoEscapeEmailHeader(string $value): string
{
    $value = trim(str_replace(["\r", "\n"], '', $value));
    return mb_encode_mimeheader($value, 'UTF-8');
}

function contactoSendMessage(string $to, string $fromEmail, string $fromName, array $data): bool
{
    $safeTo = trim($to);
    $safeFromEmail = trim($fromEmail);

    if (!filter_var($safeTo, FILTER_VALIDATE_EMAIL) || !filter_var($safeFromEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $subject = 'Contacto web Boticardo - ' . (string) $data['asunto'];
    $encodedSubject = mb_encode_mimeheader($subject, 'UTF-8');
    $replyName = contactoEscapeEmailHeader((string) $data['nombre']);

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . contactoEscapeEmailHeader($fromName) . ' <' . $safeFromEmail . '>';
    $headers[] = 'Reply-To: ' . $replyName . ' <' . (string) $data['email'] . '>';
    $headers[] = 'X-Mailer: Boticardo';

    $html = '<!doctype html><html lang="es"><body style="margin:0;background:#f4f7f6;font-family:Arial,sans-serif;color:#1f2937;">'
        . '<div style="max-width:720px;margin:0 auto;padding:24px;">'
        . '<div style="background:#ffffff;border-radius:18px;padding:26px;border:1px solid #dbe7e4;">'
        . '<p style="margin:0 0 8px;color:#087f7a;font-weight:700;text-transform:uppercase;letter-spacing:.08em;font-size:12px;">Nuevo mensaje desde la web</p>'
        . '<h1 style="margin:0 0 18px;font-size:24px;color:#123c3a;">Formulario de contacto</h1>'
        . '<p style="margin:0 0 16px;line-height:1.55;">Has recibido una nueva consulta desde la página de contacto de Boticardo.</p>'
        . '<div style="background:#f8faf9;border-radius:14px;padding:16px;line-height:1.65;">'
        . '<strong>Nombre:</strong> ' . e((string) $data['nombre']) . '<br>'
        . '<strong>Email:</strong> ' . e((string) $data['email']) . '<br>'
        . '<strong>Teléfono:</strong> ' . e((string) ($data['telefono'] !== '' ? $data['telefono'] : 'No indicado')) . '<br>'
        . '<strong>Asunto:</strong> ' . e((string) $data['asunto'])
        . '</div>'
        . '<h2 style="font-size:18px;margin:24px 0 10px;color:#123c3a;">Mensaje</h2>'
        . '<div style="white-space:pre-wrap;background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;padding:16px;line-height:1.6;">' . e((string) $data['mensaje']) . '</div>'
        . '</div></div></body></html>';

    return mail($safeTo, $encodedSubject, $html, implode("\r\n", $headers));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'nombre' => contactoCleanInput($_POST['nombre'] ?? ''),
        'email' => contactoCleanInput($_POST['email'] ?? ''),
        'telefono' => contactoCleanInput($_POST['telefono'] ?? ''),
        'asunto' => contactoCleanInput($_POST['asunto'] ?? ''),
        'mensaje' => contactoCleanInput($_POST['mensaje'] ?? ''),
    ];

    $honeypot = contactoCleanInput($_POST['website'] ?? '');
    $privacyAccepted = isset($_POST['privacidad']);

    if (!authValidateCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesión ha caducado. Recarga la página e inténtalo otra vez.';
    }

    if ($honeypot !== '') {
        $errors[] = 'No se pudo enviar el formulario. Inténtalo de nuevo.';
    }

    if ($formData['nombre'] === '' || mb_strlen($formData['nombre']) < 2) {
        $errors[] = 'Indica tu nombre.';
    }

    if ($formData['email'] === '' || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Indica un email válido.';
    }

    if ($formData['mensaje'] === '' || mb_strlen($formData['mensaje']) < 10) {
        $errors[] = 'Escribe un mensaje un poco más completo.';
    }

    if (!isset($contactSubjects[$formData['asunto']])) {
        $errors[] = 'Selecciona un asunto válido.';
    }

    if (!$privacyAccepted) {
        $errors[] = 'Debes aceptar la política de privacidad para enviar el mensaje.';
    }

    if (
        contactoHasHeaderInjection($formData['nombre'])
        || contactoHasHeaderInjection($formData['email'])
        || contactoHasHeaderInjection($formData['telefono'])
        || contactoHasHeaderInjection($formData['asunto'])
    ) {
        $errors[] = 'Los datos introducidos no son válidos.';
    }

    if (!$errors) {
        $ipActual = rateLimitClientIp();
        $emailRateLimit = rateLimitIdentifier($formData['email'], 'email-vacio');
        $limiteIp = rateLimitConsume('contacto-ip', $ipActual, 5, 60 * 60);
        $limiteEmail = rateLimitConsume('contacto-email', $emailRateLimit, 3, 60 * 60);

        if (!$limiteIp['ok']) {
            $errors[] = rateLimitMessage('envío de mensajes', (int) $limiteIp['retry_after']);
        } elseif (!$limiteEmail['ok']) {
            $errors[] = rateLimitMessage('envío de mensajes', (int) $limiteEmail['retry_after']);
        }
    }

    if (!$errors) {
        $sent = contactoSendMessage(
            $email,
            defined('MAIL_FROM_EMAIL') ? (string) MAIL_FROM_EMAIL : $email,
            defined('MAIL_FROM_NAME') ? (string) MAIL_FROM_NAME : $siteName,
            $formData
        );

        if ($sent) {
            $successMessage = 'Mensaje enviado correctamente. Te responderemos lo antes posible.';
            $formData = [
                'nombre' => '',
                'email' => '',
                'telefono' => '',
                'asunto' => 'Consulta sobre producto',
                'mensaje' => '',
            ];
        } else {
            $errors[] = 'No se pudo enviar el mensaje. Puedes llamarnos o escribirnos directamente por email.';
        }
    }
}

require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content">
    <section class="contact-page" aria-labelledby="contact-title">
        <div class="container">
            <div class="contact-hero">
                <span class="section-header__eyebrow">Contacto</span>
                <h1 id="contact-title">¿Necesitas ayuda?</h1>
                <p>
                    Escríbenos para resolver dudas sobre productos, pedidos, envíos o consejo farmacéutico.
                    También puedes llamarnos o venir a la farmacia en Manzanera.
                </p>
            </div>

            <div class="contact-layout">
                <div class="contact-info" aria-label="Datos de contacto de Boticardo">
                    <div class="contact-info__card contact-info__card--featured">
                        <p class="contact-info__eyebrow">Atención personalizada</p>
                        <h2>Farmacia Boticardo</h2>
                        <p>
                            Te atendemos desde nuestra farmacia física y online con asesoramiento cercano.
                        </p>
                    </div>

                    <div class="contact-info__card">
                        <div class="contact-info__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 3.07 9.8 19.79 19.79 0 0 1 0 1.13 2 2 0 0 1 2 .18h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L6.09 7.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        </div>
                        <div>
                            <p class="contact-info__label">Teléfono</p>
                            <a href="tel:<?= e($phoneE164) ?>" class="contact-info__value"><?= e($phoneDisplay) ?></a>
                        </div>
                    </div>

                    <div class="contact-info__card">
                        <div class="contact-info__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        </div>
                        <div>
                            <p class="contact-info__label">Email</p>
                            <a href="mailto:<?= e($email) ?>" class="contact-info__value"><?= e($email) ?></a>
                        </div>
                    </div>

                    <div class="contact-info__card">
                        <div class="contact-info__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <div>
                            <p class="contact-info__label">Dirección</p>
                            <p class="contact-info__value">
                                <?= e($streetAddress) ?><br>
                                <?= e($postalCode . ' ' . $locality . ', ' . $region) ?>
                            </p>
                        </div>
                    </div>

                    <div class="contact-info__card contact-hours">
                        <p class="contact-info__label">Horario</p>
                        <div class="contact-hours__row">
                            <span>Lunes — Viernes</span>
                            <strong><?= e($horarioVerano) ?><br><?= e($horarioVeranoT) ?></strong>
                        </div>
                        <div class="contact-hours__row">
                            <span>Sábados</span>
                            <strong><?= e($horarioVeranoV) ?></strong>
                        </div>
                        <div class="contact-hours__row">
                            <span>Domingos</span>
                            <strong>Cerrado</strong>
                        </div>
                    </div>
                </div>

                <div class="contact-form-card">
                    <div class="contact-form-card__header">
                        <h2>Envíanos un mensaje</h2>
                        <p>Rellena el formulario y te contestaremos en cuanto podamos.</p>
                    </div>

                    <?php if ($successMessage !== ''): ?>
                        <div class="auth-alert auth-alert--success" role="status"><?= e($successMessage) ?></div>
                    <?php endif; ?>

                    <?php if ($errors): ?>
                        <div class="auth-alert auth-alert--error" role="alert">
                            <strong>Revisa estos campos:</strong>
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?= e($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="contacto.php" method="post" class="contact-form" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= e(authCsrfToken()) ?>">

                        <div class="contact-form__hidden" aria-hidden="true">
                            <label for="contact-website">No rellenar este campo</label>
                            <input id="contact-website" name="website" type="text" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="contact-form__grid">
                            <div class="auth-field">
                                <label for="contact-name">Nombre</label>
                                <input id="contact-name" name="nombre" type="text" autocomplete="name" required value="<?= e($formData['nombre']) ?>">
                            </div>

                            <div class="auth-field">
                                <label for="contact-email">Email</label>
                                <input id="contact-email" name="email" type="email" autocomplete="email" required value="<?= e($formData['email']) ?>">
                            </div>
                        </div>

                        <div class="contact-form__grid">
                            <div class="auth-field">
                                <label for="contact-phone">Teléfono <span>opcional</span></label>
                                <input id="contact-phone" name="telefono" type="tel" autocomplete="tel" value="<?= e($formData['telefono']) ?>">
                            </div>

                            <div class="auth-field">
                                <label for="contact-subject">Asunto</label>
                                <select id="contact-subject" name="asunto" required>
                                    <?php foreach ($contactSubjects as $value => $label): ?>
                                        <option value="<?= e($value) ?>" <?= $formData['asunto'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="auth-field">
                            <label for="contact-message">Mensaje</label>
                            <textarea id="contact-message" name="mensaje" rows="7" required><?= e($formData['mensaje']) ?></textarea>
                        </div>

                        <label class="contact-form__privacy">
                            <input type="checkbox" name="privacidad" required>
                            <span>Acepto la <a href="privacidad.php">política de privacidad</a> y autorizo el uso de mis datos para responder a esta consulta.</span>
                        </label>

                        <button type="submit" class="btn btn--primary contact-form__submit">Enviar mensaje</button>
                    </form>
                </div>
            </div>

            <div class="contact-map-card">
                <div class="contact-map-card__header">
                    <div>
                        <span class="section-header__eyebrow">Ubicación</span>
                        <h2>Ven a visitarnos</h2>
                    </div>
                    <a href="<?= e($mapsUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn--outline">Cómo llegar</a>
                </div>

                <div class="contact-map-card__frame">
                    <iframe
                        src="<?= e($mapsEmbedUrl) ?>"
                        title="Mapa de Boticardo en <?= e($streetAddress . ', ' . $locality) ?>"
                        loading="lazy"
                        allowfullscreen
                        referrerpolicy="no-referrer-when-downgrade"
                    ></iframe>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
