<?php
declare(strict_types=1);

/** Escapa texto para mostrarlo de forma segura dentro del HTML. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Obtiene los productos más vendidos desde la Base de Datos */
function getProductosMasVendidos(mysqli $conn): array
{
    $productos = [];
    try {
        $sqlMasVendidos = "
            SELECT p.nombre, p.precio, p.imagen, l.nombre AS marca
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
/** Obtiene todos los productos del catálogo desde la Base de Datos */
/** Obtiene todos los productos del catálogo (opcionalmente filtrados por categoría) */
/** Obtiene todos los productos del catálogo filtrados por categoría, precio y marca */
function getAllProductos(mysqli $conn, ?int $categoria_id = null, ?float $min_price = null, ?float $max_price = null, ?string $marca = null): array
{
    $productos = [];
    try {
        $sql = "
            SELECT p.nombre, p.precio, p.imagen, l.nombre AS marca
            FROM productos p
            LEFT JOIN laboratorios l ON p.laboratorio_id = l.id
            WHERE 1=1
        ";

        // Filtro de Categoría
        if ($categoria_id !== null && $categoria_id > 0) {
            $sql .= " AND p.categoria_id = " . $categoria_id;
        }

        // Filtro de Precio Mínimo
        if ($min_price !== null) {
            $sql .= " AND p.precio >= " . $min_price;
        }

        // Filtro de Precio Máximo
        if ($max_price !== null) {
            $sql .= " AND p.precio <= " . $max_price;
        }

        // Filtro de Marca
        if ($marca !== null) {
            // Escapamos el texto para evitar inyecciones SQL
            $sql .= " AND l.nombre = '" . $conn->real_escape_string($marca) . "'";
        }

        $sql .= " ORDER BY p.nombre ASC";

        $resultado = $conn->query($sql);

        while ($producto = $resultado->fetch_assoc()) {
            $productos[] = $producto;
        }

        $resultado->free();
    } catch (Throwable $error) {
        error_log('Boticardo - Error al cargar el catálogo: ' . $error->getMessage());
    }

    return $productos;
}