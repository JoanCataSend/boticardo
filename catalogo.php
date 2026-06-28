<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/schema.php';

$categoria_id = isset($_GET['categoria']) && $_GET['categoria'] !== '' ? (int) $_GET['categoria'] : null;
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float) $_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float) $_GET['max_price'] : null;
$marca = isset($_GET['marca']) && $_GET['marca'] !== '' ? $_GET['marca'] : null;

$categoriasInfo = [
        1 => ['nombre' => 'Medicamentos', 'desc' => 'Medicamentos generales, analgésicos, antibióticos...'],
        2 => ['nombre' => 'Dermocosmética', 'desc' => 'Productos para el cuidado de la piel y cosméticos.'],
        3 => ['nombre' => 'Vitaminas', 'desc' => 'Complejos vitamínicos y suplementos nutricionales.'],
        4 => ['nombre' => 'Bebé y Mamá', 'desc' => 'Productos para el cuidado del bebé, alimentación infantil y maternidad.'],
        5 => ['nombre' => 'Higiene', 'desc' => 'Artículos para la higiene personal diaria: dental, corporal, íntima.']
];

$tituloSeccion = 'Todos los productos';
$descSeccion = 'Encuentra todo lo que necesitas para tu salud y bienestar, organizado alfabéticamente.';
$pageTitle = 'Catálogo de productos | Boticardo';

if ($categoria_id && isset($categoriasInfo[$categoria_id])) {
    $tituloSeccion = $categoriasInfo[$categoria_id]['nombre'];
    $descSeccion = $categoriasInfo[$categoria_id]['desc'];
    $pageTitle = $tituloSeccion . ' | Boticardo';
}

$todosLosProductos = getAllProductos($conn, $categoria_id, $min_price, $max_price, $marca);
$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

    <main id="main-content">
        <section class="catalog-page" style="padding-top: 3rem; padding-bottom: 4rem;">
            <div class="container">
                <div class="section-header">
                    <h1 class="section-header__title"><?= e($tituloSeccion) ?></h1>
                    <p class="section-header__subtitle"><?= e($descSeccion) ?></p>
                </div>

                <div class="catalog-layout">
                    <aside class="catalog-sidebar" id="catalog-filters">
                        <button
                            class="catalog-filter-toggle"
                            id="catalog-filter-toggle"
                            type="button"
                            aria-expanded="true"
                            aria-controls="catalog-filter-form"
                        >
                            <span class="catalog-filter-toggle__text">Ocultar filtros</span>
                            <span class="catalog-filter-toggle__icon" aria-hidden="true">▾</span>
                        </button>

                        <?php require __DIR__ . '/includes/filtro_form.php'; ?>
                    </aside>

                    <div class="catalog-results">
                        <div class="products__grid">
                            <?php if ($todosLosProductos): foreach ($todosLosProductos as $producto): ?>
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
                                <article class="product-card">
                                    <div class="product-card__image-wrap">
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
                                        <p class="product-card__price"><?= e($precioVisible) ?> €</p>
                                    </div>
                                    <div class="product-card__footer" style="padding: 0 0.95rem 0.95rem;">
                                        <button
                                            class="product-card__add-btn"
                                            type="button"
                                            data-product-id="<?= $productoId ?>"
                                            data-product-name="<?= e($nombreProducto) ?>"
                                        >
                                            Añadir al carrito
                                        </button>
                                    </div>
                                </article>
                            <?php endforeach; else: ?>
                                <p>No se encontraron productos.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filterSidebar = document.getElementById('catalog-filters');
            const filterToggle = document.getElementById('catalog-filter-toggle');

            if (!filterSidebar || !filterToggle) return;

            const filterToggleText = filterToggle.querySelector('.catalog-filter-toggle__text');
            const mobileQuery = window.matchMedia('(max-width: 768px)');

            function setFilterState(isOpen) {
                filterSidebar.classList.toggle('catalog-sidebar--collapsed', !isOpen);
                filterToggle.setAttribute('aria-expanded', String(isOpen));

                if (filterToggleText) {
                    filterToggleText.textContent = isOpen ? 'Ocultar filtros' : 'Mostrar filtros';
                }
            }

            function syncFilterForViewport() {
                setFilterState(!mobileQuery.matches);
            }

            filterToggle.addEventListener('click', function () {
                const isOpen = filterToggle.getAttribute('aria-expanded') === 'true';
                setFilterState(!isOpen);
            });

            syncFilterForViewport();

            if (typeof mobileQuery.addEventListener === 'function') {
                mobileQuery.addEventListener('change', syncFilterForViewport);
            } else if (typeof mobileQuery.addListener === 'function') {
                mobileQuery.addListener(syncFilterForViewport);
            }
        });
    </script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>