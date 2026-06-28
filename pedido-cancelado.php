<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/orders.php';
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Pago cancelado | Boticardo';
$pageDescription = 'El pago del pedido se ha cancelado.';
$canonicalUrl = $siteUrl . '/pedido-cancelado.php';

$currentUser = authCurrentUser();
if (!$currentUser) {
    header('Location: login.php?redirect=carrito.php');
    exit;
}

require_once __DIR__ . '/includes/schema.php';

$publicId = (string) ($_GET['pedido'] ?? '');
if ($publicId !== '') {
    orderCancelPendingForUser($conn, $publicId, (int) $currentUser['id']);
}
$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content">
    <section class="checkout-result-page">
        <div class="container">
            <div class="checkout-result checkout-result--warning">
                <div class="checkout-result__icon" aria-hidden="true">↩️</div>
                <h1>Pago cancelado</h1>
                <p>No se ha cobrado nada. Tu carrito sigue guardado para que puedas revisarlo o intentarlo de nuevo.</p>
                <div class="checkout-result-details__actions">
                    <a href="carrito.php" class="btn btn--primary">Volver al carrito</a>
                    <a href="catalogo.php" class="btn btn--outline">Seguir comprando</a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
