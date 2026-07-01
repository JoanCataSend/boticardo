<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/orders.php';

$pageTitle = 'Mis pedidos | Boticardo';
$pageDescription = 'Consulta tus pedidos, estado, productos e información de envío en Boticardo.';
$canonicalUrl = $siteUrl . '/pedidos.php';

$currentUser = authCurrentUser();
$orders = [];
$orderItems = [];

function pedidosFormatMoney(float $amount): string
{
    return number_format($amount, 2, ',', '.') . ' €';
}

function pedidosFormatDate(?string $date): string
{
    if (!$date) {
        return '—';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '—';
    }

    return date('d/m/Y H:i', $timestamp);
}

if ($currentUser) {
    require_once __DIR__ . '/includes/db.php';

    orderEnsureTables($conn);

    $userId = (int) $currentUser['id'];
    $stmt = $conn->prepare('
        SELECT *
        FROM pedidos
        WHERE usuario_id = ?
        ORDER BY created_at DESC
        LIMIT 100
    ');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }

    $result->free();
    $stmt->close();

    foreach ($orders as $order) {
        $orderItems[(int) $order['id']] = orderGetItems($conn, (int) $order['id']);
    }

    $conn->close();
}

require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content">
    <section class="orders-page">
        <div class="container">
            <div class="section-header orders-page__header">
                <span class="section-header__eyebrow">Mi cuenta</span>
                <h1 class="section-header__title">Mis pedidos</h1>
                <p class="section-header__subtitle">
                    Revisa el estado de tus pedidos, los productos comprados y los datos de envío.
                </p>
            </div>

            <?php if (!$currentUser): ?>
                <div class="orders-login-card">
                    <div class="orders-login-card__icon" aria-hidden="true">🔒</div>
                    <h2>Tienes que iniciar sesión</h2>
                    <p>Para consultar tus pedidos necesitas entrar con tu cuenta de Boticardo.</p>
                    <div class="orders-login-card__actions">
                        <a href="login.php?redirect=pedidos.php" class="btn btn--primary">Iniciar sesión</a>
                        <a href="registro.php" class="btn btn--secondary">Crear cuenta</a>
                    </div>
                </div>
            <?php elseif ($orders === []): ?>
                <div class="orders-empty-card">
                    <div class="orders-empty-card__icon" aria-hidden="true">🛍️</div>
                    <h2>Todavía no tienes pedidos</h2>
                    <p>Cuando hagas una compra, aparecerá aquí con su estado y toda la información del pedido.</p>
                    <a href="catalogo.php" class="btn btn--primary">Ver catálogo</a>
                </div>
            <?php else: ?>
                <div class="orders-list" aria-label="Listado de pedidos">
                    <?php foreach ($orders as $order): ?>
                        <?php
                        $orderId = (int) $order['id'];
                        $status = (string) ($order['estado'] ?? 'pendiente');
                        $safeStatusClass = in_array($status, orderAllowedStatuses(), true) ? $status : 'pendiente';
                        $items = $orderItems[$orderId] ?? [];
                        ?>
                        <article class="order-card">
                            <header class="order-card__header">
                                <div>
                                    <span class="order-card__eyebrow">Pedido #<?= e((string) $orderId) ?></span>
                                    <h2>Realizado el <?= e(pedidosFormatDate((string) ($order['created_at'] ?? ''))) ?></h2>
                                    <p>Referencia: <?= e((string) ($order['public_id'] ?? '')) ?></p>
                                </div>
                                <span class="order-status order-status--<?= e($safeStatusClass) ?>">
                                    <?= e(orderStatusLabel($status)) ?>
                                </span>
                            </header>

                            <div class="order-card__grid">
                                <section class="order-card__block">
                                    <h3>Resumen</h3>
                                    <dl class="order-info-list">
                                        <div>
                                            <dt>Subtotal</dt>
                                            <dd><?= e(pedidosFormatMoney((float) ($order['subtotal'] ?? 0))) ?></dd>
                                        </div>
                                        <div>
                                            <dt>Envío</dt>
                                            <dd><?= e(pedidosFormatMoney((float) ($order['envio'] ?? 0))) ?></dd>
                                        </div>
                                        <div class="order-info-list__total">
                                            <dt>Total</dt>
                                            <dd><?= e(pedidosFormatMoney((float) ($order['total'] ?? 0))) ?></dd>
                                        </div>
                                    </dl>
                                </section>

                                <section class="order-card__block">
                                    <h3><?= e(orderDeliveryLabel((string) ($order['metodo_entrega'] ?? 'domicilio'))) ?></h3>
                                    <?php if (orderNormalizeDeliveryMethod((string) ($order['metodo_entrega'] ?? 'domicilio')) === 'recogida'): ?>
                                        <p class="order-address">
                                            <strong><?= e((string) ($order['nombre_envio'] ?? '')) ?></strong><br>
                                            Recogida en farmacia.<br>
                                            Te avisaremos cuando el pedido esté listo.<br>
                                            Tel. <?= e((string) ($order['telefono_envio'] ?? '')) ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="order-address">
                                            <strong><?= e((string) ($order['nombre_envio'] ?? '')) ?></strong><br>
                                            <?= e((string) ($order['direccion_envio'] ?? '')) ?><br>
                                            <?= e((string) ($order['codigo_postal'] ?? '')) ?> <?= e((string) ($order['localidad'] ?? '')) ?>,
                                            <?= e((string) ($order['provincia'] ?? '')) ?><br>
                                            Tel. <?= e((string) ($order['telefono_envio'] ?? '')) ?>
                                        </p>
                                    <?php endif; ?>
                                </section>

                                <section class="order-card__block">
                                    <h3>Seguimiento</h3>
                                    <?php if (!empty($order['tracking_code'])): ?>
                                        <p class="order-tracking-code"><?= e((string) $order['tracking_code']) ?></p>
                                    <?php else: ?>
                                        <p class="order-muted">Todavía no hay código de seguimiento.</p>
                                    <?php endif; ?>
                                    <?php if (!empty($order['paid_at'])): ?>
                                        <p class="order-muted">Pago confirmado: <?= e(pedidosFormatDate((string) $order['paid_at'])) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($order['public_id'])): ?>
                                        <a class="order-download-link" href="descargar-justificante.php?pedido=<?= e(rawurlencode((string) $order['public_id'])) ?>">Descargar justificante</a>
                                    <?php endif; ?>
                                </section>
                            </div>

                            <details class="order-items" open>
                                <summary>
                                    Productos del pedido
                                    <span><?= count($items) ?> producto<?= count($items) === 1 ? '' : 's' ?></span>
                                </summary>

                                <?php if ($items === []): ?>
                                    <p class="order-muted">No hay productos asociados a este pedido.</p>
                                <?php else: ?>
                                    <div class="order-items__list">
                                        <?php foreach ($items as $item): ?>
                                            <div class="order-item-row">
                                                <div>
                                                    <strong><?= e((string) $item['nombre_producto']) ?></strong>
                                                    <?php if (!empty($item['marca_producto'])): ?>
                                                        <span><?= e((string) $item['marca_producto']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="order-item-row__price">
                                                    <span><?= (int) $item['cantidad'] ?> × <?= e(pedidosFormatMoney((float) $item['precio_unitario'])) ?></span>
                                                    <strong><?= e(pedidosFormatMoney((float) $item['subtotal'])) ?></strong>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </details>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
