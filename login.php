<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/rate_limit.php';
require_once __DIR__ . '/includes/account.php';
require_once __DIR__ . '/includes/db.php';

authEnsureUsuariosTable($conn);

$redirect = authSafeRedirect($_GET['redirect'] ?? $_POST['redirect'] ?? 'index.php');
$error = '';
$success = '';
$verificationEmail = '';

if (authIsLoggedIn()) {
    header('Location: ' . $redirect);
    exit;
}

if (isset($_GET['registro']) && $_GET['registro'] === 'ok') {
    $success = 'Cuenta creada correctamente. Ya puedes continuar.';
}

if (isset($_GET['verificado']) && $_GET['verificado'] === 'ok') {
    $success = 'Correo verificado correctamente. Ya puedes continuar.';
}

if (isset($_GET['error'])) {
    $error = match ((string) $_GET['error']) {
        'social_config' => 'Falta configurar ese método de acceso social.',
        'social' => 'No se pudo iniciar sesión con ese proveedor. Inténtalo otra vez.',
        'csrf' => 'La sesión ha caducado. Vuelve a intentarlo.',
        default => 'No se pudo iniciar sesión. Inténtalo otra vez.',
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!authValidateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'La sesión ha caducado. Recarga la página e inténtalo otra vez.';
    } else {
        $emailIntento = strtolower(trim((string) ($_POST['email'] ?? '')));
        $ipActual = rateLimitClientIp();

        $limiteIp = rateLimitConsume('login-ip', $ipActual, 12, 15 * 60);
        $limiteEmail = rateLimitConsume('login-email', rateLimitIdentifier($emailIntento, 'email-vacio'), 6, 15 * 60);

        if (!$limiteIp['ok']) {
            $error = rateLimitMessage('inicio de sesión', (int) $limiteIp['retry_after']);
        } elseif (!$limiteEmail['ok']) {
            $error = rateLimitMessage('inicio de sesión', (int) $limiteEmail['retry_after']);
        } else {
            $result = authLoginWithPassword(
                $conn,
                $emailIntento,
                (string) ($_POST['password'] ?? '')
            );

            if ($result['ok']) {
                rateLimitReset('login-ip', $ipActual);
                rateLimitReset('login-email', rateLimitIdentifier($emailIntento, 'email-vacio'));

                $loggedUser = authCurrentUser();
                if ($loggedUser) {
                    accountSyncSessionWithDatabase($conn, (int) $loggedUser['id']);
                }

                header('Location: ' . $redirect);
                exit;
            }

            $error = (string) $result['message'];
            if (!empty($result['needs_verification']) && !empty($result['email'])) {
                $verificationEmail = (string) $result['email'];
            }
        }
    }
}

$conn->close();

$pageTitle = 'Iniciar sesión | Boticardo';
$pageDescription = 'Accede a tu cuenta de Boticardo para finalizar compras y consultar tus pedidos.';
$canonicalUrl = $siteUrl . '/login.php';

require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content">
    <section class="auth-page">
        <div class="container">
            <div class="auth-card">
                <div class="auth-card__header">
                    <span class="section-header__eyebrow">Mi cuenta</span>
                    <h1>Iniciar sesión</h1>
                    <p>Accede para finalizar compras y mantener tus datos guardados.</p>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="auth-alert auth-alert--error" role="alert">
                        <?= e($error) ?>
                        <?php if ($verificationEmail !== ''): ?>
                            <br>
                            <a href="verificar-email.php?email=<?= e(rawurlencode($verificationEmail)) ?>&redirect=<?= e(rawurlencode($redirect)) ?>">Introducir código de verificación</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success !== ''): ?>
                    <div class="auth-alert auth-alert--success" role="status"><?= e($success) ?></div>
                <?php endif; ?>

                <div class="auth-social">
                    <a
                        href="<?= authIsGoogleConfigured() ? e('auth_social.php?provider=google&redirect=' . rawurlencode($redirect)) : '#' ?>"
                        class="auth-social__btn <?= authIsGoogleConfigured() ? '' : 'auth-social__btn--disabled' ?>"
                        aria-disabled="<?= authIsGoogleConfigured() ? 'false' : 'true' ?>"
                    >
                        <span class="auth-social__icon" aria-hidden="true">G</span>
                        Continuar con Google
                    </a>

                    <a
                        href="<?= authIsAppleConfigured() ? e('auth_social.php?provider=apple&redirect=' . rawurlencode($redirect)) : '#' ?>"
                        class="auth-social__btn auth-social__btn--apple <?= authIsAppleConfigured() ? '' : 'auth-social__btn--disabled' ?>"
                        aria-disabled="<?= authIsAppleConfigured() ? 'false' : 'true' ?>"
                    >
                        <span class="auth-social__icon" aria-hidden="true"></span>
                        Continuar con Apple
                    </a>
                </div>

                <?php if (!authIsGoogleConfigured() || !authIsAppleConfigured()): ?>
                    <p class="auth-config-note">
                        Los botones sociales aparecen preparados, pero para activarlos hay que añadir las credenciales reales en <code>includes/config.php</code> o como variables de entorno.
                    </p>
                <?php endif; ?>

                <div class="auth-divider"><span>o con email</span></div>

                <form action="login.php" method="post" class="auth-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e(authCsrfToken()) ?>">
                    <input type="hidden" name="redirect" value="<?= e($redirect) ?>">

                    <div class="auth-field">
                        <label for="login-email">Email</label>
                        <input id="login-email" name="email" type="email" autocomplete="email" required value="<?= e($_POST['email'] ?? '') ?>">
                    </div>

                    <div class="auth-field">
                        <label for="login-password">Contraseña</label>
                        <input id="login-password" name="password" type="password" autocomplete="current-password" required>
                    </div>

                    <button type="submit" class="btn btn--primary auth-submit">Iniciar sesión</button>
                </form>

                <p class="auth-switch">
                    ¿No tienes cuenta?
                    <a href="registro.php?redirect=<?= e(rawurlencode($redirect)) ?>">Regístrate aquí</a>
                </p>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
