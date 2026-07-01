<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/favorites.php';

function accountColumnExists(mysqli $conn, string $table, string $column): bool
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

function accountEnsureTables(mysqli $conn): void
{
    authEnsureUsuariosTable($conn);

    if (!accountColumnExists($conn, 'usuarios', 'telefono')) {
        $conn->query('ALTER TABLE usuarios ADD COLUMN telefono VARCHAR(30) NULL AFTER email');
    }

    if (!accountColumnExists($conn, 'usuarios', 'dni_nif')) {
        $conn->query('ALTER TABLE usuarios ADD COLUMN dni_nif VARCHAR(20) NULL AFTER telefono');
    }

    $conn->query('
        CREATE TABLE IF NOT EXISTS usuario_direcciones (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT UNSIGNED NOT NULL,
            alias VARCHAR(80) NOT NULL DEFAULT "Principal",
            nombre VARCHAR(160) NOT NULL,
            telefono VARCHAR(30) NOT NULL,
            direccion VARCHAR(255) NOT NULL,
            codigo_postal VARCHAR(12) NOT NULL,
            localidad VARCHAR(120) NOT NULL,
            provincia VARCHAR(120) NOT NULL,
            pais CHAR(2) NOT NULL DEFAULT "ES",
            es_principal TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_usuario_direcciones_usuario (usuario_id),
            KEY idx_usuario_direcciones_principal (usuario_id, es_principal)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');

    $conn->query('
        CREATE TABLE IF NOT EXISTS usuario_favoritos (
            usuario_id INT UNSIGNED NOT NULL,
            producto_id INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (usuario_id, producto_id),
            KEY idx_usuario_favoritos_producto (producto_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');

    $conn->query('
        CREATE TABLE IF NOT EXISTS usuario_carrito (
            usuario_id INT UNSIGNED NOT NULL,
            producto_id INT UNSIGNED NOT NULL,
            cantidad INT UNSIGNED NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (usuario_id, producto_id),
            KEY idx_usuario_carrito_producto (producto_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');
}

function accountCleanText(?string $value, int $maxLength): string
{
    $value = trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');
    return mb_substr($value, 0, $maxLength, 'UTF-8');
}

function accountCurrentUserFresh(mysqli $conn, int $userId): ?array
{
    accountEnsureTables($conn);

    $stmt = $conn->prepare('SELECT * FROM usuarios WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc() ?: null;
    $result->free();
    $stmt->close();

    return $user;
}

function accountRefreshSessionUser(array $user): void
{
    if (!isset($_SESSION[BOTICARDO_USER_SESSION_KEY]) || !is_array($_SESSION[BOTICARDO_USER_SESSION_KEY])) {
        return;
    }

    $_SESSION[BOTICARDO_USER_SESSION_KEY]['nombre'] = (string) ($user['nombre'] ?? $_SESSION[BOTICARDO_USER_SESSION_KEY]['nombre'] ?? '');
    $_SESSION[BOTICARDO_USER_SESSION_KEY]['email'] = (string) ($user['email'] ?? $_SESSION[BOTICARDO_USER_SESSION_KEY]['email'] ?? '');
    $_SESSION[BOTICARDO_USER_SESSION_KEY]['rol'] = (string) ($user['rol'] ?? $_SESSION[BOTICARDO_USER_SESSION_KEY]['rol'] ?? 'cliente');
    $_SESSION[BOTICARDO_USER_SESSION_KEY]['email_verificado'] = (int) ($user['email_verificado'] ?? 1);
}

function accountUpdateProfile(mysqli $conn, int $userId, array $data): array
{
    accountEnsureTables($conn);

    $nombre = accountCleanText($data['nombre'] ?? '', 120);
    $telefono = accountCleanText($data['telefono'] ?? '', 30);
    $dniNif = strtoupper(accountCleanText($data['dni_nif'] ?? '', 20));

    $errors = [];

    if (mb_strlen($nombre, 'UTF-8') < 2) {
        $errors[] = 'Escribe tu nombre completo.';
    }

    if ($telefono !== '' && !preg_match('/^[0-9 +()\-]{6,30}$/', $telefono)) {
        $errors[] = 'El teléfono no tiene un formato válido.';
    }

    if ($dniNif !== '' && !preg_match('/^[A-Z0-9 .\-]{4,20}$/i', $dniNif)) {
        $errors[] = 'El DNI/NIF no tiene un formato válido.';
    }

    if ($errors !== []) {
        return ['ok' => false, 'message' => implode(' ', $errors)];
    }

    $stmt = $conn->prepare('UPDATE usuarios SET nombre = ?, telefono = NULLIF(?, ""), dni_nif = NULLIF(?, "") WHERE id = ?');
    $stmt->bind_param('sssi', $nombre, $telefono, $dniNif, $userId);
    $stmt->execute();
    $stmt->close();

    $freshUser = accountCurrentUserFresh($conn, $userId);
    if ($freshUser) {
        accountRefreshSessionUser($freshUser);
    }

    return ['ok' => true, 'message' => 'Datos personales actualizados correctamente.'];
}

function accountChangePassword(mysqli $conn, int $userId, array $data): array
{
    accountEnsureTables($conn);

    $currentPassword = (string) ($data['current_password'] ?? '');
    $newPassword = (string) ($data['new_password'] ?? '');
    $newPasswordConfirm = (string) ($data['new_password_confirm'] ?? '');

    $user = accountCurrentUserFresh($conn, $userId);
    if (!$user) {
        return ['ok' => false, 'message' => 'No se ha encontrado la cuenta.'];
    }

    $savedHash = (string) ($user['password_hash'] ?? '');
    if ($savedHash !== '' && !password_verify($currentPassword, $savedHash)) {
        return ['ok' => false, 'message' => 'La contraseña actual no es correcta.'];
    }

    if (strlen($newPassword) < 8) {
        return ['ok' => false, 'message' => 'La nueva contraseña debe tener al menos 8 caracteres.'];
    }

    if ($newPassword !== $newPasswordConfirm) {
        return ['ok' => false, 'message' => 'Las contraseñas nuevas no coinciden.'];
    }

    if ($savedHash !== '' && password_verify($newPassword, $savedHash)) {
        return ['ok' => false, 'message' => 'La nueva contraseña debe ser diferente a la actual.'];
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?');
    $stmt->bind_param('si', $newHash, $userId);
    $stmt->execute();
    $stmt->close();

    return ['ok' => true, 'message' => 'Contraseña actualizada correctamente.'];
}

function accountValidateAddress(array $data): array
{
    $address = [
        'id' => isset($data['address_id']) ? max(0, (int) $data['address_id']) : 0,
        'alias' => accountCleanText($data['alias'] ?? 'Principal', 80),
        'nombre' => accountCleanText($data['nombre'] ?? '', 160),
        'telefono' => accountCleanText($data['telefono'] ?? '', 30),
        'direccion' => accountCleanText($data['direccion'] ?? '', 255),
        'codigo_postal' => accountCleanText($data['codigo_postal'] ?? '', 12),
        'localidad' => accountCleanText($data['localidad'] ?? '', 120),
        'provincia' => accountCleanText($data['provincia'] ?? '', 120),
        'pais' => 'ES',
        'es_principal' => !empty($data['es_principal']) ? 1 : 0,
    ];

    if ($address['alias'] === '') {
        $address['alias'] = 'Principal';
    }

    $errors = [];

    if (mb_strlen($address['nombre'], 'UTF-8') < 2) {
        $errors[] = 'Escribe el nombre de la persona que recibirá el pedido.';
    }

    if (!preg_match('/^[0-9 +()\-]{6,30}$/', $address['telefono'])) {
        $errors[] = 'Escribe un teléfono válido.';
    }

    if (mb_strlen($address['direccion'], 'UTF-8') < 5) {
        $errors[] = 'Escribe una dirección completa.';
    }

    if (!preg_match('/^[0-9]{5}$/', $address['codigo_postal'])) {
        $errors[] = 'El código postal debe tener 5 números.';
    }

    if (mb_strlen($address['localidad'], 'UTF-8') < 2) {
        $errors[] = 'Escribe la localidad.';
    }

    if (mb_strlen($address['provincia'], 'UTF-8') < 2) {
        $errors[] = 'Escribe la provincia.';
    }

    return [$address, $errors];
}

function accountGetAddresses(mysqli $conn, int $userId): array
{
    accountEnsureTables($conn);

    $stmt = $conn->prepare('
        SELECT *
        FROM usuario_direcciones
        WHERE usuario_id = ?
        ORDER BY es_principal DESC, updated_at DESC, id DESC
    ');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $addresses = [];
    while ($row = $result->fetch_assoc()) {
        $addresses[] = $row;
    }

    $result->free();
    $stmt->close();

    return $addresses;
}

function accountGetDefaultAddress(mysqli $conn, int $userId): ?array
{
    accountEnsureTables($conn);

    $stmt = $conn->prepare('
        SELECT *
        FROM usuario_direcciones
        WHERE usuario_id = ?
        ORDER BY es_principal DESC, updated_at DESC, id DESC
        LIMIT 1
    ');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $address = $result->fetch_assoc() ?: null;
    $result->free();
    $stmt->close();

    return $address;
}

function accountSaveAddress(mysqli $conn, int $userId, array $data): array
{
    accountEnsureTables($conn);

    [$address, $errors] = accountValidateAddress($data);
    if ($errors !== []) {
        return ['ok' => false, 'message' => implode(' ', $errors)];
    }

    $addresses = accountGetAddresses($conn, $userId);
    if ($addresses === []) {
        $address['es_principal'] = 1;
    }

    $conn->begin_transaction();

    try {
        if ((int) $address['es_principal'] === 1) {
            $stmt = $conn->prepare('UPDATE usuario_direcciones SET es_principal = 0 WHERE usuario_id = ?');
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();
        }

        if ((int) $address['id'] > 0) {
            $stmt = $conn->prepare('
                UPDATE usuario_direcciones
                SET alias = ?, nombre = ?, telefono = ?, direccion = ?, codigo_postal = ?, localidad = ?, provincia = ?, pais = ?, es_principal = ?
                WHERE id = ? AND usuario_id = ?
            ');
            $stmt->bind_param(
                'ssssssssiii',
                $address['alias'],
                $address['nombre'],
                $address['telefono'],
                $address['direccion'],
                $address['codigo_postal'],
                $address['localidad'],
                $address['provincia'],
                $address['pais'],
                $address['es_principal'],
                $address['id'],
                $userId
            );
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();

            if ($affected < 0) {
                throw new RuntimeException('No se pudo actualizar la dirección.');
            }
        } else {
            $stmt = $conn->prepare('
                INSERT INTO usuario_direcciones (usuario_id, alias, nombre, telefono, direccion, codigo_postal, localidad, provincia, pais, es_principal)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->bind_param(
                'issssssssi',
                $userId,
                $address['alias'],
                $address['nombre'],
                $address['telefono'],
                $address['direccion'],
                $address['codigo_postal'],
                $address['localidad'],
                $address['provincia'],
                $address['pais'],
                $address['es_principal']
            );
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }

    return ['ok' => true, 'message' => 'Dirección guardada correctamente.'];
}

function accountDeleteAddress(mysqli $conn, int $userId, int $addressId): array
{
    accountEnsureTables($conn);

    if ($addressId <= 0) {
        return ['ok' => false, 'message' => 'Dirección no válida.'];
    }

    $stmt = $conn->prepare('DELETE FROM usuario_direcciones WHERE id = ? AND usuario_id = ?');
    $stmt->bind_param('ii', $addressId, $userId);
    $stmt->execute();
    $stmt->close();

    $addresses = accountGetAddresses($conn, $userId);
    $hasDefault = false;
    foreach ($addresses as $address) {
        if ((int) ($address['es_principal'] ?? 0) === 1) {
            $hasDefault = true;
            break;
        }
    }

    if (!$hasDefault && $addresses !== []) {
        $firstId = (int) $addresses[0]['id'];
        $stmt = $conn->prepare('UPDATE usuario_direcciones SET es_principal = 1 WHERE id = ? AND usuario_id = ?');
        $stmt->bind_param('ii', $firstId, $userId);
        $stmt->execute();
        $stmt->close();
    }

    return ['ok' => true, 'message' => 'Dirección eliminada.'];
}

function accountSetDefaultAddress(mysqli $conn, int $userId, int $addressId): array
{
    accountEnsureTables($conn);

    if ($addressId <= 0) {
        return ['ok' => false, 'message' => 'Dirección no válida.'];
    }

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare('UPDATE usuario_direcciones SET es_principal = 0 WHERE usuario_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('UPDATE usuario_direcciones SET es_principal = 1 WHERE id = ? AND usuario_id = ?');
        $stmt->bind_param('ii', $addressId, $userId);
        $stmt->execute();
        $updated = $stmt->affected_rows;
        $stmt->close();

        if ($updated < 1) {
            throw new RuntimeException('No se encontró la dirección.');
        }

        $conn->commit();
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }

    return ['ok' => true, 'message' => 'Dirección principal actualizada.'];
}

function accountPersistCart(mysqli $conn, int $userId, array $cart): void
{
    accountEnsureTables($conn);

    $stmt = $conn->prepare('DELETE FROM usuario_carrito WHERE usuario_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();

    $insert = $conn->prepare('
        INSERT INTO usuario_carrito (usuario_id, producto_id, cantidad)
        VALUES (?, ?, ?)
    ');

    foreach ($cart as $productId => $quantity) {
        $productId = (int) $productId;
        $quantity = min(max(1, (int) $quantity), BOTICARDO_CART_MAX_QUANTITY);

        if ($productId <= 0 || $quantity <= 0) {
            continue;
        }

        $insert->bind_param('iii', $userId, $productId, $quantity);
        $insert->execute();
    }

    $insert->close();
}

function accountPersistCartFromSession(mysqli $conn, int $userId): void
{
    accountPersistCart($conn, $userId, cartGetRaw());
}

function accountLoadCartIntoSession(mysqli $conn, int $userId): array
{
    accountEnsureTables($conn);

    $stmt = $conn->prepare('
        SELECT uc.producto_id, uc.cantidad, p.stock
        FROM usuario_carrito uc
        INNER JOIN productos p ON p.id = uc.producto_id
        WHERE uc.usuario_id = ?
        ORDER BY uc.updated_at DESC
    ');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $cart = [];
    while ($row = $result->fetch_assoc()) {
        $productId = (int) ($row['producto_id'] ?? 0);
        $stock = max(0, (int) ($row['stock'] ?? 0));
        $quantity = min(max(1, (int) ($row['cantidad'] ?? 1)), $stock, BOTICARDO_CART_MAX_QUANTITY);

        if ($productId > 0 && $quantity > 0) {
            $cart[$productId] = $quantity;
        }
    }

    $result->free();
    $stmt->close();

    $_SESSION[BOTICARDO_CART_SESSION_KEY] = $cart;
    accountPersistCart($conn, $userId, $cart);

    return $cart;
}

function accountMergeSessionCartToDatabase(mysqli $conn, int $userId): void
{
    accountEnsureTables($conn);

    $cart = cartGetRaw();
    if ($cart === []) {
        accountLoadCartIntoSession($conn, $userId);
        return;
    }

    $stmt = $conn->prepare('
        INSERT INTO usuario_carrito (usuario_id, producto_id, cantidad)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE cantidad = LEAST(cantidad + VALUES(cantidad), ?), updated_at = CURRENT_TIMESTAMP
    ');

    foreach ($cart as $productId => $quantity) {
        $productId = (int) $productId;
        $quantity = min(max(1, (int) $quantity), BOTICARDO_CART_MAX_QUANTITY);
        $maxQuantity = BOTICARDO_CART_MAX_QUANTITY;

        if ($productId <= 0) {
            continue;
        }

        $stmt->bind_param('iiii', $userId, $productId, $quantity, $maxQuantity);
        $stmt->execute();
    }

    $stmt->close();
    accountLoadCartIntoSession($conn, $userId);
}

function accountPersistFavorites(mysqli $conn, int $userId, array $ids): void
{
    accountEnsureTables($conn);

    $stmt = $conn->prepare('DELETE FROM usuario_favoritos WHERE usuario_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();

    $insert = $conn->prepare('
        INSERT IGNORE INTO usuario_favoritos (usuario_id, producto_id)
        VALUES (?, ?)
    ');

    foreach ($ids as $productId) {
        $productId = (int) $productId;
        if ($productId <= 0) {
            continue;
        }

        $insert->bind_param('ii', $userId, $productId);
        $insert->execute();
    }

    $insert->close();
}

function accountPersistFavoritesFromSession(mysqli $conn, int $userId): void
{
    accountPersistFavorites($conn, $userId, favoritesIds());
}

function accountLoadFavoritesIntoSession(mysqli $conn, int $userId): array
{
    accountEnsureTables($conn);

    $stmt = $conn->prepare('
        SELECT uf.producto_id
        FROM usuario_favoritos uf
        INNER JOIN productos p ON p.id = uf.producto_id
        WHERE uf.usuario_id = ?
        ORDER BY uf.created_at DESC
    ');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $ids = [];
    while ($row = $result->fetch_assoc()) {
        $ids[] = (int) $row['producto_id'];
    }

    $result->free();
    $stmt->close();

    $_SESSION['favorites'] = array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));
    accountPersistFavorites($conn, $userId, $_SESSION['favorites']);

    return $_SESSION['favorites'];
}

function accountMergeSessionFavoritesToDatabase(mysqli $conn, int $userId): void
{
    accountEnsureTables($conn);

    $ids = favoritesIds();
    if ($ids !== []) {
        $stmt = $conn->prepare('
            INSERT IGNORE INTO usuario_favoritos (usuario_id, producto_id)
            VALUES (?, ?)
        ');

        foreach ($ids as $productId) {
            $productId = (int) $productId;
            if ($productId <= 0) {
                continue;
            }

            $stmt->bind_param('ii', $userId, $productId);
            $stmt->execute();
        }

        $stmt->close();
    }

    accountLoadFavoritesIntoSession($conn, $userId);
}

function accountSyncSessionWithDatabase(mysqli $conn, int $userId): void
{
    if ($userId <= 0) {
        return;
    }

    $syncKey = 'boticardo_account_synced_' . $userId;
    if (!empty($_SESSION[$syncKey])) {
        return;
    }

    accountEnsureTables($conn);
    accountMergeSessionCartToDatabase($conn, $userId);
    accountMergeSessionFavoritesToDatabase($conn, $userId);
    $_SESSION[$syncKey] = true;
}

function accountGetFavoriteProducts(mysqli $conn, int $userId, int $limit = 12): array
{
    accountEnsureTables($conn);

    $limit = max(1, min($limit, 24));
    $stmt = $conn->prepare('
        SELECT p.id, p.nombre, p.precio, p.imagen, p.stock, l.nombre AS marca
        FROM usuario_favoritos uf
        INNER JOIN productos p ON p.id = uf.producto_id
        LEFT JOIN laboratorios l ON l.id = p.laboratorio_id
        WHERE uf.usuario_id = ?
        ORDER BY uf.created_at DESC
        LIMIT ?
    ');
    $stmt->bind_param('ii', $userId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    $result->free();
    $stmt->close();

    return $products;
}

function accountGetRecentOrders(mysqli $conn, int $userId, int $limit = 5): array
{
    require_once __DIR__ . '/orders.php';
    orderEnsureTables($conn);

    $limit = max(1, min($limit, 20));
    $stmt = $conn->prepare('
        SELECT *
        FROM pedidos
        WHERE usuario_id = ?
        ORDER BY created_at DESC
        LIMIT ?
    ');
    $stmt->bind_param('ii', $userId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }

    $result->free();
    $stmt->close();

    return $orders;
}
