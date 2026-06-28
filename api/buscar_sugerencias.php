<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$query = trim((string) ($_GET['q'] ?? ''));

if ($query === '' || strlen(normalizeSearchText($query)) < 2) {
    echo json_encode([
        'ok' => true,
        'query' => $query,
        'items' => [],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $productos = searchProductSuggestions($conn, $query, 6);
    $items = [];

    foreach ($productos as $producto) {
        $nombre = (string) ($producto['nombre'] ?? 'Producto de farmacia');
        $items[] = [
            'id' => (int) ($producto['id'] ?? 0),
            'nombre' => $nombre,
            'marca' => (string) ($producto['marca'] ?: 'Boticardo'),
            'precio' => number_format((float) ($producto['precio'] ?? 0), 2, ',', '.') . ' €',
            'imagen' => 'img/productos/' . basename((string) ($producto['imagen'] ?? 'placeholder.jpg')),
            'url' => productoUrl((int) ($producto['id'] ?? 0)),
        ];
    }

    echo json_encode([
        'ok' => true,
        'query' => $query,
        'items' => $items,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $error) {
    error_log('Boticardo - Error en sugerencias de búsqueda: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'query' => $query,
        'items' => [],
        'message' => 'No se pudieron cargar las sugerencias.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} finally {
    $conn->close();
}
