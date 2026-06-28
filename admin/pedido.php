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

$orderId = max(0, (int) ($_GET['id'] ?? $_POST['id'] ?? 0));
$order = $orderId > 0 ? adminFetchOrder($conn, $orderId) : null;
$items = $order ? orderGetItems($conn, (int) $order['id']) : [];
$success = '';
$error = '';

if (!$order) {
    http_response_code(404);
    adminRenderHeader('Pedido no encontrado', 'pedidos');
    echo '<section class="admin-card"><p class="admin-empty">No se ha encontrado el pedido.</p><p><a href="pedidos.php" class="btn btn--secondary">Volver a pedidos</a></p></section>';
    adminRenderFooter();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!authValidateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'La sesión ha caducado. Recarga la página e inténtalo otra vez.';
    } else {
        try {
            adminUpdateOrder(
                $conn,
                (int) $order['id'],
                (string) ($_POST['estado'] ?? ''),
                (string) ($_POST['admin_notes'] ?? ''),
                (string) ($_POST['tracking_code'] ?? '')
            );
            $success = 'Pedido actualizado correctamente.';
            $order = adminFetchOrder($conn, (int) $order['id']);
            $items = orderGetItems($conn, (int) $order['id']);
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}

$statuses = adminStatusOptions();
adminRenderHeader('Pedido #' . (string) $order['public_id'], 'pedidos');
?>
<section class="admin-page-header">
    <div>
        <p class="admin-eyebrow">Detalle de pedido</p>
        <h1>Pedido #<?= e((string) $order['public_id']) ?></h1>
        <p>Fecha: <?= e(date('d/m/Y H:i', strtotime((string) $order['created_at']))) ?></p>
    </div>
    <a href="pedidos.php" class="btn btn--secondary">Volver</a>
</section>

<?php if ($success !== ''): ?>
    <div class="auth-alert auth-alert--success" role="status"><?= e($success) ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <div class="auth-alert auth-alert--error" role="alert"><?= e($error) ?></div>
<?php endif; ?>

<div class="admin-detail-grid">
    <section class="admin-card">
        <div class="admin-card__header">
            <h2>Productos</h2>
            <span class="<?= e(adminStatusClass((string) $order['estado'])) ?>"><?= e(orderStatusLabel((string) $order['estado'])) ?></span>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cant.</th>
                        <th>Precio</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <strong><?= e((string) $item['nombre_producto']) ?></strong><br>
                                <small><?= e((string) ($item['marca_producto'] ?? '')) ?></small>
                            </td>
                            <td><?= (int) $item['cantidad'] ?></td>
                            <td><?= e(adminFormatMoney((float) $item['precio_unitario'], (string) $order['moneda'])) ?></td>
                            <td><strong><?= e(adminFormatMoney((float) $item['subtotal'], (string) $order['moneda'])) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="admin-total-box">
            <span>Subtotal</span><strong><?= e(adminFormatMoney((float) $order['subtotal'], (string) $order['moneda'])) ?></strong>
            <span>Envío</span><strong><?= e(adminFormatMoney((float) $order['envio'], (string) $order['moneda'])) ?></strong>
            <span>Total</span><strong><?= e(adminFormatMoney((float) $order['total'], (string) $order['moneda'])) ?></strong>
        </div>
    </section>

    <aside class="admin-card admin-card--side">
        <h2>Preparación</h2>
        <form method="post" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?= e(authCsrfToken()) ?>">
            <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">

            <label for="estado">Estado del pedido</label>
            <select id="estado" name="estado">
                <?php foreach ($statuses as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= (string) $order['estado'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="tracking_code">Seguimiento / referencia de envío</label>
            <input id="tracking_code" name="tracking_code" type="text" value="<?= e((string) ($order['tracking_code'] ?? '')) ?>" placeholder="Ej: Correos, GLS, referencia interna…">

            <label for="admin_notes">Notas internas</label>
            <textarea id="admin_notes" name="admin_notes" rows="5" placeholder="Notas solo visibles para administración"><?= e((string) ($order['admin_notes'] ?? '')) ?></textarea>

            <button type="submit" class="btn btn--primary">Guardar cambios</button>
        </form>
    </aside>
</div>

<section class="admin-card">
    <div class="admin-card__header"><h2>Cliente y envío</h2></div>
    <div class="admin-info-grid">
        <div>
            <h3>Cliente</h3>
            <p><strong><?= e((string) ($order['usuario_nombre'] ?: $order['nombre_envio'])) ?></strong></p>
            <p><?= e((string) ($order['usuario_email'] ?: $order['email_envio'])) ?></p>
            <p><a href="cliente.php?id=<?= (int) $order['usuario_id'] ?>">Ver ficha de cliente</a></p>
        </div>
        <div>
            <h3>Dirección de envío</h3>
            <p><strong><?= e((string) $order['nombre_envio']) ?></strong></p>
            <p><?= e((string) $order['direccion_envio']) ?></p>
            <p><?= e((string) $order['codigo_postal']) ?> <?= e((string) $order['localidad']) ?>, <?= e((string) $order['provincia']) ?></p>
            <p>Tel. <?= e((string) $order['telefono_envio']) ?> · <?= e((string) $order['email_envio']) ?></p>
        </div>
        <div>
            <h3>Pago</h3>
            <p>Stripe session: <code><?= e((string) ($order['stripe_checkout_session_id'] ?? '')) ?></code></p>
            <p>Payment intent: <code><?= e((string) ($order['stripe_payment_intent'] ?? '')) ?></code></p>
            <p>Pagado: <?= !empty($order['paid_at']) ? e(date('d/m/Y H:i', strtotime((string) $order['paid_at']))) : 'No confirmado' ?></p>
            <p>Aviso email: <?= !empty($order['admin_notified_at']) ? e(date('d/m/Y H:i', strtotime((string) $order['admin_notified_at']))) : 'Pendiente/no enviado' ?></p>
        </div>
    </div>
</section>
<?php adminRenderFooter(); ?>
