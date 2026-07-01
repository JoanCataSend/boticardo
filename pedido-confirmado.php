<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/orders.php';
require_once __DIR__ . '/includes/account.php';
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Pedido recibido | Boticardo';
$pageDescription = 'Confirmación del pedido en Boticardo.';
$canonicalUrl = $siteUrl . '/pedido-confirmado.php';

$currentUser = authCurrentUser();
if (!$currentUser) {
    header('Location: login.php?redirect=pedido-confirmado.php');
    exit;
}

require_once __DIR__ . '/includes/schema.php';

$publicId = (string) ($_GET['pedido'] ?? '');
$order = $publicId !== '' ? orderGetByPublicIdForUser($conn, $publicId, (int) $currentUser['id']) : null;
$items = $order ? orderGetItems($conn, (int) $order['id']) : [];

if ($order && (string) $order['estado'] === 'pagado') {
    cartClear();
    accountPersistCartFromSession($conn, (int) $currentUser['id']);
}

$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content">
    <section class="checkout-result-page">
        <div class="container">
            <?php if (!$order): ?>
                <div class="checkout-result checkout-result--warning">
                    <div class="checkout-result__icon" aria-hidden="true">⚠️</div>
                    <h1>No hemos encontrado este pedido</h1>
                    <p>Puede que el enlace sea incorrecto o que el pedido pertenezca a otra cuenta.</p>
                    <a href="carrito.php" class="btn btn--primary">Volver al carrito</a>
                </div>
            <?php elseif ((string) $order['estado'] === 'pagado'): ?>
                <div class="checkout-result checkout-result--success">
                    <div class="checkout-result__icon" aria-hidden="true">✅</div>
                    <h1>Pedido pagado correctamente</h1>
                    <p>Tu pedido <strong>#<?= e((string) $order['id']) ?></strong> se ha confirmado correctamente.</p>
                    <p>Te enviaremos las actualizaciones al email <strong><?= e((string) $order['email_envio']) ?></strong>.</p>
                </div>
            <?php else: ?>
                <div class="checkout-result checkout-result--pending">
                    <div class="checkout-result__icon" aria-hidden="true">⏳</div>
                    <h1>Pago recibido, confirmación en proceso</h1>
                    <p>Stripe todavía no ha confirmado el pago mediante webhook. No cierres esta página si quieres revisarla dentro de unos segundos.</p>
                    <p>Estado actual del pedido: <strong><?= e((string) $order['estado']) ?></strong>.</p>
                </div>
            <?php endif; ?>

            <?php if ($order): ?>
                <div class="checkout-result-details">
                    <h2>Resumen</h2>

                    <?php foreach ($items as $item): ?>
                        <div class="checkout-summary__item">
                            <div>
                                <strong><?= e((string) $item['nombre_producto']) ?></strong>
                                <span><?= (int) $item['cantidad'] ?> × <?= number_format((float) $item['precio_unitario'], 2, ',', '.') ?> €</span>
                            </div>
                            <span><?= number_format((float) $item['subtotal'], 2, ',', '.') ?> €</span>
                        </div>
                    <?php endforeach; ?>

                    <div class="checkout-summary__row">
                        <span>Entrega</span>
                        <strong><?= e(orderDeliveryLabel((string) ($order['metodo_entrega'] ?? 'domicilio'))) ?></strong>
                    </div>
                    <div class="checkout-summary__row">
                        <span>Envío</span>
                        <strong><?= (float) ($order['envio'] ?? 0) === 0.0 ? 'Gratis' : number_format((float) $order['envio'], 2, ',', '.') . ' €' ?></strong>
                    </div>
                    <div class="checkout-summary__total">
                        <span>Total</span>
                        <strong><?= number_format((float) $order['total'], 2, ',', '.') ?> €</strong>
                    </div>

                    <?php if (orderNormalizeDeliveryMethod((string) ($order['metodo_entrega'] ?? 'domicilio')) === 'recogida'): ?>
                        <p class="checkout-security-note">Has elegido recoger en farmacia. Te avisaremos por email cuando el pedido esté listo para recoger.</p>
                    <?php endif; ?>

                    <div class="checkout-result-details__actions">
                        <a href="catalogo.php" class="btn btn--outline">Seguir comprando</a>
                        <a href="cuenta.php" class="btn btn--primary">Ir a mi cuenta</a>
                        <a href="descargar-justificante.php?pedido=<?= e(rawurlencode((string) ($order['public_id'] ?? ''))) ?>" class="btn btn--secondary">Descargar justificante</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
