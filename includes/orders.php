<?php
declare(strict_types=1);

require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/mailer.php';

function paymentMoneyToCents(float $amount): int
{
    return (int) round($amount * 100);
}

function paymentCentsToMoney(int $cents): float
{
    return round($cents / 100, 2);
}

function orderEnsureTables(mysqli $conn): void
{
    $conn->query("\n        CREATE TABLE IF NOT EXISTS pedidos (\n            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,\n            public_id CHAR(32) NOT NULL,\n            usuario_id INT UNSIGNED NOT NULL,\n            estado ENUM('pendiente','pagado','preparando','enviado','completado','cancelado','fallido') NOT NULL DEFAULT 'pendiente',\n            subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,\n            envio DECIMAL(10,2) NOT NULL DEFAULT 0.00,\n            total DECIMAL(10,2) NOT NULL DEFAULT 0.00,\n            moneda CHAR(3) NOT NULL DEFAULT 'EUR',\n            nombre_envio VARCHAR(160) NOT NULL,\n            email_envio VARCHAR(190) NOT NULL,\n            telefono_envio VARCHAR(30) NOT NULL,\n            direccion_envio VARCHAR(255) NOT NULL,\n            codigo_postal VARCHAR(12) NOT NULL,\n            localidad VARCHAR(120) NOT NULL,\n            provincia VARCHAR(120) NOT NULL,\n            pais CHAR(2) NOT NULL DEFAULT 'ES',\n            stripe_checkout_session_id VARCHAR(255) NULL,\n            stripe_payment_intent VARCHAR(255) NULL,\n            stripe_payment_method_types VARCHAR(255) NULL,\n            stripe_event_id VARCHAR(255) NULL,\n            paid_at DATETIME NULL,\n            admin_notified_at DATETIME NULL,\n            admin_notes TEXT NULL,\n            tracking_code VARCHAR(120) NULL,\n            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n            UNIQUE KEY uq_pedidos_public_id (public_id),\n            UNIQUE KEY uq_pedidos_stripe_session (stripe_checkout_session_id),\n            KEY idx_pedidos_usuario (usuario_id),\n            KEY idx_pedidos_estado (estado)\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    $conn->query("\n        CREATE TABLE IF NOT EXISTS pedido_items (\n            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,\n            pedido_id INT UNSIGNED NOT NULL,\n            producto_id INT UNSIGNED NOT NULL,\n            nombre_producto VARCHAR(190) NOT NULL,\n            marca_producto VARCHAR(190) NULL,\n            precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,\n            cantidad INT UNSIGNED NOT NULL DEFAULT 1,\n            subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,\n            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n            KEY idx_pedido_items_pedido (pedido_id),\n            CONSTRAINT fk_pedido_items_pedido\n                FOREIGN KEY (pedido_id) REFERENCES pedidos(id)\n                ON DELETE CASCADE\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    $conn->query("\n        CREATE TABLE IF NOT EXISTS stripe_webhook_events (\n            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,\n            event_id VARCHAR(255) NOT NULL,\n            event_type VARCHAR(120) NOT NULL,\n            processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n            UNIQUE KEY uq_stripe_event_id (event_id)\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    orderEnsurePedidoColumns($conn);
}


function orderColumnExists(mysqli $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare('
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ');
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $result->free();
    $stmt->close();

    return ((int) ($row['total'] ?? 0)) > 0;
}

function orderTableExists(mysqli $conn, string $table): bool
{
    $stmt = $conn->prepare('
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
    ');
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $result->free();
    $stmt->close();

    return ((int) ($row['total'] ?? 0)) > 0;
}

function orderClearPersistentCartForUser(mysqli $conn, int $userId): void
{
    if ($userId <= 0 || !orderTableExists($conn, 'usuario_carrito')) {
        return;
    }

    $stmt = $conn->prepare('DELETE FROM usuario_carrito WHERE usuario_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
}


function orderEnsurePedidoColumns(mysqli $conn): void
{
    $conn->query("ALTER TABLE pedidos MODIFY estado ENUM('pendiente','pagado','preparando','enviado','completado','cancelado','fallido') NOT NULL DEFAULT 'pendiente'");

    if (!orderColumnExists($conn, 'pedidos', 'admin_notified_at')) {
        $conn->query('ALTER TABLE pedidos ADD COLUMN admin_notified_at DATETIME NULL AFTER paid_at');
    }

    if (!orderColumnExists($conn, 'pedidos', 'admin_notes')) {
        $conn->query('ALTER TABLE pedidos ADD COLUMN admin_notes TEXT NULL AFTER admin_notified_at');
    }

    if (!orderColumnExists($conn, 'pedidos', 'tracking_code')) {
        $conn->query('ALTER TABLE pedidos ADD COLUMN tracking_code VARCHAR(120) NULL AFTER admin_notes');
    }

    if (!orderColumnExists($conn, 'pedidos', 'stock_reservado')) {
        $conn->query('ALTER TABLE pedidos ADD COLUMN stock_reservado TINYINT(1) NOT NULL DEFAULT 0 AFTER tracking_code');
    }

    if (!orderColumnExists($conn, 'pedidos', 'metodo_entrega')) {
        $conn->query("ALTER TABLE pedidos ADD COLUMN metodo_entrega ENUM('domicilio','recogida') NOT NULL DEFAULT 'domicilio' AFTER estado");
    }
}

function orderAllowedStatuses(): array
{
    return ['pendiente', 'pagado', 'preparando', 'enviado', 'completado', 'cancelado', 'fallido'];
}

function orderStatusLabel(string $status): string
{
    return match ($status) {
        'pendiente' => 'Pendiente de pago',
        'pagado' => 'Pagado · pendiente de preparar',
        'preparando' => 'Preparando',
        'enviado' => 'Enviado',
        'completado' => 'Completado',
        'cancelado' => 'Cancelado',
        'fallido' => 'Pago fallido',
        default => ucfirst($status),
    };
}

function orderGetById(mysqli $conn, int $orderId): ?array
{
    orderEnsureTables($conn);

    $stmt = $conn->prepare('
        SELECT p.*, u.nombre AS usuario_nombre, u.email AS usuario_email
        FROM pedidos p
        LEFT JOIN usuarios u ON u.id = p.usuario_id
        WHERE p.id = ?
        LIMIT 1
    ');
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc() ?: null;
    $result->free();
    $stmt->close();

    return $order;
}

function orderGetByStripeSessionId(mysqli $conn, string $sessionId): ?array
{
    orderEnsureTables($conn);

    $stmt = $conn->prepare('SELECT * FROM pedidos WHERE stripe_checkout_session_id = ? LIMIT 1');
    $stmt->bind_param('s', $sessionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc() ?: null;
    $result->free();
    $stmt->close();

    return $order;
}

function orderNotifyAdminIfNeeded(mysqli $conn, int $orderId): bool
{
    orderEnsureTables($conn);

    $stmt = $conn->prepare('SELECT * FROM pedidos WHERE id = ? AND estado IN ("pagado", "preparando", "enviado", "completado") LIMIT 1');
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc() ?: null;
    $result->free();
    $stmt->close();

    if (!$order || !empty($order['admin_notified_at'])) {
        return false;
    }

    $items = orderGetItems($conn, $orderId);
    $sent = mailerSendNewOrderAdmin($order, $items);

    if ($sent) {
        $stmt = $conn->prepare('UPDATE pedidos SET admin_notified_at = NOW() WHERE id = ? AND admin_notified_at IS NULL');
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $stmt->close();
    }

    return $sent;
}

function orderDeliveryMethods(): array
{
    return [
        'domicilio' => 'Envío a domicilio',
        'recogida' => 'Recoger en farmacia',
    ];
}

function orderNormalizeDeliveryMethod(?string $method): string
{
    return $method === 'recogida' ? 'recogida' : 'domicilio';
}

function orderDeliveryLabel(?string $method): string
{
    $method = orderNormalizeDeliveryMethod($method);
    $methods = orderDeliveryMethods();

    return $methods[$method] ?? $methods['domicilio'];
}

function orderCalculateShippingCost(float $subtotal, ?string $deliveryMethod = 'domicilio'): float
{
    $deliveryMethod = orderNormalizeDeliveryMethod($deliveryMethod);
    if ($deliveryMethod === 'recogida') {
        return 0.00;
    }

    $standardCost = defined('ORDER_HOME_SHIPPING_COST') ? (float) ORDER_HOME_SHIPPING_COST : 3.95;
    $freeFrom = defined('ORDER_FREE_SHIPPING_FROM') ? (float) ORDER_FREE_SHIPPING_FROM : 39.00;

    if ($freeFrom > 0 && $subtotal >= $freeFrom) {
        return 0.00;
    }

    return round(max(0.0, $standardCost), 2);
}

function orderDeliverySummaryText(array $order): string
{
    $method = orderNormalizeDeliveryMethod((string) ($order['metodo_entrega'] ?? 'domicilio'));

    if ($method === 'recogida') {
        return 'Recogida en farmacia. Avisaremos al cliente cuando el pedido esté listo.';
    }

    $parts = array_filter([
        (string) ($order['direccion_envio'] ?? ''),
        trim((string) ($order['codigo_postal'] ?? '') . ' ' . (string) ($order['localidad'] ?? '')),
        (string) ($order['provincia'] ?? ''),
    ]);

    return implode(', ', $parts);
}

function orderCleanText(string $value, int $maxLength): string
{
    $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    return mb_substr($value, 0, $maxLength, 'UTF-8');
}

function orderValidateShipping(array $data): array
{
    $deliveryMethod = orderNormalizeDeliveryMethod((string) ($data['metodo_entrega'] ?? 'domicilio'));

    $shipping = [
        'metodo_entrega' => $deliveryMethod,
        'nombre_envio' => orderCleanText((string) ($data['nombre_envio'] ?? ''), 160),
        'email_envio' => strtolower(orderCleanText((string) ($data['email_envio'] ?? ''), 190)),
        'telefono_envio' => orderCleanText((string) ($data['telefono_envio'] ?? ''), 30),
        'direccion_envio' => orderCleanText((string) ($data['direccion_envio'] ?? ''), 255),
        'codigo_postal' => orderCleanText((string) ($data['codigo_postal'] ?? ''), 12),
        'localidad' => orderCleanText((string) ($data['localidad'] ?? ''), 120),
        'provincia' => orderCleanText((string) ($data['provincia'] ?? ''), 120),
        'pais' => 'ES',
    ];

    if ($deliveryMethod === 'recogida') {
        $shipping['direccion_envio'] = 'Recogida en farmacia';
        $shipping['codigo_postal'] = '';
        $shipping['localidad'] = '';
        $shipping['provincia'] = '';
    }

    $errors = [];

    if (mb_strlen($shipping['nombre_envio'], 'UTF-8') < 2) {
        $errors[] = 'Escribe el nombre de la persona que recibirá el pedido.';
    }

    if (!filter_var($shipping['email_envio'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Escribe un email válido.';
    }

    if (!preg_match('/^[0-9 +()\-]{6,30}$/', $shipping['telefono_envio'])) {
        $errors[] = 'Escribe un teléfono válido.';
    }

    if ($deliveryMethod === 'domicilio') {
        if (mb_strlen($shipping['direccion_envio'], 'UTF-8') < 5) {
            $errors[] = 'Escribe una dirección completa.';
        }

        if (!preg_match('/^[0-9]{5}$/', $shipping['codigo_postal'])) {
            $errors[] = 'El código postal debe tener 5 números.';
        }

        if (mb_strlen($shipping['localidad'], 'UTF-8') < 2) {
            $errors[] = 'Escribe la localidad.';
        }

        if (mb_strlen($shipping['provincia'], 'UTF-8') < 2) {
            $errors[] = 'Escribe la provincia.';
        }
    }

    return [$shipping, $errors];
}

function orderCreatePendingFromCart(mysqli $conn, int $userId, array $shipping, array $cartSummary): array
{
    orderEnsureTables($conn);

    $items = $cartSummary['items'] ?? [];
    if ($userId <= 0 || $items === []) {
        throw new RuntimeException('No se puede crear un pedido sin usuario o sin productos.');
    }

    $subtotal = round((float) ($cartSummary['subtotal'] ?? 0), 2);
    $deliveryMethod = orderNormalizeDeliveryMethod((string) ($shipping['metodo_entrega'] ?? 'domicilio'));
    $envio = orderCalculateShippingCost($subtotal, $deliveryMethod);
    $total = round($subtotal + $envio, 2);
    $publicId = bin2hex(random_bytes(16));
    $currency = strtoupper(defined('STRIPE_CURRENCY') ? STRIPE_CURRENCY : 'eur');

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("
            INSERT INTO pedidos (
                public_id, usuario_id, estado, metodo_entrega, subtotal, envio, total, moneda,
                nombre_envio, email_envio, telefono_envio, direccion_envio, codigo_postal, localidad, provincia, pais, stock_reservado
            ) VALUES (?, ?, 'pendiente', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ES', 1)
        ");

        $stmt->bind_param(
            'sisdddssssssss',
            $publicId,
            $userId,
            $deliveryMethod,
            $subtotal,
            $envio,
            $total,
            $currency,
            $shipping['nombre_envio'],
            $shipping['email_envio'],
            $shipping['telefono_envio'],
            $shipping['direccion_envio'],
            $shipping['codigo_postal'],
            $shipping['localidad'],
            $shipping['provincia']
        );
        $stmt->execute();
        $orderId = (int) $stmt->insert_id;
        $stmt->close();

        $stockStmt = $conn->prepare('SELECT stock FROM productos WHERE id = ? LIMIT 1 FOR UPDATE');
        $reserveStmt = $conn->prepare('UPDATE productos SET stock = stock - ? WHERE id = ? AND stock >= ?');
        $itemStmt = $conn->prepare("\n            INSERT INTO pedido_items (pedido_id, producto_id, nombre_producto, marca_producto, precio_unitario, cantidad, subtotal)\n            VALUES (?, ?, ?, ?, ?, ?, ?)\n        ");

        foreach ($items as $item) {
            $productId = (int) $item['id'];
            $name = orderCleanText((string) $item['nombre'], 190);
            $brand = orderCleanText((string) ($item['marca'] ?? ''), 190);
            $unitPrice = round((float) $item['precio'], 2);
            $quantity = max(1, (int) $item['quantity']);
            $lineSubtotal = round($unitPrice * $quantity, 2);

            $stockStmt->bind_param('i', $productId);
            $stockStmt->execute();
            $stockResult = $stockStmt->get_result();
            $stockRow = $stockResult->fetch_assoc();
            $stockResult->free();

            $availableStock = max(0, (int) ($stockRow['stock'] ?? 0));
            if (!$stockRow || $availableStock < $quantity) {
                throw new RuntimeException('No hay stock suficiente de ' . $name . '.');
            }

            $reserveStmt->bind_param('iii', $quantity, $productId, $quantity);
            $reserveStmt->execute();
            if ($reserveStmt->affected_rows !== 1) {
                throw new RuntimeException('No se pudo reservar stock de ' . $name . '.');
            }

            $itemStmt->bind_param('iissdid', $orderId, $productId, $name, $brand, $unitPrice, $quantity, $lineSubtotal);
            $itemStmt->execute();
        }

        $stockStmt->close();
        $reserveStmt->close();
        $itemStmt->close();
        $conn->commit();
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }

    return [
        'id' => $orderId,
        'public_id' => $publicId,
        'metodo_entrega' => $deliveryMethod,
        'subtotal' => $subtotal,
        'envio' => $envio,
        'total' => $total,
        'moneda' => $currency,
        'items' => $items,
    ];
}

function orderUpdateStripeSession(mysqli $conn, int $orderId, string $sessionId): void
{
    $stmt = $conn->prepare('UPDATE pedidos SET stripe_checkout_session_id = ? WHERE id = ? AND estado = "pendiente"');
    $stmt->bind_param('si', $sessionId, $orderId);
    $stmt->execute();
    $stmt->close();
}

function orderMarkFailed(mysqli $conn, int $orderId): void
{
    if ($orderId <= 0) {
        return;
    }

    orderEnsureTables($conn);
    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare('SELECT id, estado, stock_reservado FROM pedidos WHERE id = ? LIMIT 1 FOR UPDATE');
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc() ?: null;
        $result->free();
        $stmt->close();

        if ($order && (string) $order['estado'] === 'pendiente') {
            if ((int) ($order['stock_reservado'] ?? 0) === 1) {
                orderReleaseReservedStockForOrder($conn, $orderId);
            }

            $stmt = $conn->prepare('UPDATE pedidos SET estado = "fallido", stock_reservado = 0 WHERE id = ? AND estado = "pendiente"');
            $stmt->bind_param('i', $orderId);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }
}

function orderGetByPublicIdForUser(mysqli $conn, string $publicId, int $userId): ?array
{
    orderEnsureTables($conn);

    $stmt = $conn->prepare('SELECT * FROM pedidos WHERE public_id = ? AND usuario_id = ? LIMIT 1');
    $stmt->bind_param('si', $publicId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc() ?: null;
    $result->free();
    $stmt->close();

    return $order;
}

function orderGetItems(mysqli $conn, int $orderId): array
{
    $stmt = $conn->prepare('SELECT * FROM pedido_items WHERE pedido_id = ? ORDER BY id ASC');
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];

    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    $result->free();
    $stmt->close();

    return $items;
}

function orderReleaseReservedStockForOrder(mysqli $conn, int $orderId): void
{
    if ($orderId <= 0) {
        return;
    }

    $items = orderGetItems($conn, $orderId);
    if ($items === []) {
        return;
    }

    $stmt = $conn->prepare('UPDATE productos SET stock = stock + ? WHERE id = ?');

    foreach ($items as $item) {
        $quantity = max(0, (int) ($item['cantidad'] ?? 0));
        $productId = (int) ($item['producto_id'] ?? 0);

        if ($quantity <= 0 || $productId <= 0) {
            continue;
        }

        $stmt->bind_param('ii', $quantity, $productId);
        $stmt->execute();
    }

    $stmt->close();
}

function orderIncreaseSalesForOrder(mysqli $conn, int $orderId): void
{
    if ($orderId <= 0) {
        return;
    }

    $items = orderGetItems($conn, $orderId);
    if ($items === []) {
        return;
    }

    $stmt = $conn->prepare('UPDATE productos SET ventas_totales = ventas_totales + ? WHERE id = ?');

    foreach ($items as $item) {
        $quantity = max(0, (int) ($item['cantidad'] ?? 0));
        $productId = (int) ($item['producto_id'] ?? 0);

        if ($quantity <= 0 || $productId <= 0) {
            continue;
        }

        $stmt->bind_param('ii', $quantity, $productId);
        $stmt->execute();
    }

    $stmt->close();
}

function orderCancelPendingForUser(mysqli $conn, string $publicId, int $userId): void
{
    if ($publicId === '' || $userId <= 0) {
        return;
    }

    orderEnsureTables($conn);
    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare('SELECT id, estado, stock_reservado FROM pedidos WHERE public_id = ? AND usuario_id = ? LIMIT 1 FOR UPDATE');
        $stmt->bind_param('si', $publicId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc() ?: null;
        $result->free();
        $stmt->close();

        if ($order && (string) $order['estado'] === 'pendiente') {
            $orderId = (int) $order['id'];

            if ((int) ($order['stock_reservado'] ?? 0) === 1) {
                orderReleaseReservedStockForOrder($conn, $orderId);
            }

            $stmt = $conn->prepare('UPDATE pedidos SET estado = "cancelado", stock_reservado = 0 WHERE id = ? AND estado = "pendiente"');
            $stmt->bind_param('i', $orderId);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }
}

function orderRecordStripeEvent(mysqli $conn, string $eventId, string $eventType): bool
{
    orderEnsureTables($conn);

    $stmt = $conn->prepare('INSERT IGNORE INTO stripe_webhook_events (event_id, event_type) VALUES (?, ?)');
    $stmt->bind_param('ss', $eventId, $eventType);
    $stmt->execute();
    $inserted = $stmt->affected_rows === 1;
    $stmt->close();

    return $inserted;
}

function orderMarkPaidFromStripeSession(mysqli $conn, array $session, string $eventId): bool
{
    $sessionId = (string) ($session['id'] ?? '');
    $paymentStatus = (string) ($session['payment_status'] ?? '');
    $amountTotal = (int) ($session['amount_total'] ?? -1);
    $currency = strtolower((string) ($session['currency'] ?? ''));
    $publicId = (string) ($session['metadata']['public_id'] ?? '');
    $paymentIntent = is_string($session['payment_intent'] ?? null) ? (string) $session['payment_intent'] : '';
    $methodTypes = isset($session['payment_method_types']) && is_array($session['payment_method_types'])
        ? implode(',', array_map('strval', $session['payment_method_types']))
        : '';

    if ($sessionId === '' || $paymentStatus !== 'paid' || $amountTotal <= 0 || $currency !== strtolower((string) STRIPE_CURRENCY)) {
        return false;
    }

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare('SELECT * FROM pedidos WHERE public_id = ? OR stripe_checkout_session_id = ? LIMIT 1 FOR UPDATE');
        $stmt->bind_param('ss', $publicId, $sessionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc() ?: null;
        $result->free();
        $stmt->close();

        if (!$order) {
            $conn->rollback();
            return false;
        }

        $expectedCents = paymentMoneyToCents((float) $order['total']);
        if ($expectedCents !== $amountTotal) {
            error_log('Boticardo - Stripe amount mismatch. Pedido ' . $order['id'] . ' esperado ' . $expectedCents . ' recibido ' . $amountTotal);
            $conn->rollback();
            return false;
        }

        if ((string) $order['estado'] === 'pagado') {
            $orderId = (int) $order['id'];
            orderClearPersistentCartForUser($conn, (int) ($order['usuario_id'] ?? 0));
            $conn->commit();
            try {
                orderNotifyAdminIfNeeded($conn, $orderId);
            } catch (Throwable $notifyError) {
                error_log('Boticardo - No se pudo notificar el pedido pagado #' . $orderId . ': ' . $notifyError->getMessage());
            }
            return true;
        }

        if ((string) $order['estado'] !== 'pendiente') {
            $conn->commit();
            return false;
        }

        $stmt = $conn->prepare("\n            UPDATE pedidos\n            SET estado = 'pagado',\n                stripe_checkout_session_id = ?,\n                stripe_payment_intent = ?,\n                stripe_payment_method_types = ?,\n                stripe_event_id = ?,\n                paid_at = NOW(),\n                stock_reservado = 0\n            WHERE id = ?\n        ");
        $orderId = (int) $order['id'];
        $stmt->bind_param('ssssi', $sessionId, $paymentIntent, $methodTypes, $eventId, $orderId);
        $stmt->execute();
        $stmt->close();

        orderIncreaseSalesForOrder($conn, $orderId);
        orderClearPersistentCartForUser($conn, (int) ($order['usuario_id'] ?? 0));

        $conn->commit();

        try {
            orderNotifyAdminIfNeeded($conn, $orderId);
        } catch (Throwable $notifyError) {
            error_log('Boticardo - No se pudo notificar el pedido pagado #' . $orderId . ': ' . $notifyError->getMessage());
        }

        return true;
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }
}
