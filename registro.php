<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/rate_limit.php';
require_once __DIR__ . '/includes/db.php';

authEnsureUsuariosTable($conn);

$redirect = authSafeRedirect($_GET['redirect'] ?? $_POST['redirect'] ?? 'index.php');
$error = '';

if (authIsLoggedIn()) {
    header('Location: ' . $redirect);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!authValidateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'La sesión ha caducado. Recarga la página e inténtalo otra vez.';
    } else {
        $emailIntento = strtolower(trim((string) ($_POST['email'] ?? '')));
        $ipActual = rateLimitClientIp();

        $limiteIp = rateLimitConsume('registro-ip', $ipActual, 5, 60 * 60);
        $limiteEmail = rateLimitConsume('registro-email', rateLimitIdentifier($emailIntento, 'email-vacio'), 3, 60 * 60);

        if (!$limiteIp['ok']) {
            $error = rateLimitMessage('registro', (int) $limiteIp['retry_after']);
        } elseif (!$limiteEmail['ok']) {
            $error = rateLimitMessage('registro', (int) $limiteEmail['retry_after']);
        } else {
            $result = authRegisterWithPassword(
                $conn,
                (string) ($_POST['nombre'] ?? ''),
                $emailIntento,
                (string) ($_POST['password'] ?? ''),
                (string) ($_POST['password_confirm'] ?? '')
            );

            if ($result['ok']) {
                $verifyParams = http_build_query([
                    'email' => (string) ($result['email'] ?? ''),
                    'redirect' => $redirect,
                    'sent' => !empty($result['mail_sent']) ? '1' : '0',
                ]);

                header('Location: verificar-email.php?' . $verifyParams);
                exit;
            }

            $error = (string) $result['message'];
        }
    }
}

$conn->close();

$pageTitle = 'Crear cuenta | Boticardo';
$pageDescription = 'Crea tu cuenta de Boticardo para comprar online y guardar tu carrito.';
$canonicalUrl = $siteUrl . '/registro.php';

require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content">
    <section class="auth-page">
        <div class="container">
            <div class="auth-card">
                <div class="auth-card__header">
                    <span class="section-header__eyebrow">Nueva cuenta</span>
                    <h1>Registrarse</h1>
                    <p>Crea tu cuenta y verifica tu correo para poder iniciar sesión.</p>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="auth-alert auth-alert--error" role="alert"><?= e($error) ?></div>
                <?php endif; ?>

                <div class="auth-social">
                    <a
                        href="<?= authIsGoogleConfigured() ? e('auth_social.php?provider=google&redirect=' . rawurlencode($redirect)) : '#' ?>"
                        class="auth-social__btn <?= authIsGoogleConfigured() ? '' : 'auth-social__btn--disabled' ?>"
                        aria-disabled="<?= authIsGoogleConfigured() ? 'false' : 'true' ?>"
                    >
                        <span class="auth-social__icon" aria-hidden="true">G</span>
                        Registrarse con Google
                    </a>

                    <a
                        href="<?= authIsAppleConfigured() ? e('auth_social.php?provider=apple&redirect=' . rawurlencode($redirect)) : '#' ?>"
                        class="auth-social__btn auth-social__btn--apple <?= authIsAppleConfigured() ? '' : 'auth-social__btn--disabled' ?>"
                        aria-disabled="<?= authIsAppleConfigured() ? 'false' : 'true' ?>"
                    >
                        <span class="auth-social__icon" aria-hidden="true"></span>
                        Registrarse con Apple
                    </a>
                </div>

                <?php if (!authIsGoogleConfigured() || !authIsAppleConfigured()): ?>
                    <p class="auth-config-note">
                        Los registros sociales quedan preparados. Se activarán cuando configures las credenciales reales.
                    </p>
                <?php endif; ?>

                <div class="auth-divider"><span>o con email</span></div>

                <form action="registro.php" method="post" class="auth-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e(authCsrfToken()) ?>">
                    <input type="hidden" name="redirect" value="<?= e($redirect) ?>">

                    <div class="auth-field">
                        <label for="register-nombre">Nombre</label>
                        <input id="register-nombre" name="nombre" type="text" autocomplete="name" required value="<?= e($_POST['nombre'] ?? '') ?>">
                    </div>

                    <div class="auth-field">
                        <label for="register-email">Email</label>
                        <input id="register-email" name="email" type="email" autocomplete="email" required value="<?= e($_POST['email'] ?? '') ?>">
                    </div>

                    <div class="auth-field">
                        <label for="register-password">Contraseña</label>
                        <input id="register-password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                        <small>Mínimo 8 caracteres.</small>
                    </div>

                    <div class="auth-field">
                        <label for="register-password-confirm">Repetir contraseña</label>
                        <input id="register-password-confirm" name="password_confirm" type="password" autocomplete="new-password" minlength="8" required>
                    </div>

                    <button type="submit" class="btn btn--primary auth-submit">Crear cuenta y enviar código</button>
                </form>

                <p class="auth-switch">
                    ¿Ya tienes cuenta?
                    <a href="login.php?redirect=<?= e(rawurlencode($redirect)) ?>">Inicia sesión aquí</a>
                </p>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
