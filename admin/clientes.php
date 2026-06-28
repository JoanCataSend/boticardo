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

$clients = adminFetchClients($conn);
adminRenderHeader('Clientes', 'clientes');
?>
<section class="admin-page-header">
    <div>
        <p class="admin-eyebrow">Usuarios</p>
        <h1>Clientes</h1>
        <p>Consulta clientes registrados y sus pedidos.</p>
    </div>
</section>

<section class="admin-card">
    <div class="admin-card__header">
        <h2>Clientes registrados</h2>
        <span><?= count($clients) ?> usuario<?= count($clients) === 1 ? '' : 's' ?></span>
    </div>

    <?php if ($clients === []): ?>
        <p class="admin-empty">Todavía no hay clientes registrados.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Rol</th>
                        <th>Pedidos</th>
                        <th>Total comprado</th>
                        <th>Último pedido</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                        <tr>
                            <td>
                                <strong><?= e((string) $client['nombre']) ?></strong><br>
                                <small><?= e((string) $client['email']) ?></small>
                            </td>
                            <td><?= e((string) $client['rol']) ?></td>
                            <td><?= (int) $client['pedidos_total'] ?></td>
                            <td><?= e(adminFormatMoney((float) $client['compras_total'])) ?></td>
                            <td><?= $client['ultimo_pedido'] ? e(date('d/m/Y H:i', strtotime((string) $client['ultimo_pedido']))) : '—' ?></td>
                            <td><a href="cliente.php?id=<?= (int) $client['id'] ?>" class="admin-table__action">Abrir</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php adminRenderFooter(); ?>
