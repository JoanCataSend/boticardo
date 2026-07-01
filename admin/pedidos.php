<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/cart.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin.php';

authEnsureUsuariosTable($conn);
orderEnsureTables($conn);
adminRequire();

$status = isset($_GET['estado']) ? (string) $_GET['estado'] : null;
if ($status !== null && !in_array($status, orderAllowedStatuses(), true)) {
    $status = null;
}
$orders = adminFetchOrders($conn, $status, 150);
$statuses = adminStatusOptions();

adminRenderHeader('Pedidos', 'pedidos');
?>
<section class="admin-page-header">
    <div>
        <p class="admin-eyebrow">Gestión manual</p>
        <h1>Pedidos</h1>
        <p>Revisa los pedidos pagados, prepara el paquete y actualiza el estado.</p>
    </div>
</section>

<div class="admin-filter-tabs" role="list" aria-label="Filtrar pedidos por estado">
    <a href="pedidos.php" class="<?= $status === null ? 'admin-filter-tabs__link--active' : '' ?>">Todos</a>
    <?php foreach ($statuses as $key => $label): ?>
        <a href="pedidos.php?estado=<?= e($key) ?>" class="<?= $status === $key ? 'admin-filter-tabs__link--active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>

<section class="admin-card">
    <div class="admin-card__header">
        <h2><?= $status ? e($statuses[$status]) : 'Todos los pedidos' ?></h2>
        <span><?= count($orders) ?> resultado<?= count($orders) === 1 ? '' : 's' ?></span>
    </div>

    <?php if ($orders === []): ?>
        <p class="admin-empty">No hay pedidos con este filtro.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Envío</th>
                        <th>Estado</th>
                        <th>Total</th>
                        <th>Fecha</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong>#<?= e((string) $order['public_id']) ?></strong></td>
                            <td>
                                <?= e((string) ($order['usuario_nombre'] ?: $order['nombre_envio'])) ?><br>
                                <small><?= e((string) ($order['usuario_email'] ?: $order['email_envio'])) ?></small>
                            </td>
                            <td>
                                <?= e(orderDeliveryLabel((string) ($order['metodo_entrega'] ?? 'domicilio'))) ?><br>
                                <?php if (orderNormalizeDeliveryMethod((string) ($order['metodo_entrega'] ?? 'domicilio')) === 'recogida'): ?>
                                    <small>Sin envío</small>
                                <?php else: ?>
                                    <small><?= e((string) $order['codigo_postal']) ?> · <?= e((string) $order['provincia']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><span class="<?= e(adminStatusClass((string) $order['estado'])) ?>"><?= e(orderStatusLabel((string) $order['estado'])) ?></span></td>
                            <td><?= e(adminFormatMoney((float) $order['total'], (string) $order['moneda'])) ?></td>
                            <td><?= e(date('d/m/Y H:i', strtotime((string) $order['created_at']))) ?></td>
                            <td><a href="pedido.php?id=<?= (int) $order['id'] ?>" class="admin-table__action">Abrir</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php adminRenderFooter(); ?>
