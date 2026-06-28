<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function favoritesNormalizeId(int $productId): int
{
    return max(0, $productId);
}

function favoritesIds(): array
{
    if (!isset($_SESSION['favorites']) || !is_array($_SESSION['favorites'])) {
        $_SESSION['favorites'] = [];
    }

    $ids = array_map('intval', $_SESSION['favorites']);
    $ids = array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));
    $_SESSION['favorites'] = $ids;

    return $ids;
}

function favoritesCount(): int
{
    return count(favoritesIds());
}

function favoritesHas(int $productId): bool
{
    $productId = favoritesNormalizeId($productId);

    if ($productId <= 0) {
        return false;
    }

    return in_array($productId, favoritesIds(), true);
}

function favoritesAdd(int $productId): bool
{
    $productId = favoritesNormalizeId($productId);

    if ($productId <= 0) {
        return false;
    }

    $ids = favoritesIds();

    if (!in_array($productId, $ids, true)) {
        $ids[] = $productId;
    }

    $_SESSION['favorites'] = array_values(array_unique($ids));

    return true;
}

function favoritesRemove(int $productId): bool
{
    $productId = favoritesNormalizeId($productId);

    if ($productId <= 0) {
        return false;
    }

    $_SESSION['favorites'] = array_values(array_filter(favoritesIds(), static fn (int $id): bool => $id !== $productId));

    return true;
}

function favoritesToggle(int $productId): bool
{
    if (favoritesHas($productId)) {
        favoritesRemove($productId);
        return false;
    }

    favoritesAdd($productId);
    return true;
}

function favoritesProducts(mysqli $conn): array
{
    $ids = favoritesIds();

    if (!$ids) {
        return [];
    }

    $productos = [];

    foreach ($ids as $id) {
        $producto = getProductoById($conn, $id);

        if ($producto) {
            $productos[] = $producto;
        }
    }

    return $productos;
}
