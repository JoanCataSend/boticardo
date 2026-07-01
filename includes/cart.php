<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';
boticardoStartSession();

const BOTICARDO_CART_SESSION_KEY = 'boticardo_cart';
const BOTICARDO_CART_MAX_QUANTITY = 99;

function cartGetRaw(): array
{
    if (!isset($_SESSION[BOTICARDO_CART_SESSION_KEY]) || !is_array($_SESSION[BOTICARDO_CART_SESSION_KEY])) {
        $_SESSION[BOTICARDO_CART_SESSION_KEY] = [];
    }

    $cart = [];
    foreach ($_SESSION[BOTICARDO_CART_SESSION_KEY] as $productId => $quantity) {
        $productId = (int) $productId;
        $quantity = (int) $quantity;

        if ($productId > 0 && $quantity > 0) {
            $cart[$productId] = min($quantity, BOTICARDO_CART_MAX_QUANTITY);
        }
    }

    $_SESSION[BOTICARDO_CART_SESSION_KEY] = $cart;
    return $cart;
}

function cartSave(array $cart): void
{
    $cleanCart = [];

    foreach ($cart as $productId => $quantity) {
        $productId = (int) $productId;
        $quantity = (int) $quantity;

        if ($productId > 0 && $quantity > 0) {
            $cleanCart[$productId] = min($quantity, BOTICARDO_CART_MAX_QUANTITY);
        }
    }

    $_SESSION[BOTICARDO_CART_SESSION_KEY] = $cleanCart;
}

function cartTotalQuantity(): int
{
    return array_sum(cartGetRaw());
}

function cartProductStock(mysqli $conn, int $productId): ?array
{
    if ($productId <= 0) {
        return null;
    }

    $stmt = $conn->prepare('
        SELECT id, nombre, stock
        FROM productos
        WHERE id = ?
        LIMIT 1
    ');
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc() ?: null;
    $result->free();
    $stmt->close();

    if (!$product) {
        return null;
    }

    $product['stock'] = max(0, (int) ($product['stock'] ?? 0));
    return $product;
}

function cartAddProduct(int $productId, int $quantity = 1): int
{
    if ($productId <= 0) {
        return cartTotalQuantity();
    }

    $quantity = max(1, min($quantity, BOTICARDO_CART_MAX_QUANTITY));
    $cart = cartGetRaw();
    $cart[$productId] = min(($cart[$productId] ?? 0) + $quantity, BOTICARDO_CART_MAX_QUANTITY);
    cartSave($cart);

    return cartTotalQuantity();
}

function cartAddProductWithStock(mysqli $conn, int $productId, int $quantity = 1): array
{
    if ($productId <= 0) {
        return [
            'ok' => false,
            'message' => 'Producto no válido.',
            'cart_count' => cartTotalQuantity(),
        ];
    }

    $product = cartProductStock($conn, $productId);
    if (!$product) {
        return [
            'ok' => false,
            'message' => 'Este producto ya no está disponible.',
            'cart_count' => cartTotalQuantity(),
        ];
    }

    $stock = (int) $product['stock'];
    if ($stock <= 0) {
        return [
            'ok' => false,
            'message' => 'Este producto está agotado temporalmente.',
            'cart_count' => cartTotalQuantity(),
        ];
    }

    $quantity = max(1, min($quantity, BOTICARDO_CART_MAX_QUANTITY));
    $cart = cartGetRaw();
    $currentQuantity = (int) ($cart[$productId] ?? 0);
    $newQuantity = min($currentQuantity + $quantity, $stock, BOTICARDO_CART_MAX_QUANTITY);

    if ($newQuantity <= $currentQuantity) {
        return [
            'ok' => false,
            'message' => 'Ya tienes en el carrito todo el stock disponible de este producto.',
            'cart_count' => cartTotalQuantity(),
            'available_stock' => $stock,
        ];
    }

    $cart[$productId] = $newQuantity;
    cartSave($cart);

    $message = 'Producto añadido al carrito.';
    if ($newQuantity < $currentQuantity + $quantity) {
        $message = 'Solo quedan ' . $stock . ' unidades. Hemos ajustado la cantidad del carrito.';
    }

    return [
        'ok' => true,
        'message' => $message,
        'cart_count' => cartTotalQuantity(),
        'available_stock' => $stock,
        'product' => [
            'id' => (int) $product['id'],
            'name' => (string) $product['nombre'],
        ],
    ];
}

function cartUpdateProduct(int $productId, int $quantity): int
{
    $cart = cartGetRaw();

    if ($productId > 0) {
        if ($quantity <= 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = min($quantity, BOTICARDO_CART_MAX_QUANTITY);
        }
    }

    cartSave($cart);
    return cartTotalQuantity();
}

function cartUpdateProductWithStock(mysqli $conn, int $productId, int $quantity): array
{
    $cart = cartGetRaw();

    if ($productId <= 0) {
        return [
            'ok' => false,
            'message' => 'Producto no válido.',
            'cart_count' => cartTotalQuantity(),
        ];
    }

    if ($quantity <= 0) {
        unset($cart[$productId]);
        cartSave($cart);

        return [
            'ok' => true,
            'message' => 'Producto eliminado del carrito.',
            'cart_count' => cartTotalQuantity(),
        ];
    }

    $product = cartProductStock($conn, $productId);
    if (!$product || (int) $product['stock'] <= 0) {
        unset($cart[$productId]);
        cartSave($cart);

        return [
            'ok' => false,
            'message' => 'Este producto se ha agotado y se ha quitado del carrito.',
            'cart_count' => cartTotalQuantity(),
        ];
    }

    $stock = (int) $product['stock'];
    $newQuantity = min(max(1, $quantity), $stock, BOTICARDO_CART_MAX_QUANTITY);
    $cart[$productId] = $newQuantity;
    cartSave($cart);

    return [
        'ok' => $newQuantity === $quantity,
        'message' => $newQuantity === $quantity
            ? 'Carrito actualizado.'
            : 'Solo quedan ' . $stock . ' unidades de ' . (string) $product['nombre'] . '. Hemos ajustado la cantidad.',
        'cart_count' => cartTotalQuantity(),
        'available_stock' => $stock,
    ];
}

function cartRemoveProduct(int $productId): int
{
    $cart = cartGetRaw();
    unset($cart[$productId]);
    cartSave($cart);

    return cartTotalQuantity();
}

function cartClear(): void
{
    $_SESSION[BOTICARDO_CART_SESSION_KEY] = [];
}

function cartGetProducts(mysqli $conn): array
{
    $cart = cartGetRaw();

    if ($cart === []) {
        return [];
    }

    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    $sql = "
        SELECT p.id, p.nombre, p.precio, p.imagen, p.stock, l.nombre AS marca
        FROM productos p
        LEFT JOIN laboratorios l ON p.laboratorio_id = l.id
        WHERE p.id IN ($placeholders)
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $result = $stmt->get_result();

    $productsById = [];
    while ($product = $result->fetch_assoc()) {
        $productId = (int) $product['id'];
        $quantity = (int) ($cart[$productId] ?? 0);
        $stock = max(0, (int) ($product['stock'] ?? 0));

        if ($quantity <= 0 || $stock <= 0) {
            continue;
        }

        $quantity = min($quantity, $stock, BOTICARDO_CART_MAX_QUANTITY);
        $price = (float) $product['precio'];
        $product['stock'] = $stock;
        $product['quantity'] = $quantity;
        $product['subtotal'] = $price * $quantity;
        $productsById[$productId] = $product;
    }

    $result->free();
    $stmt->close();

    $orderedProducts = [];
    $cleanCart = [];

    foreach ($cart as $productId => $quantity) {
        if (isset($productsById[$productId])) {
            $orderedProducts[] = $productsById[$productId];
            $cleanCart[$productId] = (int) $productsById[$productId]['quantity'];
        }
    }

    if ($cleanCart !== $cart) {
        cartSave($cleanCart);
    }

    return $orderedProducts;
}

function cartValidateStock(mysqli $conn): array
{
    $before = cartGetRaw();
    $items = cartGetProducts($conn);
    $after = cartGetRaw();
    $messages = [];

    foreach ($before as $productId => $quantity) {
        if (!isset($after[$productId])) {
            $messages[] = 'Algún producto se ha agotado y se ha quitado del carrito.';
            continue;
        }

        if ((int) $after[$productId] < (int) $quantity) {
            $messages[] = 'Hemos ajustado una cantidad porque no había suficiente stock.';
        }
    }

    return [
        'ok' => $messages === [],
        'messages' => array_values(array_unique($messages)),
        'items' => $items,
        'changed' => $before !== $after,
    ];
}

function cartSummary(mysqli $conn): array
{
    $items = cartGetProducts($conn);
    $subtotal = 0.0;
    $quantity = 0;

    foreach ($items as $item) {
        $subtotal += (float) $item['subtotal'];
        $quantity += (int) $item['quantity'];
    }

    return [
        'items' => $items,
        'quantity' => $quantity,
        'subtotal' => $subtotal,
    ];
}

if (!function_exists('isUserLoggedIn')) {
    function isUserLoggedIn(): bool
    {
        return !empty($_SESSION['usuario_id'])
            || !empty($_SESSION['user_id'])
            || !empty($_SESSION['cliente_id'])
            || !empty($_SESSION['logged_in']);
    }
}
