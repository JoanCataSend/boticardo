<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

authEnsureUsuariosTable($conn);

$redirect = authSafeRedirect($_GET['redirect'] ?? $_POST['redirect'] ?? 'index.php');
$emailVerificacion = strtolower(trim((string) ($_GET['email'] ?? $_POST['email'] ?? '')));
$error = '';
$success = '';

if (authIsLoggedIn()) {
    header('Location: ' . $redirect);
    exit;
}

if (isset($_GET['sent'])) {
    if ($_GET['sent'] === '1') {
        $success = 'Te hemos enviado un código de verificación a tu correo.';
    } elseif ($_GET['sent'] === '0') {
        $error = 'La cuenta se ha creado, pero no se pudo enviar el correo. Revisa la configuración de email del servidor o solicita un nuevo código.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!authValidateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'La sesión ha caducado. Recarga la página e inténtalo otra vez.';
    } else {
        $action = (string) ($_POST['action'] ?? 'verify');

        if ($action === 'resend') {
            $result = authResendEmailVerificationCode($conn, $emailVerificacion);

            if ($result['ok']) {
                $success = (string) $result['message'];
                $error = '';
            } else {
                $error = (string) $result['message'];
            }
        } else {
            $result = authVerifyEmailCode(
                $conn,
                $emailVerificacion,
                (string) ($_POST['codigo'] ?? '')
            );

            if ($result['ok']) {
                header('Location: ' . $redirect);
                exit;
            }

            $error = (string) $result['message'];
        }
    }
}

$conn->close();

$pageTitle = 'Verificar correo | Boticardo';
$pageDescription = 'Introduce el código de verificación enviado a tu correo para activar tu cuenta en Boticardo.';
$canonicalUrl = $siteUrl . '/verificar-email.php';

require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content">
    <section class="auth-page verify-email-page">
        <div class="container">
            <div class="auth-card verify-email-card">
                <div class="auth-card__header">
                    <span class="section-header__eyebrow">Verificación</span>
                    <h1>Verifica tu correo</h1>
                    <p>Introduce el código de 6 números que te hemos enviado por email para activar tu cuenta.</p>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="auth-alert auth-alert--error" role="alert"><?= e($error) ?></div>
                <?php endif; ?>

                <?php if ($success !== ''): ?>
                    <div class="auth-alert auth-alert--success" role="status"><?= e($success) ?></div>
                <?php endif; ?>

                <form action="verificar-email.php" method="post" class="auth-form verify-email-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e(authCsrfToken()) ?>">
                    <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
                    <div class="auth-field">
                        <label for="verify-email">Email</label>
                        <input id="verify-email" name="email" type="email" autocomplete="email" required value="<?= e($emailVerificacion) ?>">
                    </div>

                    <div class="auth-field">
                        <label for="verify-code">Código de verificación</label>
                        <input
                            id="verify-code"
                            name="codigo"
                            type="text"
                            inputmode="numeric"
                            pattern="[0-9]{6}"
                            maxlength="6"
                            autocomplete="one-time-code"
                            class="verify-code-input"
                            required
                        >
                        <small>El código caduca a los 30 minutos.</small>
                    </div>

                    <button type="submit" name="action" value="verify" class="btn btn--primary auth-submit">Verificar correo</button>

                    <div class="verify-resend-form">
                        <button type="submit" name="action" value="resend" class="verify-resend-button" formnovalidate>No me ha llegado, reenviar código</button>
                    </div>
                </form>

                <p class="auth-switch">
                    ¿Ya verificaste tu cuenta?
                    <a href="login.php?redirect=<?= e(rawurlencode($redirect)) ?>">Inicia sesión aquí</a>
                </p>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
