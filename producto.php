<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/favorites.php';

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$producto = getProductoDetalleById($conn, $productId);

if (!$producto) {
    http_response_code(404);
    $pageTitle = 'Producto no encontrado | Boticardo';
    $pageDescription = 'No hemos encontrado el producto solicitado en Boticardo.';
    $canonicalUrl = APP_BASE_URL . '/producto.php';
    require_once __DIR__ . '/includes/schema.php';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <main id="main-content">
        <section class="product-detail-page">
            <div class="container">
                <div class="product-not-found">
                    <div class="product-not-found__icon" aria-hidden="true">?</div>
                    <h1>Producto no encontrado</h1>
                    <p>Puede que el producto ya no esté disponible o que el enlace no sea correcto.</p>
                    <div class="product-not-found__actions">
                        <a href="catalogo.php" class="btn btn--primary">Ver catálogo</a>
                        <a href="index.php" class="btn btn--outline">Volver al inicio</a>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    $conn->close();
    exit;
}

$productoId = (int) ($producto['id'] ?? 0);
$nombreProducto = (string) ($producto['nombre'] ?? 'Producto de farmacia');
$marcaProducto = (string) ($producto['marca'] ?: 'Boticardo');
$descripcionBbdd = trim((string) ($producto['descripcion'] ?? ''));
$descripcionFallback = $nombreProducto . ' es un producto de ' . $marcaProducto . ' disponible en nuestro catálogo online. La información mostrada es orientativa; ante cualquier duda, consulta con nuestro equipo farmacéutico.';
$descripcionProducto = $descripcionBbdd !== '' ? $descripcionBbdd : $descripcionFallback;
$resumenProducto = mb_strlen($descripcionProducto, 'UTF-8') > 220
    ? mb_substr($descripcionProducto, 0, 220, 'UTF-8') . '...'
    : $descripcionProducto;
$imagenProducto = basename((string) ($producto['imagen'] ?? 'placeholder.jpg'));
$imagenProductoPath = 'img/productos/' . $imagenProducto;
$precioNumero = (float) ($producto['precio'] ?? 0);
$precioMaquina = number_format($precioNumero, 2, '.', '');
$precioVisible = number_format($precioNumero, 2, ',', '.');
$stockProducto = productoStock($producto);
$productoDisponible = $stockProducto > 0;
$stockLabel = productoStockLabel($producto);
$stockClass = productoStockClass($producto);
$stockMaxCompra = max(1, min(99, $stockProducto));
$categoriaId = isset($producto['categoria_id']) ? (int) $producto['categoria_id'] : null;
$categoriaProducto = trim((string) ($producto['categoria_nombre'] ?? '')) ?: 'Sin categoría';
$codigoNacional = trim((string) ($producto['codigo_nacional'] ?? '')) ?: (trim((string) ($producto['codigo_sku'] ?? '')) ?: 'No indicado');
$principioActivo = trim((string) ($producto['principio_activo'] ?? '')) ?: 'No aplica / no indicado';
$modoEmpleo = trim((string) ($producto['modo_empleo'] ?? '')) ?: 'Información no disponible. Consulta siempre el prospecto o al equipo farmacéutico si tienes dudas.';
$advertencias = trim((string) ($producto['advertencias'] ?? '')) ?: 'Información no disponible. Lee el prospecto antes de utilizar el producto.';
$contraindicaciones = trim((string) ($producto['contraindicaciones'] ?? '')) ?: 'Información no disponible. Consulta con un profesional sanitario si estás embarazada, en tratamiento o tienes patologías previas.';
$conservacion = trim((string) ($producto['conservacion'] ?? '')) ?: 'Conservar en lugar fresco y seco, alejado de la luz directa y fuera del alcance de los niños, salvo indicación diferente del envase.';
$stockDisponibleDetalle = $productoDisponible ? $stockProducto . ' unidades disponibles' : 'Agotado temporalmente';
$productInfoText = static function (?string $value): string {
    return nl2br(e(trim((string) $value)));
};
$productosRelacionados = getProductosRelacionados($conn, $productoId, $categoriaId, 4);

$pageTitle = $nombreProducto . ' | Boticardo';
$pageDescription = mb_strlen(strip_tags($descripcionProducto), 'UTF-8') > 155
    ? mb_substr(strip_tags($descripcionProducto), 0, 155, 'UTF-8') . '...'
    : strip_tags($descripcionProducto);
$canonicalUrl = APP_BASE_URL . '/producto.php?id=' . $productoId;

if (is_file(__DIR__ . '/' . $imagenProductoPath)) {
    $socialImageExists = true;
    $socialImageUrl = APP_BASE_URL . '/' . $imagenProductoPath;
}

require_once __DIR__ . '/includes/schema.php';

$structuredData['@graph'][] = [
    '@type' => 'Product',
    '@id' => $canonicalUrl . '#product',
    'name' => $nombreProducto,
    'brand' => [
        '@type' => 'Brand',
        'name' => $marcaProducto,
    ],
    'image' => APP_BASE_URL . '/' . $imagenProductoPath,
    'description' => strip_tags($descripcionProducto),
    'sku' => $codigoNacional,
    'category' => $categoriaProducto,
    'offers' => [
        '@type' => 'Offer',
        'url' => $canonicalUrl,
        'priceCurrency' => 'EUR',
        'price' => $precioMaquina,
        'availability' => $productoDisponible ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        'seller' => ['@id' => $siteUrl . '/#pharmacy'],
    ],
];

require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content">
    <section class="product-detail-page" aria-labelledby="product-detail-title">
        <div class="container">
            <nav class="product-breadcrumb" aria-label="Migas de pan">
                <a href="index.php">Inicio</a>
                <span aria-hidden="true">/</span>
                <a href="catalogo.php">Catálogo</a>
                <span aria-hidden="true">/</span>
                <span><?= e($nombreProducto) ?></span>
            </nav>

            <article class="product-detail">
                <div class="product-detail__media">
                    <div class="product-detail__image-card">
                        <button
                            class="product-card__wishlist product-detail__favorite <?= favoritesHas($productoId) ? 'product-card__wishlist--active' : '' ?>"
                            type="button"
                            aria-label="<?= favoritesHas($productoId) ? 'Quitar' : 'Añadir' ?> <?= e($nombreProducto) ?> <?= favoritesHas($productoId) ? 'de' : 'a' ?> favoritos"
                            aria-pressed="<?= favoritesHas($productoId) ? 'true' : 'false' ?>"
                            data-favorite-product-id="<?= $productoId ?>"
                            data-product-name="<?= e($nombreProducto) ?>"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
                        </button>
                        <img
                            src="<?= e($imagenProductoPath) ?>"
                            alt="<?= e($nombreProducto) ?> de <?= e($marcaProducto) ?>"
                            class="product-detail__image"
                            width="900"
                            height="900"
                            fetchpriority="high"
                            decoding="async"
                            onerror="this.onerror=null;this.src='img/productos/placeholder.jpg'"
                        />
                    </div>
                </div>

                <div class="product-detail__info">
                    <span class="product-detail__brand"><?= e($marcaProducto) ?></span>
                    <h1 class="product-detail__title" id="product-detail-title"><?= e($nombreProducto) ?></h1>

                    <div class="product-detail__price-row">
                        <data class="product-detail__price" value="<?= e($precioMaquina) ?>"><?= e($precioVisible) ?> €</data>
                        <span class="product-detail__tax">IVA incluido</span>
                    </div>

                    <p class="product-detail__stock <?= e($stockClass) ?>">
                        <?= e($stockLabel) ?>
                        <?php if ($stockProducto > 5): ?>
                            <span>Stock disponible: <?= $stockProducto ?></span>
                        <?php endif; ?>
                    </p>

                    <p class="product-detail__summary">
                        <?= e($resumenProducto) ?>
                    </p>

                    <ul class="product-detail__benefits" role="list">
                        <li>
                            <span aria-hidden="true">✓</span>
                            Envío gratis en pedidos superiores a 39€
                        </li>
                        <li>
                            <span aria-hidden="true">✓</span>
                            Pago seguro con tarjeta bancaria y Bizum
                        </li>
                        <li>
                            <span aria-hidden="true">✓</span>
                            Preparado por la farmacia Boticardo
                        </li>
                    </ul>

                    <div class="product-detail__buy-box">
                        <label class="product-detail__quantity" for="product-quantity">
                            <span>Cantidad</span>
                            <input id="product-quantity" type="number" min="1" max="<?= $stockMaxCompra ?>" value="1" inputmode="numeric" <?= $productoDisponible ? '' : 'disabled' ?> />
                        </label>

                        <button
                            class="product-card__add-btn product-detail__add-btn"
                            type="button"
                            aria-label="Añadir <?= e($nombreProducto) ?> al carrito"
                            data-product-id="<?= $productoId ?>"
                            data-product-name="<?= e($nombreProducto) ?>"
                            data-quantity-input="product-quantity"
                            <?= $productoDisponible ? '' : 'disabled aria-disabled="true"' ?>
                        >
                            <?php if ($productoDisponible): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
                            Añadir al carrito
                            <?php else: ?>
                            Agotado temporalmente
                            <?php endif; ?>
                        </button>
                    </div>

                    <div class="product-detail__secure-note">
                        <strong>Compra segura</strong>
                        <span>El pago se realiza en una pasarela externa segura.</span>
                    </div>
                </div>
            </article>

            <section class="product-pharma-info" aria-labelledby="product-pharma-info-title">
                <div class="section-header">
                    <span class="section-header__eyebrow">Ficha farmacéutica</span>
                    <h2 class="section-header__title" id="product-pharma-info-title">Información del producto</h2>
                    <p class="section-header__description">Datos útiles para identificar el producto y usarlo correctamente.</p>
                </div>

                <div class="product-pharma-info__grid">
                    <article class="product-pharma-info__card">
                        <span class="product-pharma-info__label">Código nacional</span>
                        <p class="product-pharma-info__value"><?= e($codigoNacional) ?></p>
                    </article>

                    <article class="product-pharma-info__card">
                        <span class="product-pharma-info__label">Marca / laboratorio</span>
                        <p class="product-pharma-info__value"><?= e($marcaProducto) ?></p>
                    </article>

                    <article class="product-pharma-info__card">
                        <span class="product-pharma-info__label">Categoría</span>
                        <p class="product-pharma-info__value"><?= e($categoriaProducto) ?></p>
                    </article>

                    <article class="product-pharma-info__card">
                        <span class="product-pharma-info__label">Stock disponible</span>
                        <p class="product-pharma-info__value <?= e($stockClass) ?>"><?= e($stockDisponibleDetalle) ?></p>
                    </article>

                    <article class="product-pharma-info__card product-pharma-info__card--wide">
                        <span class="product-pharma-info__label">Principio activo</span>
                        <p class="product-pharma-info__value"><?= $productInfoText($principioActivo) ?></p>
                    </article>

                    <article class="product-pharma-info__card product-pharma-info__card--wide">
                        <span class="product-pharma-info__label">Modo de empleo</span>
                        <p class="product-pharma-info__value"><?= $productInfoText($modoEmpleo) ?></p>
                    </article>

                    <article class="product-pharma-info__card product-pharma-info__card--wide">
                        <span class="product-pharma-info__label">Advertencias</span>
                        <p class="product-pharma-info__value"><?= $productInfoText($advertencias) ?></p>
                    </article>

                    <article class="product-pharma-info__card product-pharma-info__card--wide">
                        <span class="product-pharma-info__label">Contraindicaciones</span>
                        <p class="product-pharma-info__value"><?= $productInfoText($contraindicaciones) ?></p>
                    </article>

                    <article class="product-pharma-info__card product-pharma-info__card--wide">
                        <span class="product-pharma-info__label">Conservación</span>
                        <p class="product-pharma-info__value"><?= $productInfoText($conservacion) ?></p>
                    </article>
                </div>

                <p class="product-pharma-info__note">
                    Esta información es orientativa y no sustituye el consejo farmacéutico ni la lectura del prospecto o etiquetado del producto.
                </p>
            </section>

            <?php if ($productosRelacionados): ?>
                <section class="products product-related" aria-labelledby="related-products-title">
                    <div class="section-header">
                        <span class="section-header__eyebrow">También te puede interesar</span>
                        <h2 class="section-header__title" id="related-products-title">Productos relacionados</h2>
                    </div>

                    <div class="products__grid">
                        <?php foreach ($productosRelacionados as $relacionado): ?>
                            <?php
                            $relacionadoId = (int) ($relacionado['id'] ?? 0);
                            $relacionadoNombre = (string) ($relacionado['nombre'] ?? 'Producto de farmacia');
                            $relacionadoMarca = (string) ($relacionado['marca'] ?: 'Boticardo');
                            $relacionadoImagen = basename((string) ($relacionado['imagen'] ?? 'placeholder.jpg'));
                            $relacionadoPrecioNumero = (float) ($relacionado['precio'] ?? 0);
                            $relacionadoPrecioMaquina = number_format($relacionadoPrecioNumero, 2, '.', '');
                            $relacionadoPrecioVisible = number_format($relacionadoPrecioNumero, 2, ',', '.');
                            $relacionadoStock = productoStock($relacionado);
                            $relacionadoDisponible = $relacionadoStock > 0;
                            $relacionadoStockLabel = productoStockLabel($relacionado);
                            $relacionadoStockClass = productoStockClass($relacionado);
                            $relacionadoUrl = productoUrl($relacionadoId);
                            ?>
                            <article class="product-card" aria-labelledby="relacionado-<?= md5((string) $relacionadoId . $relacionadoNombre) ?>">
                                <div class="product-card__image-wrap">
                                    <button
                                        class="product-card__wishlist <?= favoritesHas($relacionadoId) ? 'product-card__wishlist--active' : '' ?>"
                                        type="button"
                                        aria-label="<?= favoritesHas($relacionadoId) ? 'Quitar' : 'Añadir' ?> <?= e($relacionadoNombre) ?> <?= favoritesHas($relacionadoId) ? 'de' : 'a' ?> favoritos"
                                        aria-pressed="<?= favoritesHas($relacionadoId) ? 'true' : 'false' ?>"
                                        data-favorite-product-id="<?= $relacionadoId ?>"
                                        data-product-name="<?= e($relacionadoNombre) ?>"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
                                    </button>
                                    <a href="<?= e($relacionadoUrl) ?>" class="product-card__image-link" aria-label="Ver <?= e($relacionadoNombre) ?>">
                                        <img
                                            src="img/productos/<?= e($relacionadoImagen) ?>"
                                            alt="<?= e($relacionadoNombre) ?> de <?= e($relacionadoMarca) ?>"
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
                                    <span class="product-card__brand"><?= e($relacionadoMarca) ?></span>
                                    <h3 class="product-card__name" id="relacionado-<?= md5((string) $relacionadoId . $relacionadoNombre) ?>">
                                        <a href="<?= e($relacionadoUrl) ?>" class="product-card__name-link"><?= e($relacionadoNombre) ?></a>
                                    </h3>
                                    <div class="product-card__pricing">
                                        <data class="product-card__price" value="<?= e($relacionadoPrecioMaquina) ?>"><?= e($relacionadoPrecioVisible) ?> €</data>
                                    </div>
                                    <p class="product-card__stock <?= e($relacionadoStockClass) ?>"><?= e($relacionadoStockLabel) ?></p>
                                </div>
                                <div class="product-card__footer">
                                    <button
                                        class="product-card__add-btn"
                                        type="button"
                                        aria-label="Añadir <?= e($relacionadoNombre) ?> al carrito"
                                        data-product-id="<?= $relacionadoId ?>"
                                        data-product-name="<?= e($relacionadoNombre) ?>"
                                        <?= $relacionadoDisponible ? '' : 'disabled aria-disabled="true"' ?>
                                    >
                                        <?= $relacionadoDisponible ? 'Añadir al carrito' : 'Agotado' ?>
                                    </button>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
$conn->close();
require_once __DIR__ . '/includes/footer.php';
?>
