<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/favorites.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Método no permitido.']);
    exit;
}

$csrfToken = (string) ($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));

if (!authValidateCsrf($csrfToken)) {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'message' => 'Sesión caducada. Recarga la página e inténtalo de nuevo.',
        'favorites_count' => favoritesCount(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$action = isset($_POST['action']) ? trim((string) $_POST['action']) : 'toggle';
$productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;

if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Producto no válido.']);
    exit;
}

$producto = getProductoById($conn, $productId);

if (!$producto) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Producto no encontrado.']);
    exit;
}

switch ($action) {
    case 'add':
        favoritesAdd($productId);
        $isFavorite = true;
        break;

    case 'remove':
        favoritesRemove($productId);
        $isFavorite = false;
        break;

    case 'toggle':
    default:
        $isFavorite = favoritesToggle($productId);
        break;
}

$conn->close();

echo json_encode([
    'ok' => true,
    'product_id' => $productId,
    'is_favorite' => $isFavorite,
    'favorites_count' => favoritesCount(),
]);
