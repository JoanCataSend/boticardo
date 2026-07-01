<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/cart.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin.php';
require_once __DIR__ . '/../includes/admin_catalog.php';

if (function_exists('authEnsureUsuariosTable')) {
    authEnsureUsuariosTable($conn);
}
orderEnsureTables($conn);
adminCatalogEnsure($conn);
adminRequire();

$stats = adminDashboardStats($conn);
$storeStats = adminStoreStats($conn);
$recentOrders = adminFetchOrders($conn, null, 8);

$shortcuts = [
    ['Productos', 'Crea productos, sube imágenes, controla stock y ofertas.', 'productos.php', 'Gestionar'],
    ['Categorías', 'Ordena el catálogo por familias de farmacia.', 'categorias.php', 'Editar'],
    ['Laboratorios', 'Gestiona marcas y laboratorios visibles en filtros.', 'laboratorios.php', 'Editar'],
    ['Cupones', 'Crea descuentos por porcentaje o importe fijo.', 'cupones.php', 'Configurar'],
    ['Banners', 'Controla los banners/ofertas de portada.', 'banners.php', 'Configurar'],
    ['Páginas legales', 'Edita los textos legales desde el panel.', 'paginas-legales.php', 'Editar'],
];

adminRenderHeader('Panel de administración', 'dashboard');
?>
<section class="admin-page-header">
    <div>
        <p class="admin-eyebrow">Farmacia</p>
        <h1>Panel de administración</h1>
        <p>Resumen rápido de pedidos, ventas y contenido de la tienda.</p>
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
        <span>Productos</span>
        <strong><?= (int) $storeStats['productos'] ?></strong>
    </article>
    <article class="admin-stat-card">
        <span>Ventas confirmadas</span>
        <strong><?= e(adminFormatMoney((float) $stats['ventas_confirmadas'])) ?></strong>
    </article>
</section>

<section class="admin-stats-grid admin-stats-grid--compact" aria-label="Resumen de catálogo">
    <article class="admin-stat-card">
        <span>Stock agotado</span>
        <strong><?= (int) $storeStats['agotados'] ?></strong>
    </article>
    <article class="admin-stat-card">
        <span>Categorías</span>
        <strong><?= (int) $storeStats['categorias'] ?></strong>
    </article>
    <article class="admin-stat-card">
        <span>Cupones activos</span>
        <strong><?= (int) $storeStats['cupones_activos'] ?></strong>
    </article>
    <article class="admin-stat-card">
        <span>Banners activos</span>
        <strong><?= (int) $storeStats['banners_activos'] ?></strong>
    </article>
</section>

<section class="admin-card">
    <div class="admin-card__header">
        <h2>Gestión de tienda</h2>
        <span>Accesos rápidos</span>
    </div>
    <div class="admin-shortcut-grid">
        <?php foreach ($shortcuts as [$title, $description, $url, $action]): ?>
            <article class="admin-shortcut-card">
                <h3><?= e($title) ?></h3>
                <p><?= e($description) ?></p>
                <a href="<?= e($url) ?>" class="admin-table__action"><?= e($action) ?></a>
            </article>
        <?php endforeach; ?>
    </div>
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
