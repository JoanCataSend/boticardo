<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/auth.php';

if (!authIsLoggedIn()) {
    header('Location: login.php?redirect=cuenta.php');
    exit;
}

$currentUser = authCurrentUser();

$pageTitle = 'Mi cuenta | Boticardo';
$pageDescription = 'Gestiona tu cuenta de Boticardo.';
$canonicalUrl = $siteUrl . '/cuenta.php';

require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content">
    <section class="auth-page">
        <div class="container">
            <div class="auth-card auth-card--account">
                <div class="auth-card__header">
                    <span class="section-header__eyebrow">Mi cuenta</span>
                    <h1>Hola, <?= e($currentUser['nombre'] ?? 'cliente') ?></h1>
                    <p>Desde aquí podrás consultar tus pedidos y datos cuando añadamos esas secciones.</p>
                </div>

                <div class="account-summary">
                    <div>
                        <strong>Nombre</strong>
                        <span><?= e($currentUser['nombre'] ?? '') ?></span>
                    </div>
                    <div>
                        <strong>Email</strong>
                        <span><?= e($currentUser['email'] ?? '') ?></span>
                    </div>
                </div>

                <div class="account-actions">
                    <a href="carrito.php" class="btn btn--primary">Ver carrito</a>
                    <a href="logout.php" class="btn btn--secondary">Cerrar sesión</a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
