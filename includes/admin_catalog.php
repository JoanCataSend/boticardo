<?php
declare(strict_types=1);

require_once __DIR__ . '/content.php';

function adminCatalogEnsure(mysqli $conn): void
{
    contentEnsureTables($conn);

    $conn->query("\n        CREATE TABLE IF NOT EXISTS categorias (\n            id INT AUTO_INCREMENT PRIMARY KEY,\n            nombre VARCHAR(100) NOT NULL,\n            descripcion TEXT NULL\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    $conn->query("\n        CREATE TABLE IF NOT EXISTS laboratorios (\n            id INT AUTO_INCREMENT PRIMARY KEY,\n            nombre VARCHAR(100) NOT NULL,\n            pais_origen VARCHAR(50) NULL\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    $conn->query("\n        CREATE TABLE IF NOT EXISTS productos (\n            id INT AUTO_INCREMENT PRIMARY KEY,\n            codigo_sku VARCHAR(50) NOT NULL,\n            nombre VARCHAR(150) NOT NULL,\n            descripcion TEXT NULL,\n            imagen VARCHAR(255) DEFAULT 'placeholder.jpg',\n            precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,\n            stock INT NOT NULL DEFAULT 0,\n            ventas_totales INT NOT NULL DEFAULT 0,\n            requiere_receta TINYINT(1) DEFAULT 0,\n            categoria_id INT NULL,\n            laboratorio_id INT NULL,\n            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    $productColumns = [
        'codigo_nacional' => "ALTER TABLE productos ADD COLUMN codigo_nacional VARCHAR(20) DEFAULT NULL AFTER codigo_sku",
        'principio_activo' => "ALTER TABLE productos ADD COLUMN principio_activo VARCHAR(190) DEFAULT NULL AFTER descripcion",
        'modo_empleo' => "ALTER TABLE productos ADD COLUMN modo_empleo TEXT DEFAULT NULL AFTER principio_activo",
        'advertencias' => "ALTER TABLE productos ADD COLUMN advertencias TEXT DEFAULT NULL AFTER modo_empleo",
        'contraindicaciones' => "ALTER TABLE productos ADD COLUMN contraindicaciones TEXT DEFAULT NULL AFTER advertencias",
        'conservacion' => "ALTER TABLE productos ADD COLUMN conservacion TEXT DEFAULT NULL AFTER contraindicaciones",
        'en_oferta' => "ALTER TABLE productos ADD COLUMN en_oferta TINYINT(1) NOT NULL DEFAULT 0 AFTER precio",
        'precio_original' => "ALTER TABLE productos ADD COLUMN precio_original DECIMAL(10,2) DEFAULT NULL AFTER en_oferta",
        'descuento_porcentaje' => "ALTER TABLE productos ADD COLUMN descuento_porcentaje DECIMAL(5,2) DEFAULT NULL AFTER precio_original",
        'oferta_inicio' => "ALTER TABLE productos ADD COLUMN oferta_inicio DATETIME DEFAULT NULL AFTER descuento_porcentaje",
        'oferta_fin' => "ALTER TABLE productos ADD COLUMN oferta_fin DATETIME DEFAULT NULL AFTER oferta_inicio",
        'etiqueta_oferta' => "ALTER TABLE productos ADD COLUMN etiqueta_oferta VARCHAR(80) DEFAULT NULL AFTER oferta_fin",
        'destacar_oferta' => "ALTER TABLE productos ADD COLUMN destacar_oferta TINYINT(1) NOT NULL DEFAULT 0 AFTER etiqueta_oferta",
    ];

    foreach ($productColumns as $column => $sql) {
        if (!dbColumnExists($conn, 'productos', $column)) {
            $conn->query($sql);
        }
    }
}

function adminStoreStats(mysqli $conn): array
{
    adminCatalogEnsure($conn);

    $stats = [
        'productos' => 0,
        'agotados' => 0,
        'categorias' => 0,
        'laboratorios' => 0,
        'cupones_activos' => 0,
        'banners_activos' => 0,
    ];

    $queries = [
        'productos' => 'SELECT COUNT(*) AS total FROM productos',
        'agotados' => 'SELECT COUNT(*) AS total FROM productos WHERE stock <= 0',
        'categorias' => 'SELECT COUNT(*) AS total FROM categorias',
        'laboratorios' => 'SELECT COUNT(*) AS total FROM laboratorios',
        'cupones_activos' => 'SELECT COUNT(*) AS total FROM cupones WHERE activo = 1',
        'banners_activos' => 'SELECT COUNT(*) AS total FROM banners_portada WHERE activo = 1',
    ];

    foreach ($queries as $key => $sql) {
        try {
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            $stats[$key] = (int) ($row['total'] ?? 0);
            $result->free();
        } catch (Throwable $error) {
            error_log('Boticardo - Error cargando estadística admin: ' . $error->getMessage());
        }
    }

    return $stats;
}

function adminFetchCategorias(mysqli $conn): array
{
    adminCatalogEnsure($conn);
    $items = [];
    $result = $conn->query('SELECT c.*, COUNT(p.id) AS productos_total FROM categorias c LEFT JOIN productos p ON p.categoria_id = c.id GROUP BY c.id, c.nombre, c.descripcion ORDER BY c.nombre ASC');
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $result->free();
    return $items;
}

function adminFetchLaboratorios(mysqli $conn): array
{
    adminCatalogEnsure($conn);
    $items = [];
    $result = $conn->query('SELECT l.*, COUNT(p.id) AS productos_total FROM laboratorios l LEFT JOIN productos p ON p.laboratorio_id = l.id GROUP BY l.id, l.nombre, l.pais_origen ORDER BY l.nombre ASC');
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $result->free();
    return $items;
}

function adminFetchProducts(mysqli $conn, ?string $q = null, ?int $categoriaId = null, ?string $stockFilter = null): array
{
    adminCatalogEnsure($conn);
    $products = [];

    $sql = "\n        SELECT p.*, c.nombre AS categoria_nombre, l.nombre AS laboratorio_nombre\n        FROM productos p\n        LEFT JOIN categorias c ON c.id = p.categoria_id\n        LEFT JOIN laboratorios l ON l.id = p.laboratorio_id\n        WHERE 1=1\n    ";
    $types = '';
    $params = [];

    if ($q !== null && trim($q) !== '') {
        $term = '%' . trim($q) . '%';
        $sql .= ' AND (p.nombre LIKE ? OR p.codigo_sku LIKE ? OR p.codigo_nacional LIKE ?)';
        $types .= 'sss';
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    if ($categoriaId !== null && $categoriaId > 0) {
        $sql .= ' AND p.categoria_id = ?';
        $types .= 'i';
        $params[] = $categoriaId;
    }

    if ($stockFilter === 'agotados') {
        $sql .= ' AND p.stock <= 0';
    } elseif ($stockFilter === 'bajo') {
        $sql .= ' AND p.stock BETWEEN 1 AND 5';
    } elseif ($stockFilter === 'ofertas') {
        $sql .= ' AND p.en_oferta = 1';
    }

    $sql .= ' ORDER BY p.fecha_creacion DESC, p.id DESC LIMIT 300';
    $stmt = $conn->prepare($sql);
    dbBindDynamicParams($stmt, $types, $params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $result->free();
    $stmt->close();

    return $products;
}

function adminFetchProduct(mysqli $conn, int $id): ?array
{
    adminCatalogEnsure($conn);
    $stmt = $conn->prepare('SELECT * FROM productos WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc() ?: null;
    $result->free();
    $stmt->close();
    return $product;
}

function adminNormalizeDateTime(?string $value): ?string
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    $value = str_replace('T', ' ', $value);
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
        return $value . ':00';
    }

    return $value;
}

function adminDateTimeInput(?string $value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $timestamp = strtotime($value);
    return $timestamp ? date('Y-m-d\TH:i', $timestamp) : '';
}

function adminHandleImageUpload(string $field, string $currentImage = '', string $folder = 'productos'): string
{
    if (empty($_FILES[$field]) || !is_array($_FILES[$field]) || (int) ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return basename($currentImage) ?: '';
    }

    $file = $_FILES[$field];
    $error = (int) ($file['error'] ?? UPLOAD_ERR_OK);
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se ha podido subir la imagen. Código de error: ' . $error);
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    $originalName = (string) ($file['name'] ?? '');
    $size = (int) ($file['size'] ?? 0);

    if ($size <= 0 || $size > 3 * 1024 * 1024) {
        throw new RuntimeException('La imagen debe pesar menos de 3 MB.');
    }

    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($extension, $allowed, true)) {
        throw new RuntimeException('Formato de imagen no permitido. Usa JPG, PNG o WEBP.');
    }

    $imageInfo = @getimagesize($tmpName);
    if ($imageInfo === false) {
        throw new RuntimeException('El archivo subido no parece ser una imagen válida.');
    }

    $targetFolder = __DIR__ . '/../img/' . $folder;
    if (!is_dir($targetFolder) && !mkdir($targetFolder, 0775, true) && !is_dir($targetFolder)) {
        throw new RuntimeException('No se ha podido crear la carpeta de imágenes.');
    }

    $safeBase = preg_replace('/[^a-z0-9_-]+/i', '-', pathinfo($originalName, PATHINFO_FILENAME)) ?: 'imagen';
    $fileName = strtolower(trim($safeBase, '-')) . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $extension;
    $targetPath = $targetFolder . '/' . $fileName;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        throw new RuntimeException('No se ha podido guardar la imagen subida.');
    }

    return $fileName;
}

function adminCouponStatus(array $coupon): string
{
    if ((int) ($coupon['activo'] ?? 0) !== 1) {
        return 'Inactivo';
    }

    $now = time();
    $start = !empty($coupon['fecha_inicio']) ? strtotime((string) $coupon['fecha_inicio']) : null;
    $end = !empty($coupon['fecha_fin']) ? strtotime((string) $coupon['fecha_fin']) : null;

    if ($start && $start > $now) {
        return 'Programado';
    }

    if ($end && $end < $now) {
        return 'Caducado';
    }

    if (!empty($coupon['usos_maximos']) && (int) $coupon['usos_actuales'] >= (int) $coupon['usos_maximos']) {
        return 'Agotado';
    }

    return 'Activo';
}
