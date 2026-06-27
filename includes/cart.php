<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const BOTICARDO_CART_SESSION_KEY = 'boticardo_cart';

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
            $cart[$productId] = min($quantity, 99);
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
            $cleanCart[$productId] = min($quantity, 99);
        }
    }

    $_SESSION[BOTICARDO_CART_SESSION_KEY] = $cleanCart;
}

function cartTotalQuantity(): int
{
    return array_sum(cartGetRaw());
}

function cartAddProduct(int $productId, int $quantity = 1): int
{
    if ($productId <= 0) {
        return cartTotalQuantity();
    }

    $quantity = max(1, min($quantity, 99));
    $cart = cartGetRaw();
    $cart[$productId] = min(($cart[$productId] ?? 0) + $quantity, 99);
    cartSave($cart);

    return cartTotalQuantity();
}

function cartUpdateProduct(int $productId, int $quantity): int
{
    $cart = cartGetRaw();

    if ($productId > 0) {
        if ($quantity <= 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = min($quantity, 99);
        }
    }

    cartSave($cart);
    return cartTotalQuantity();
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
        SELECT p.id, p.nombre, p.precio, p.imagen, l.nombre AS marca
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

        if ($quantity <= 0) {
            continue;
        }

        $price = (float) $product['precio'];
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
            $cleanCart[$productId] = $quantity;
        }
    }

    if (count($cleanCart) !== count($cart)) {
        cartSave($cleanCart);
    }

    return $orderedProducts;
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
