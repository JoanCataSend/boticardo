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

function orderCleanText(string $value, int $maxLength): string
{
    $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    return mb_substr($value, 0, $maxLength, 'UTF-8');
}

function orderValidateShipping(array $data): array
{
    $shipping = [
        'nombre_envio' => orderCleanText((string) ($data['nombre_envio'] ?? ''), 160),
        'email_envio' => strtolower(orderCleanText((string) ($data['email_envio'] ?? ''), 190)),
        'telefono_envio' => orderCleanText((string) ($data['telefono_envio'] ?? ''), 30),
        'direccion_envio' => orderCleanText((string) ($data['direccion_envio'] ?? ''), 255),
        'codigo_postal' => orderCleanText((string) ($data['codigo_postal'] ?? ''), 12),
        'localidad' => orderCleanText((string) ($data['localidad'] ?? ''), 120),
        'provincia' => orderCleanText((string) ($data['provincia'] ?? ''), 120),
        'pais' => 'ES',
    ];

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
    $envio = 0.00;
    $total = round($subtotal + $envio, 2);
    $publicId = bin2hex(random_bytes(16));
    $currency = strtoupper(defined('STRIPE_CURRENCY') ? STRIPE_CURRENCY : 'eur');

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("\n            INSERT INTO pedidos (\n                public_id, usuario_id, estado, subtotal, envio, total, moneda,\n                nombre_envio, email_envio, telefono_envio, direccion_envio, codigo_postal, localidad, provincia, pais\n            ) VALUES (?, ?, 'pendiente', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ES')\n        ");

        $stmt->bind_param(
            'sidddssssssss',
            $publicId,
            $userId,
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

        $itemStmt = $conn->prepare("\n            INSERT INTO pedido_items (pedido_id, producto_id, nombre_producto, marca_producto, precio_unitario, cantidad, subtotal)\n            VALUES (?, ?, ?, ?, ?, ?, ?)\n        ");

        foreach ($items as $item) {
            $productId = (int) $item['id'];
            $name = orderCleanText((string) $item['nombre'], 190);
            $brand = orderCleanText((string) ($item['marca'] ?? ''), 190);
            $unitPrice = round((float) $item['precio'], 2);
            $quantity = max(1, (int) $item['quantity']);
            $lineSubtotal = round($unitPrice * $quantity, 2);

            $itemStmt->bind_param('iissdid', $orderId, $productId, $name, $brand, $unitPrice, $quantity, $lineSubtotal);
            $itemStmt->execute();
        }

        $itemStmt->close();
        $conn->commit();
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }

    return [
        'id' => $orderId,
        'public_id' => $publicId,
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
    $stmt = $conn->prepare('UPDATE pedidos SET estado = "fallido" WHERE id = ? AND estado = "pendiente"');
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $stmt->close();
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

function orderCancelPendingForUser(mysqli $conn, string $publicId, int $userId): void
{
    $stmt = $conn->prepare('UPDATE pedidos SET estado = "cancelado" WHERE public_id = ? AND usuario_id = ? AND estado = "pendiente"');
    $stmt->bind_param('si', $publicId, $userId);
    $stmt->execute();
    $stmt->close();
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
            $conn->commit();
            try {
                orderNotifyAdminIfNeeded($conn, $orderId);
            } catch (Throwable $notifyError) {
                error_log('Boticardo - No se pudo notificar el pedido pagado #' . $orderId . ': ' . $notifyError->getMessage());
            }
            return true;
        }

        $stmt = $conn->prepare("\n            UPDATE pedidos\n            SET estado = 'pagado',\n                stripe_checkout_session_id = ?,\n                stripe_payment_intent = ?,\n                stripe_payment_method_types = ?,\n                stripe_event_id = ?,\n                paid_at = NOW()\n            WHERE id = ?\n        ");
        $orderId = (int) $order['id'];
        $stmt->bind_param('ssssi', $sessionId, $paymentIntent, $methodTypes, $eventId, $orderId);
        $stmt->execute();
        $stmt->close();

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
