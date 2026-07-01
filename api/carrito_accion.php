<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

function cartJsonResponse(array $data, int $statusCode = 200): never
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cartJsonResponse([
        'ok' => false,
        'message' => 'Método no permitido.',
        'cart_count' => cartTotalQuantity(),
    ], 405);
}

$csrfToken = (string) ($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));

if (!authValidateCsrf($csrfToken)) {
    cartJsonResponse([
        'ok' => false,
        'message' => 'Sesión caducada. Recarga la página e inténtalo de nuevo.',
        'cart_count' => cartTotalQuantity(),
    ], 403);
}

$action = (string) ($_POST['action'] ?? '');
$productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;

try {
    if ($action === 'add') {
        if ($productId <= 0) {
            cartJsonResponse([
                'ok' => false,
                'message' => 'Producto no válido.',
                'cart_count' => cartTotalQuantity(),
            ], 400);
        }

        $result = cartAddProductWithStock($conn, $productId, max(1, $quantity));

        cartJsonResponse($result, $result['ok'] ? 200 : 409);
    }

    if ($action === 'update') {
        if ($productId <= 0) {
            cartJsonResponse([
                'ok' => false,
                'message' => 'Producto no válido.',
                'cart_count' => cartTotalQuantity(),
            ], 400);
        }

        $result = cartUpdateProductWithStock($conn, $productId, max(0, $quantity));

        cartJsonResponse($result, $result['ok'] ? 200 : 409);
    }

    if ($action === 'remove') {
        if ($productId <= 0) {
            cartJsonResponse([
                'ok' => false,
                'message' => 'Producto no válido.',
                'cart_count' => cartTotalQuantity(),
            ], 400);
        }

        $cartCount = cartRemoveProduct($productId);

        cartJsonResponse([
            'ok' => true,
            'message' => 'Producto eliminado del carrito.',
            'cart_count' => $cartCount,
        ]);
    }

    if ($action === 'clear') {
        cartClear();

        cartJsonResponse([
            'ok' => true,
            'message' => 'Carrito vaciado.',
            'cart_count' => 0,
        ]);
    }

    cartJsonResponse([
        'ok' => false,
        'message' => 'Acción no válida.',
        'cart_count' => cartTotalQuantity(),
    ], 400);
} catch (Throwable $error) {
    error_log('Boticardo - Error en carrito_accion.php: ' . $error->getMessage());
    cartJsonResponse([
        'ok' => false,
        'message' => 'No se pudo actualizar el carrito.',
        'cart_count' => cartTotalQuantity(),
    ], 500);
}
