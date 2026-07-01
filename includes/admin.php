<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/orders.php';

function adminRequire(): void
{
    authRequireAdmin('../login.php');
}

function adminStatusOptions(): array
{
    $statuses = [];
    foreach (orderAllowedStatuses() as $status) {
        $statuses[$status] = orderStatusLabel($status);
    }
    return $statuses;
}

function adminStatusClass(string $status): string
{
    return 'admin-status admin-status--' . preg_replace('/[^a-z0-9_-]/', '', strtolower($status));
}

function adminFormatMoney(float $amount, string $currency = 'EUR'): string
{
    return number_format($amount, 2, ',', '.') . ' ' . strtoupper($currency);
}

function adminDashboardStats(mysqli $conn): array
{
    orderEnsureTables($conn);

    $stats = [
        'total' => 0,
        'pendiente' => 0,
        'pagado' => 0,
        'preparando' => 0,
        'enviado' => 0,
        'completado' => 0,
        'cancelado' => 0,
        'fallido' => 0,
        'ventas_confirmadas' => 0.0,
    ];

    $result = $conn->query('SELECT estado, COUNT(*) AS total FROM pedidos GROUP BY estado');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $status = (string) $row['estado'];
            $count = (int) $row['total'];
            $stats[$status] = $count;
            $stats['total'] += $count;
        }
        $result->free();
    }

    $result = $conn->query("SELECT COALESCE(SUM(total), 0) AS total FROM pedidos WHERE estado IN ('pagado','preparando','enviado','completado')");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['ventas_confirmadas'] = (float) ($row['total'] ?? 0);
        $result->free();
    }

    return $stats;
}

function adminFetchOrders(mysqli $conn, ?string $status = null, int $limit = 100): array
{
    orderEnsureTables($conn);
    $limit = max(1, min(300, $limit));
    $orders = [];

    if ($status !== null && in_array($status, orderAllowedStatuses(), true)) {
        $stmt = $conn->prepare("\n            SELECT p.*, u.nombre AS usuario_nombre, u.email AS usuario_email\n            FROM pedidos p\n            LEFT JOIN usuarios u ON u.id = p.usuario_id\n            WHERE p.estado = ?\n            ORDER BY p.created_at DESC\n            LIMIT $limit\n        ");
        $stmt->bind_param('s', $status);
    } else {
        $stmt = $conn->prepare("\n            SELECT p.*, u.nombre AS usuario_nombre, u.email AS usuario_email\n            FROM pedidos p\n            LEFT JOIN usuarios u ON u.id = p.usuario_id\n            ORDER BY p.created_at DESC\n            LIMIT $limit\n        ");
    }

    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    $result->free();
    $stmt->close();

    return $orders;
}

function adminFetchOrder(mysqli $conn, int $orderId): ?array
{
    return orderGetById($conn, $orderId);
}

function adminUpdateOrder(mysqli $conn, int $orderId, string $status, string $notes, string $trackingCode): bool
{
    orderEnsureTables($conn);

    if (!in_array($status, orderAllowedStatuses(), true)) {
        throw new InvalidArgumentException('Estado de pedido no válido.');
    }

    $notes = trim($notes);
    $trackingCode = trim($trackingCode);

    $stmt = $conn->prepare("
        UPDATE pedidos
        SET estado = ?, admin_notes = ?, tracking_code = ?
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param('sssi', $status, $notes, $trackingCode, $orderId);
    $stmt->execute();
    $ok = $stmt->affected_rows >= 0;
    $stmt->close();

    return $ok;
}

function adminFetchClients(mysqli $conn, int $limit = 200): array
{
    authEnsureUsuariosTable($conn);
    orderEnsureTables($conn);
    $limit = max(1, min(500, $limit));

    $clients = [];
    $result = $conn->query("\n        SELECT\n            u.id, u.nombre, u.email, u.rol, u.ultimo_login, u.created_at,\n            COUNT(p.id) AS pedidos_total,\n            COALESCE(SUM(CASE WHEN p.estado IN ('pagado','preparando','enviado','completado') THEN p.total ELSE 0 END), 0) AS compras_total,\n            MAX(p.created_at) AS ultimo_pedido\n        FROM usuarios u\n        LEFT JOIN pedidos p ON p.usuario_id = u.id\n        GROUP BY u.id, u.nombre, u.email, u.rol, u.ultimo_login, u.created_at\n        ORDER BY u.created_at DESC\n        LIMIT $limit\n    ");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $clients[] = $row;
        }
        $result->free();
    }

    return $clients;
}

function adminFetchClient(mysqli $conn, int $userId): ?array
{
    authEnsureUsuariosTable($conn);

    $stmt = $conn->prepare('SELECT * FROM usuarios WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $client = $result->fetch_assoc() ?: null;
    $result->free();
    $stmt->close();

    return $client;
}

function adminFetchOrdersForClient(mysqli $conn, int $userId): array
{
    orderEnsureTables($conn);
    $orders = [];

    $stmt = $conn->prepare('SELECT * FROM pedidos WHERE usuario_id = ? ORDER BY created_at DESC LIMIT 100');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    $result->free();
    $stmt->close();

    return $orders;
}

function adminRenderHeader(string $title, string $active = 'dashboard'): void
{
    $currentUser = authCurrentUser();
    $nav = [
        'dashboard' => ['Panel', 'index.php'],
        'pedidos' => ['Pedidos', 'pedidos.php'],
        'clientes' => ['Clientes', 'clientes.php'],
        'productos' => ['Productos', 'productos.php'],
        'categorias' => ['Categorías', 'categorias.php'],
        'laboratorios' => ['Laboratorios', 'laboratorios.php'],
        'cupones' => ['Cupones', 'cupones.php'],
        'banners' => ['Banners', 'banners.php'],
        'legales' => ['Legales', 'paginas-legales.php'],
    ];
    ?>
<!DOCTYPE html>
<html lang="es-ES">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> | Admin Boticardo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css?v=9">
</head>
<body class="admin-body">
    <header class="admin-topbar">
        <a href="index.php" class="admin-brand">Boticardo <span>Admin</span></a>
        <nav class="admin-nav" aria-label="Administración">
            <?php foreach ($nav as $key => [$label, $url]): ?>
                <a href="<?= e($url) ?>" class="admin-nav__link <?= $active === $key ? 'admin-nav__link--active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="admin-user">
            <span><?= e($currentUser['nombre'] ?? 'Admin') ?></span>
            <a href="../index.php">Ver tienda</a>
            <a href="../logout.php">Salir</a>
        </div>
    </header>
    <main class="admin-main">
    <?php
}

function adminRenderFooter(): void
{
    ?>
    </main>
</body>
</html>
    <?php
}
