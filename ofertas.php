<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/schema.php';

$pageTitle = 'Ofertas y promociones | Boticardo';
$pageDescription = 'Consulta las ofertas activas de Boticardo en productos de farmacia, higiene, dermocosmética, vitaminas y cuidado diario.';
$canonicalUrl = rtrim((string) ($siteUrl ?? ''), '/') . '/ofertas.php';

/** Comprueba que la tabla productos ya tiene los campos nuevos de ofertas. */
function ofertasSchemaDisponible(mysqli $conn): bool
{
    return dbColumnExists($conn, 'productos', 'en_oferta')
        && dbColumnExists($conn, 'productos', 'precio_original')
        && dbColumnExists($conn, 'productos', 'descuento_porcentaje')
        && dbColumnExists($conn, 'productos', 'oferta_inicio')
        && dbColumnExists($conn, 'productos', 'oferta_fin')
        && dbColumnExists($conn, 'productos', 'etiqueta_oferta')
        && dbColumnExists($conn, 'productos', 'destacar_oferta');
}

/** Carga productos en oferta según la estructura real de tu BBDD. */
function getProductosEnOferta(mysqli $conn, int $limit = 24): array
{
    $limit = max(1, min($limit, 48));

    if (!ofertasSchemaDisponible($conn)) {
        return [];
    }

    $sql = "
        SELECT
            p.id,
            p.codigo_sku,
            p.nombre,
            p.descripcion,
            p.imagen,
            p.precio,
            p.precio_original,
            p.descuento_porcentaje,
            p.etiqueta_oferta,
            p.oferta_inicio,
            p.oferta_fin,
            p.stock,
            p.destacar_oferta,
            l.nombre AS marca,
            c.nombre AS categoria
        FROM productos p
        LEFT JOIN laboratorios l ON p.laboratorio_id = l.id
        LEFT JOIN categorias c ON p.categoria_id = c.id
        WHERE p.en_oferta = 1
          AND p.stock > 0
          AND COALESCE(p.requiere_receta, 0) = 0
          AND (p.oferta_inicio IS NULL OR p.oferta_inicio <= NOW())
          AND (p.oferta_fin IS NULL OR p.oferta_fin >= NOW())
        ORDER BY p.destacar_oferta DESC, p.descuento_porcentaje DESC, p.ventas_totales DESC, p.nombre ASC
        LIMIT {$limit}
    ";

    $resultado = $conn->query($sql);

    if (!$resultado) {
        error_log('Boticardo - Error al cargar ofertas: ' . $conn->error);
        return [];
    }

    $productos = [];

    while ($producto = $resultado->fetch_assoc()) {
        $precioActual = (float) ($producto['precio'] ?? 0);
        $precioOriginal = isset($producto['precio_original']) ? (float) $producto['precio_original'] : 0.0;
        $descuento = isset($producto['descuento_porcentaje']) ? (float) $producto['descuento_porcentaje'] : 0.0;

        if ($precioOriginal > $precioActual && $precioActual > 0 && $descuento <= 0) {
            $descuento = round((1 - ($precioActual / $precioOriginal)) * 100);
        }

        $producto['precio_original_calculado'] = $precioOriginal > $precioActual ? $precioOriginal : null;
        $producto['descuento_calculado'] = $descuento > 0 ? $descuento : null;
        $productos[] = $producto;
    }

    $resultado->free();

    return $productos;
}

function ofertaFechaFinVisible(?string $fecha): ?string
{
    if (!$fecha) {
        return null;
    }

    $timestamp = strtotime($fecha);
    if (!$timestamp) {
        return null;
    }

    return date('d/m/Y', $timestamp);
}

$ofertasPreparadas = ofertasSchemaDisponible($conn);
$productosOferta = getProductosEnOferta($conn);
$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content">
    <section class="offers-page" aria-label="Ofertas y promociones">
        <div class="container">
            <div class="offers-hero">
                <div class="offers-hero__content">
                    <span class="offers-hero__eyebrow">Promociones Boticardo</span>
                    <h1 class="offers-hero__title">Ofertas y promociones de farmacia</h1>
                    <p class="offers-hero__text">
                        Revisa los productos con promoción activa y aprovecha descuentos en cuidado diario,
                        higiene, dermocosmética, vitaminas y productos seleccionados de parafarmacia.
                    </p>
                    <div class="offers-hero__actions">
                        <a href="catalogo.php" class="btn btn--primary">Ver todo el catálogo</a>
                        <a href="contacto.php" class="btn btn--outline">Consultar una oferta</a>
                    </div>
                </div>

                <div class="offers-hero__panel" aria-label="Ventajas de compra">
                    <div class="offers-hero__saving">
                        <span class="offers-hero__saving-label">Ofertas activas</span>
                        <span class="offers-hero__saving-value"><?= count($productosOferta) ?></span>
                    </div>
                    <ul class="offers-hero__list" role="list">
                        <li>Pago seguro con tarjeta o Bizum.</li>
                        <li>Promociones leídas directamente desde productos.</li>
                        <li>No se muestran productos con receta en ofertas.</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="offers-section">
        <div class="container">
            <div class="section-header">
                <span class="section-header__eyebrow">Selección actual</span>
                <h2 class="section-header__title">Productos en oferta</h2>
                <p class="section-header__subtitle">
                    Las ofertas salen de la tabla <strong>productos</strong>: precio actual, precio anterior,
                    porcentaje de descuento y fechas de promoción.
                </p>
            </div>

            <?php if ($productosOferta): ?>
                <div class="offers-toolbar" aria-live="polite">
                    <p><?= count($productosOferta) ?> <?= count($productosOferta) === 1 ? 'oferta encontrada' : 'ofertas encontradas' ?></p>
                </div>

                <div class="products__grid offers-grid">
                    <?php foreach ($productosOferta as $producto): ?>
                        <?php
                        $productoId = (int) ($producto['id'] ?? 0);
                        $nombreProducto = (string) ($producto['nombre'] ?? 'Producto de farmacia');
                        $marcaProducto = (string) ($producto['marca'] ?: 'Boticardo');
                        $categoriaProducto = (string) ($producto['categoria'] ?: 'Farmacia');
                        $imagenProducto = basename((string) ($producto['imagen'] ?? 'placeholder.jpg'));
                        $precioNumero = (float) ($producto['precio'] ?? 0);
                        $precioVisible = number_format($precioNumero, 2, ',', '.');
                        $precioMaquina = number_format($precioNumero, 2, '.', '');
                        $precioOriginal = $producto['precio_original_calculado'] ?? null;
                        $precioOriginalVisible = $precioOriginal !== null ? number_format((float) $precioOriginal, 2, ',', '.') : null;
                        $descuento = $producto['descuento_calculado'] ?? null;
                        $etiquetaOferta = trim((string) ($producto['etiqueta_oferta'] ?? ''));
                        $descuentoVisible = $etiquetaOferta !== ''
                            ? $etiquetaOferta
                            : ($descuento !== null ? '-' . rtrim(rtrim(number_format((float) $descuento, 1, ',', '.'), '0'), ',') . '%' : 'Oferta');
                        $fechaFinVisible = ofertaFechaFinVisible($producto['oferta_fin'] ?? null);
                        $stockProducto = productoStock($producto);
                        $productoDisponible = $stockProducto > 0;
                        $stockLabel = productoStockLabel($producto);
                        $stockClass = productoStockClass($producto);
                        $productUrl = productoUrl($productoId);
                        ?>
                        <article class="product-card product-card--offer">
                            <div class="product-card__image-wrap">
                                <span class="product-card__offer-badge"><?= e($descuentoVisible) ?></span>
                                <button
                                    class="product-card__wishlist <?= favoritesHas($productoId) ? 'product-card__wishlist--active' : '' ?>"
                                    type="button"
                                    aria-label="<?= favoritesHas($productoId) ? 'Quitar' : 'Añadir' ?> <?= e($nombreProducto) ?> <?= favoritesHas($productoId) ? 'de' : 'a' ?> favoritos"
                                    aria-pressed="<?= favoritesHas($productoId) ? 'true' : 'false' ?>"
                                    data-favorite-product-id="<?= $productoId ?>"
                                    data-product-name="<?= e($nombreProducto) ?>"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
                                </button>
                                <a href="<?= e($productUrl) ?>" class="product-card__image-link" aria-label="Ver <?= e($nombreProducto) ?>">
                                    <img
                                        src="img/productos/<?= e($imagenProducto) ?>"
                                        alt="<?= e($nombreProducto) ?> de <?= e($marcaProducto) ?>"
                                        class="product-card__image"
                                        width="600"
                                        height="600"
                                        loading="lazy"
                                        decoding="async"
                                        onerror="this.onerror=null;this.src='img/productos/placeholder.jpg'"
                                    />
                                </a>
                            </div>
                            <div class="product-card__body">
                                <span class="product-card__brand"><?= e($marcaProducto) ?></span>
                                <h2 class="product-card__name"><a href="<?= e($productUrl) ?>" class="product-card__name-link"><?= e($nombreProducto) ?></a></h2>
                                <p class="product-card__category"><?= e($categoriaProducto) ?></p>
                                <div class="product-card__price-row">
                                    <?php if ($precioOriginalVisible !== null): ?>
                                        <span class="product-card__price-old"><?= e($precioOriginalVisible) ?> €</span>
                                    <?php endif; ?>
                                    <p class="product-card__price product-card__price--offer">
                                        <data value="<?= e($precioMaquina) ?>"><?= e($precioVisible) ?> €</data>
                                    </p>
                                </div>
                                <?php if ($fechaFinVisible !== null): ?>
                                    <p class="product-card__offer-date">Oferta válida hasta el <?= e($fechaFinVisible) ?></p>
                                <?php endif; ?>
                                <p class="product-card__stock <?= e($stockClass) ?>"><?= e($stockLabel) ?></p>
                            </div>
                            <div class="product-card__footer" style="padding: 0 0.95rem 0.95rem;">
                                <button
                                    class="product-card__add-btn"
                                    type="button"
                                    data-product-id="<?= $productoId ?>"
                                    data-product-name="<?= e($nombreProducto) ?>"
                                    <?= $productoDisponible ? '' : 'disabled aria-disabled="true"' ?>
                                >
                                    <?= $productoDisponible ? 'Añadir al carrito' : 'Agotado' ?>
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="offers-empty">
                    <div class="offers-empty__icon" aria-hidden="true">%</div>
                    <?php if (!$ofertasPreparadas): ?>
                        <h2>Falta preparar la tabla de productos</h2>
                        <p>
                            Ejecuta el archivo <strong>database/ofertas_productos.sql</strong> en phpMyAdmin.
                            Después aparecerán aquí los productos marcados con <strong>en_oferta = 1</strong>.
                        </p>
                    <?php else: ?>
                        <h2>Ahora mismo no hay ofertas activas</h2>
                        <p>
                            La página ya está preparada. Cuando marques productos en oferta desde la base de datos,
                            aparecerán automáticamente aquí.
                        </p>
                    <?php endif; ?>
                    <div class="offers-empty__actions">
                        <a href="catalogo.php" class="btn btn--primary">Ir al catálogo</a>
                        <a href="contacto.php" class="btn btn--outline">Preguntar por promociones</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="offers-info" aria-label="Información sobre promociones">
        <div class="container">
            <div class="offers-info__grid">
                <article class="offers-info__card">
                    <h2>Ofertas reales de BBDD</h2>
                    <p>Solo aparecen productos con <strong>en_oferta = 1</strong> y fechas de promoción activas.</p>
                </article>
                <article class="offers-info__card">
                    <h2>Precio coherente</h2>
                    <p>El carrito cobra el campo <strong>precio</strong>. El campo <strong>precio_original</strong> solo sirve para mostrar el precio tachado.</p>
                </article>
                <article class="offers-info__card">
                    <h2>Farmacia responsable</h2>
                    <p>Los productos marcados como receta no se muestran en esta sección de promociones.</p>
                </article>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
