<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/orders.php';
require_once __DIR__ . '/includes/db.php';

$currentUser = authCurrentUser();
if (!$currentUser) {
    header('Location: login.php?redirect=cuenta.php#pedidos');
    exit;
}

$publicId = trim((string) ($_GET['pedido'] ?? ''));
$order = $publicId !== '' ? orderGetByPublicIdForUser($conn, $publicId, (int) $currentUser['id']) : null;

if (!$order) {
    $conn->close();
    http_response_code(404);
    echo 'No se ha encontrado el pedido o no pertenece a tu cuenta.';
    exit;
}

$items = orderGetItems($conn, (int) $order['id']);
$conn->close();

function justificanteMoney(float $amount): string
{
    return number_format($amount, 2, ',', '.') . ' €';
}

function justificanteDate(?string $date): string
{
    if (!$date) {
        return '—';
    }

    $timestamp = strtotime($date);
    return $timestamp ? date('d/m/Y H:i', $timestamp) : '—';
}

$filename = 'justificante-boticardo-pedido-' . (int) $order['id'] . '.html';
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Justificante pedido #<?= (int) $order['id'] ?> - Boticardo</title>
    <style>
        body { font-family: Arial, sans-serif; color: #183b38; margin: 36px; line-height: 1.5; }
        .header { border-bottom: 3px solid #6BBFB5; padding-bottom: 18px; margin-bottom: 26px; }
        .brand { font-size: 28px; font-weight: 800; color: #2c7a72; margin: 0; }
        h1 { font-size: 24px; margin: 16px 0 6px; }
        h2 { font-size: 17px; color: #2c7a72; margin-top: 28px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border-bottom: 1px solid #dcefeb; padding: 10px 8px; text-align: left; }
        th { background: #eef9f7; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .box { border: 1px solid #dcefeb; border-radius: 12px; padding: 14px; }
        .totals { margin-left: auto; width: min(320px, 100%); }
        .totals div { display: flex; justify-content: space-between; padding: 7px 0; }
        .totals .total { font-size: 18px; font-weight: 800; border-top: 2px solid #6BBFB5; margin-top: 8px; padding-top: 12px; }
        .note { margin-top: 28px; font-size: 13px; color: #60706f; }
        @media print { body { margin: 18mm; } }
    </style>
</head>
<body>
    <div class="header">
        <p class="brand">Boticardo</p>
        <h1>Justificante de pedido #<?= (int) $order['id'] ?></h1>
        <p>Referencia: <?= e((string) ($order['public_id'] ?? '')) ?></p>
        <p>Fecha: <?= e(justificanteDate((string) ($order['created_at'] ?? ''))) ?></p>
        <p>Estado: <?= e(orderStatusLabel((string) ($order['estado'] ?? 'pendiente'))) ?></p>
    </div>

    <div class="grid">
        <section class="box">
            <h2>Cliente</h2>
            <p>
                <strong><?= e((string) ($currentUser['nombre'] ?? $order['nombre_envio'] ?? 'Cliente')) ?></strong><br>
                <?= e((string) ($currentUser['email'] ?? $order['email_envio'] ?? '')) ?>
            </p>
        </section>

        <section class="box">
            <h2><?= e(orderDeliveryLabel((string) ($order['metodo_entrega'] ?? 'domicilio'))) ?></h2>
            <p>
                <strong><?= e((string) ($order['nombre_envio'] ?? '')) ?></strong><br>
                <?php if (orderNormalizeDeliveryMethod((string) ($order['metodo_entrega'] ?? 'domicilio')) === 'recogida'): ?>
                    Recogida en farmacia.<br>
                    Te avisaremos cuando el pedido esté listo.<br>
                <?php else: ?>
                    <?= e((string) ($order['direccion_envio'] ?? '')) ?><br>
                    <?= e((string) ($order['codigo_postal'] ?? '')) ?> <?= e((string) ($order['localidad'] ?? '')) ?>, <?= e((string) ($order['provincia'] ?? '')) ?><br>
                <?php endif; ?>
                Tel. <?= e((string) ($order['telefono_envio'] ?? '')) ?>
            </p>
        </section>
    </div>

    <h2>Productos</h2>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Marca</th>
                <th>Cantidad</th>
                <th>Precio unidad</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= e((string) $item['nombre_producto']) ?></td>
                    <td><?= e((string) ($item['marca_producto'] ?? '')) ?></td>
                    <td><?= (int) $item['cantidad'] ?></td>
                    <td><?= e(justificanteMoney((float) $item['precio_unitario'])) ?></td>
                    <td><?= e(justificanteMoney((float) $item['subtotal'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <div><span>Subtotal</span><strong><?= e(justificanteMoney((float) ($order['subtotal'] ?? 0))) ?></strong></div>
        <div><span>Envío</span><strong><?= e(justificanteMoney((float) ($order['envio'] ?? 0))) ?></strong></div>
        <div class="total"><span>Total</span><strong><?= e(justificanteMoney((float) ($order['total'] ?? 0))) ?></strong></div>
    </div>

    <p class="note">
        Este documento es un justificante de pedido generado desde tu cuenta de Boticardo. No sustituye una factura oficial si necesitas datos fiscales completos.
    </p>
</body>
</html>
