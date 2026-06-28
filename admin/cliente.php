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

$userId = max(0, (int) ($_GET['id'] ?? 0));
$client = $userId > 0 ? adminFetchClient($conn, $userId) : null;

if (!$client) {
    http_response_code(404);
    adminRenderHeader('Cliente no encontrado', 'clientes');
    echo '<section class="admin-card"><p class="admin-empty">No se ha encontrado el cliente.</p><p><a href="clientes.php" class="btn btn--secondary">Volver a clientes</a></p></section>';
    adminRenderFooter();
    exit;
}

$orders = adminFetchOrdersForClient($conn, (int) $client['id']);
adminRenderHeader('Cliente ' . (string) $client['nombre'], 'clientes');
?>
<section class="admin-page-header">
    <div>
        <p class="admin-eyebrow">Ficha de cliente</p>
        <h1><?= e((string) $client['nombre']) ?></h1>
        <p><?= e((string) $client['email']) ?> · Rol: <?= e((string) $client['rol']) ?></p>
    </div>
    <a href="clientes.php" class="btn btn--secondary">Volver</a>
</section>

<section class="admin-card">
    <div class="admin-card__header">
        <h2>Pedidos del cliente</h2>
        <span><?= count($orders) ?> pedido<?= count($orders) === 1 ? '' : 's' ?></span>
    </div>

    <?php if ($orders === []): ?>
        <p class="admin-empty">Este cliente todavía no tiene pedidos.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Estado</th>
                        <th>Total</th>
                        <th>Fecha</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?= e((string) $order['public_id']) ?></td>
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
