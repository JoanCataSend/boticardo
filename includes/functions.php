<?php
declare(strict_types=1);

/** Escapa texto para mostrarlo de forma segura dentro del HTML. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Comprueba de forma segura si existe una columna en una tabla. */
function dbColumnExists(mysqli $conn, string $tableName, string $columnName): bool
{
    try {
        $stmt = $conn->prepare("\n            SELECT COUNT(*) AS total\n            FROM INFORMATION_SCHEMA.COLUMNS\n            WHERE TABLE_SCHEMA = DATABASE()\n              AND TABLE_NAME = ?\n              AND COLUMN_NAME = ?\n            LIMIT 1\n        ");
        $stmt->bind_param('ss', $tableName, $columnName);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $result->free();
        $stmt->close();

        return (int) ($row['total'] ?? 0) > 0;
    } catch (Throwable $error) {
        error_log('Boticardo - Error al comprobar columna de BD: ' . $error->getMessage());
        return false;
    }
}

/** Obtiene los productos más vendidos desde la Base de Datos */
function getProductosMasVendidos(mysqli $conn): array
{
    $productos = [];
    try {
        $sqlMasVendidos = "
            SELECT p.id, p.nombre, p.precio, p.imagen, l.nombre AS marca
            FROM productos p
            LEFT JOIN laboratorios l ON p.laboratorio_id = l.id
            ORDER BY p.ventas_totales DESC
            LIMIT 4
        ";

        $resultado = $conn->query($sqlMasVendidos);

        while ($producto = $resultado->fetch_assoc()) {
            $productos[] = $producto;
        }

        $resultado->free();
    } catch (Throwable $error) {
        error_log('Boticardo - Error al cargar productos: ' . $error->getMessage());
    }

    return $productos;
}

/** Enlaza parámetros dinámicos a una consulta preparada de mysqli. */
function dbBindDynamicParams(mysqli_stmt $stmt, string $types, array &$params): void
{
    if ($types === '' || $params === []) {
        return;
    }

    $bindValues = [$types];

    foreach ($params as $index => $value) {
        $bindValues[] = &$params[$index];
    }

    call_user_func_array([$stmt, 'bind_param'], $bindValues);
}

/** Obtiene las marcas/laboratorios disponibles desde la Base de Datos. */
function getMarcasDisponibles(mysqli $conn, ?int $categoria_id = null, ?float $min_price = null, ?float $max_price = null): array
{
    $marcas = [];

    try {
        $sql = "
            SELECT l.nombre, COUNT(p.id) AS total_productos
            FROM laboratorios l
            INNER JOIN productos p ON p.laboratorio_id = l.id
            WHERE 1=1
        ";
        $types = '';
        $params = [];

        if ($categoria_id !== null && $categoria_id > 0) {
            $sql .= " AND p.categoria_id = ?";
            $types .= 'i';
            $params[] = $categoria_id;
        }

        if ($min_price !== null && $min_price >= 0) {
            $sql .= " AND p.precio >= ?";
            $types .= 'd';
            $params[] = $min_price;
        }

        if ($max_price !== null && $max_price >= 0) {
            $sql .= " AND p.precio <= ?";
            $types .= 'd';
            $params[] = $max_price;
        }

        $sql .= "
            GROUP BY l.id, l.nombre
            ORDER BY l.nombre ASC
        ";

        $stmt = $conn->prepare($sql);
        dbBindDynamicParams($stmt, $types, $params);
        $stmt->execute();
        $resultado = $stmt->get_result();

        while ($marca = $resultado->fetch_assoc()) {
            $marcas[] = $marca;
        }

        $resultado->free();
        $stmt->close();
    } catch (Throwable $error) {
        error_log('Boticardo - Error al cargar marcas disponibles: ' . $error->getMessage());
    }

    return $marcas;
}

/** Obtiene todos los productos del catálogo filtrados por categoría, precio y marca */
function getAllProductos(mysqli $conn, ?int $categoria_id = null, ?float $min_price = null, ?float $max_price = null, ?string $marca = null): array
{
    $productos = [];

    try {
        $sql = "
            SELECT p.id, p.nombre, p.precio, p.imagen, l.nombre AS marca
            FROM productos p
            LEFT JOIN laboratorios l ON p.laboratorio_id = l.id
            WHERE 1=1
        ";
        $types = '';
        $params = [];

        if ($categoria_id !== null && $categoria_id > 0) {
            $sql .= " AND p.categoria_id = ?";
            $types .= 'i';
            $params[] = $categoria_id;
        }

        if ($min_price !== null && $min_price >= 0) {
            $sql .= " AND p.precio >= ?";
            $types .= 'd';
            $params[] = $min_price;
        }

        if ($max_price !== null && $max_price >= 0) {
            $sql .= " AND p.precio <= ?";
            $types .= 'd';
            $params[] = $max_price;
        }

        if ($marca !== null && trim($marca) !== '') {
            $sql .= " AND l.nombre = ?";
            $types .= 's';
            $params[] = trim($marca);
        }

        $sql .= " ORDER BY p.nombre ASC";

        $stmt = $conn->prepare($sql);
        dbBindDynamicParams($stmt, $types, $params);
        $stmt->execute();
        $resultado = $stmt->get_result();

        while ($producto = $resultado->fetch_assoc()) {
            $productos[] = $producto;
        }

        $resultado->free();
        $stmt->close();
    } catch (Throwable $error) {
        error_log('Boticardo - Error al cargar el catálogo: ' . $error->getMessage());
    }

    return $productos;
}

function getProductoById(mysqli $conn, int $productId): ?array
{
    if ($productId <= 0) {
        return null;
    }

    try {
        $stmt = $conn->prepare("
            SELECT p.id, p.nombre, p.precio, p.imagen, l.nombre AS marca
            FROM productos p
            LEFT JOIN laboratorios l ON p.laboratorio_id = l.id
            WHERE p.id = ?
            LIMIT 1
        ");
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc() ?: null;
        $result->free();
        $stmt->close();

        return $product;
    } catch (Throwable $error) {
        error_log('Boticardo - Error al cargar producto por ID: ' . $error->getMessage());
        return null;
    }
}


/** Obtiene un producto con información ampliada para la ficha de producto. */
function getProductoDetalleById(mysqli $conn, int $productId): ?array
{
    if ($productId <= 0) {
        return null;
    }

    try {
        $descripcionSelect = dbColumnExists($conn, 'productos', 'descripcion')
            ? 'p.descripcion'
            : 'NULL AS descripcion';

        $stmt = $conn->prepare("\n            SELECT p.id, p.nombre, p.precio, p.imagen, p.categoria_id, {$descripcionSelect}, l.nombre AS marca\n            FROM productos p\n            LEFT JOIN laboratorios l ON p.laboratorio_id = l.id\n            WHERE p.id = ?\n            LIMIT 1\n        ");
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc() ?: null;
        $result->free();
        $stmt->close();

        return $product;
    } catch (Throwable $error) {
        error_log('Boticardo - Error al cargar detalle de producto: ' . $error->getMessage());
        return null;
    }
}

/** Devuelve productos relacionados de la misma categoría. */
function getProductosRelacionados(mysqli $conn, int $productId, ?int $categoriaId = null, int $limit = 4): array
{
    if ($productId <= 0) {
        return [];
    }

    $limit = max(1, min($limit, 12));
    $productos = [];

    try {
        if ($categoriaId !== null && $categoriaId > 0) {
            $stmt = $conn->prepare("\n                SELECT p.id, p.nombre, p.precio, p.imagen, l.nombre AS marca\n                FROM productos p\n                LEFT JOIN laboratorios l ON p.laboratorio_id = l.id\n                WHERE p.id <> ? AND p.categoria_id = ?\n                ORDER BY p.ventas_totales DESC, p.nombre ASC\n                LIMIT ?\n            ");
            $stmt->bind_param('iii', $productId, $categoriaId, $limit);
        } else {
            $stmt = $conn->prepare("\n                SELECT p.id, p.nombre, p.precio, p.imagen, l.nombre AS marca\n                FROM productos p\n                LEFT JOIN laboratorios l ON p.laboratorio_id = l.id\n                WHERE p.id <> ?\n                ORDER BY p.ventas_totales DESC, p.nombre ASC\n                LIMIT ?\n            ");
            $stmt->bind_param('ii', $productId, $limit);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        while ($producto = $result->fetch_assoc()) {
            $productos[] = $producto;
        }

        $result->free();
        $stmt->close();
    } catch (Throwable $error) {
        error_log('Boticardo - Error al cargar productos relacionados: ' . $error->getMessage());
    }

    return $productos;
}

/** URL interna de ficha de producto. */
function productoUrl(int $productId): string
{
    return 'producto.php?id=' . max(0, $productId);
}

/** Normaliza un texto para búsquedas: minúsculas, sin acentos y sin signos raros. */
function normalizeSearchText(?string $text): string
{
    $text = trim((string) $text);

    if ($text === '') {
        return '';
    }

    if (function_exists('mb_strtolower')) {
        $text = mb_strtolower($text, 'UTF-8');
    } else {
        $text = strtolower($text);
    }

    $text = strtr($text, [
        'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a',
        'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
        'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
        'ñ' => 'n', 'ç' => 'c',
    ]);

    $text = preg_replace('/[^a-z0-9]+/u', ' ', $text) ?? $text;
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

    return trim($text);
}

/** Variante fonética sencilla para errores típicos en español: b/v, h muda, y/i. */
function normalizeSearchPhonetic(?string $text): string
{
    $text = normalizeSearchText($text);

    if ($text === '') {
        return '';
    }

    $text = str_replace(['v', 'h', 'y'], ['b', '', 'i'], $text);
    $text = preg_replace('/(.)\1+/u', '$1', $text) ?? $text;

    return $text;
}

/** Divide la búsqueda en palabras útiles. */
function searchTokens(string $query): array
{
    $query = normalizeSearchText($query);

    if ($query === '') {
        return [];
    }

    $tokens = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $stopWords = ['de', 'del', 'la', 'el', 'los', 'las', 'con', 'para', 'por', 'y', 'en'];

    return array_values(array_unique(array_filter($tokens, static function (string $token) use ($stopWords): bool {
        return strlen($token) >= 2 && !in_array($token, $stopWords, true);
    })));
}

/** Umbral de distancia máxima según la longitud de la palabra buscada. */
function searchMaxDistance(string $token): int
{
    $length = strlen($token);

    if ($length <= 4) {
        return 1;
    }

    if ($length <= 8) {
        return 2;
    }

    return 3;
}

/** Devuelve productos ordenados por relevancia, tolerando errores de escritura. */
function searchProductos(mysqli $conn, string $query, int $limit = 48): array
{
    $query = trim($query);
    $normalizedQuery = normalizeSearchText($query);
    $phoneticQuery = normalizeSearchPhonetic($query);
    $queryTokens = searchTokens($query);

    if ($normalizedQuery === '' || strlen($normalizedQuery) < 2 || !$queryTokens) {
        return [];
    }

    $productos = [];

    try {
        $sql = "
            SELECT p.id, p.nombre, p.precio, p.imagen, l.nombre AS marca
            FROM productos p
            LEFT JOIN laboratorios l ON p.laboratorio_id = l.id
            ORDER BY p.nombre ASC
        ";

        $resultado = $conn->query($sql);

        while ($producto = $resultado->fetch_assoc()) {
            $nombre = (string) ($producto['nombre'] ?? '');
            $marca = (string) ($producto['marca'] ?? '');
            $combined = trim($nombre . ' ' . $marca);
            $normalizedCombined = normalizeSearchText($combined);
            $normalizedName = normalizeSearchText($nombre);
            $normalizedBrand = normalizeSearchText($marca);
            $phoneticCombined = normalizeSearchPhonetic($combined);
            $productTokens = searchTokens($combined);
            $score = 0;
            $matchedTokens = 0;

            if ($normalizedCombined === $normalizedQuery) {
                $score += 300;
            }

            if (str_contains($normalizedName, $normalizedQuery)) {
                $score += 170;
            }

            if ($normalizedBrand !== '' && str_contains($normalizedBrand, $normalizedQuery)) {
                $score += 145;
            }

            if (str_contains($normalizedCombined, $normalizedQuery)) {
                $score += 120;
            }

            if ($phoneticQuery !== '' && str_contains($phoneticCombined, $phoneticQuery)) {
                $score += 95;
            }

            foreach ($queryTokens as $queryToken) {
                $tokenMatched = false;
                $phoneticQueryToken = normalizeSearchPhonetic($queryToken);

                foreach ($productTokens as $productToken) {
                    $phoneticProductToken = normalizeSearchPhonetic($productToken);

                    if ($productToken === $queryToken) {
                        $score += 100;
                        $tokenMatched = true;
                        break;
                    }

                    if (str_starts_with($productToken, $queryToken) || str_starts_with($queryToken, $productToken)) {
                        $score += 72;
                        $tokenMatched = true;
                        break;
                    }

                    if ($phoneticProductToken !== '' && $phoneticQueryToken !== '' && $phoneticProductToken === $phoneticQueryToken) {
                        $score += 64;
                        $tokenMatched = true;
                        break;
                    }

                    $distance = levenshtein($queryToken, $productToken);
                    $maxDistance = searchMaxDistance($queryToken);
                    similar_text($queryToken, $productToken, $similarity);

                    if ($distance <= $maxDistance || $similarity >= 72) {
                        $score += max(35, 70 - ($distance * 10));
                        $tokenMatched = true;
                        break;
                    }
                }

                if ($tokenMatched) {
                    $matchedTokens++;
                }
            }

            if ($matchedTokens === count($queryTokens)) {
                $score += 85;
            } elseif ($matchedTokens > 0) {
                $score += 25 * $matchedTokens;
            }

            if ($score > 0) {
                $producto['_search_score'] = $score;
                $productos[] = $producto;
            }
        }

        $resultado->free();
    } catch (Throwable $error) {
        error_log('Boticardo - Error al buscar productos: ' . $error->getMessage());
    }

    usort($productos, static function (array $a, array $b): int {
        $scoreCompare = ((int) ($b['_search_score'] ?? 0)) <=> ((int) ($a['_search_score'] ?? 0));

        if ($scoreCompare !== 0) {
            return $scoreCompare;
        }

        return strcasecmp((string) ($a['nombre'] ?? ''), (string) ($b['nombre'] ?? ''));
    });

    if ($limit > 0) {
        $productos = array_slice($productos, 0, $limit);
    }

    foreach ($productos as &$producto) {
        unset($producto['_search_score']);
    }
    unset($producto);

    return $productos;
}

/** Sugerencias rápidas para el buscador del header. */
function searchProductSuggestions(mysqli $conn, string $query, int $limit = 6): array
{
    return searchProductos($conn, $query, $limit);
}

