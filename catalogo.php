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
                                <article class="product-card">
                                    <div class="product-card__image-wrap">
                                        <img src="img/productos/<?= e(basename($producto['imagen'])) ?>" alt="<?= e($producto['nombre']) ?>" class="product-card__image" loading="lazy">
                                    </div>
                                    <div class="product-card__body">
                                        <span class="product-card__brand"><?= e($producto['marca'] ?: 'Boticardo') ?></span>
                                        <h2 class="product-card__name"><?= e($producto['nombre']) ?></h2>
                                        <p class="product-card__price"><?= number_format((float)$producto['precio'], 2, ',', '.') ?> €</p>
                                    </div>
                                    <div class="product-card__footer" style="padding: 0 0.95rem 0.95rem;">
                                        <button class="product-card__add-btn" type="button" data-product-name="<?= e($producto['nombre']) ?>">
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
            const filterForm = document.getElementById('catalog-filter-form');

            if (!filterSidebar || !filterToggle || !filterForm) return;

            const filterToggleText = filterToggle.querySelector('.catalog-filter-toggle__text');
            const mobileQuery = window.matchMedia('(max-width: 768px)');

            function setFilterState(isOpen) {
                const shouldHideForm = mobileQuery.matches && !isOpen;

                filterSidebar.classList.toggle('catalog-sidebar--collapsed', shouldHideForm);
                filterForm.hidden = shouldHideForm;
                filterToggle.setAttribute('aria-expanded', String(!shouldHideForm));

                if (filterToggleText) {
                    filterToggleText.textContent = shouldHideForm ? 'Mostrar filtros' : 'Ocultar filtros';
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