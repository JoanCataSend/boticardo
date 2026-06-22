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
function getAllProductos(mysqli $conn): array
{
    $productos = [];
    try {
        // Obtenemos todos los productos ordenados alfabéticamente
        $sql = "
            SELECT p.nombre, p.precio, p.imagen, l.nombre AS marca
            FROM productos p
            LEFT JOIN laboratorios l ON p.laboratorio_id = l.id
            ORDER BY p.nombre ASC
        ";

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