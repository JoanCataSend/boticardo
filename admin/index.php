<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/cart.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin.php';

if (function_exists('authEnsureUsuariosTable')) {
    authEnsureUsuariosTable($conn);
}
orderEnsureTables($conn);
adminRequire();

$stats = adminDashboardStats($conn);
$recentOrders = adminFetchOrders($conn, null, 8);

adminRenderHeader('Panel de administración', 'dashboard');
?>
<section class="admin-page-header">
    <div>
        <p class="admin-eyebrow">Farmacia</p>
        <h1>Panel de administración</h1>
        <p>Resumen rápido de pedidos y ventas confirmadas.</p>
    </div>
    <a href="pedidos.php?estado=pagado" class="btn btn--primary">Ver pedidos pendientes</a>
</section>

<section class="admin-stats-grid" aria-label="Resumen de pedidos">
    <article class="admin-stat-card">
        <span>Total pedidos</span>
        <strong><?= (int) $stats['total'] ?></strong>
    </article>
    <article class="admin-stat-card admin-stat-card--warning">
        <span>Por preparar</span>
        <strong><?= (int) $stats['pagado'] ?></strong>
    </article>
    <article class="admin-stat-card">
        <span>Preparando</span>
        <strong><?= (int) $stats['preparando'] ?></strong>
    </article>
    <article class="admin-stat-card">
        <span>Ventas confirmadas</span>
        <strong><?= e(adminFormatMoney((float) $stats['ventas_confirmadas'])) ?></strong>
    </article>
</section>

<section class="admin-card">
    <div class="admin-card__header">
        <h2>Últimos pedidos</h2>
        <a href="pedidos.php">Ver todos</a>
    </div>

    <?php if ($recentOrders === []): ?>
        <p class="admin-empty">Todavía no hay pedidos.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Estado</th>
                        <th>Total</th>
                        <th>Fecha</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td>#<?= e((string) $order['public_id']) ?></td>
                            <td><?= e((string) ($order['usuario_nombre'] ?: $order['email_envio'])) ?></td>
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
