<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/favorites.php';

$pageTitle = 'Mis favoritos | Boticardo';
$pageDescription = 'Productos guardados como favoritos en Boticardo.';
$productosFavoritos = favoritesProducts($conn);
$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content">
    <section class="products favorites-page" aria-labelledby="favorites-title">
        <div class="container">
            <div class="section-header">
                <span class="section-header__eyebrow">Tus favoritos</span>
                <h1 class="section-header__title" id="favorites-title">Productos favoritos</h1>
                <p class="section-header__subtitle">Aquí aparecen los productos que has marcado con el corazón.</p>
            </div>

            <?php if ($productosFavoritos): ?>
                <div class="products__grid">
                    <?php foreach ($productosFavoritos as $producto): ?>
                        <?php
                        $productoId = (int) ($producto['id'] ?? 0);
                        $nombreProducto = (string) ($producto['nombre'] ?? 'Producto de farmacia');
                        $marcaProducto = (string) ($producto['marca'] ?: 'Boticardo');
                        $imagenProducto = basename((string) ($producto['imagen'] ?? 'placeholder.jpg'));
                        $precioNumero = (float) ($producto['precio'] ?? 0);
                        $precioMaquina = number_format($precioNumero, 2, '.', '');
                        $precioVisible = number_format($precioNumero, 2, ',', '.');
                        $productUrl = productoUrl($productoId);
                        ?>
                        <article class="product-card" aria-labelledby="favorito-<?= md5((string) $productoId . $nombreProducto) ?>">
                            <div class="product-card__image-wrap">
                                <button
                                    class="product-card__wishlist product-card__wishlist--active"
                                    type="button"
                                    aria-label="Quitar <?= e($nombreProducto) ?> de favoritos"
                                    aria-pressed="true"
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
                                <h2 class="product-card__name" id="favorito-<?= md5((string) $productoId . $nombreProducto) ?>"><a href="<?= e($productUrl) ?>" class="product-card__name-link"><?= e($nombreProducto) ?></a></h2>
                                <div class="product-card__pricing">
                                    <data class="product-card__price" value="<?= e($precioMaquina) ?>"><?= e($precioVisible) ?> €</data>
                                </div>
                            </div>
                            <div class="product-card__footer">
                                <button
                                    class="product-card__add-btn"
                                    type="button"
                                    aria-label="Añadir <?= e($nombreProducto) ?> al carrito"
                                    data-product-id="<?= $productoId ?>"
                                    data-product-name="<?= e($nombreProducto) ?>"
                                >
                                    Añadir al carrito
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="favorites-empty">
                    <h2>Todavía no tienes favoritos</h2>
                    <p>Pulsa el corazón de cualquier producto para guardarlo aquí.</p>
                    <a href="catalogo.php" class="btn btn--primary">Ver productos</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
