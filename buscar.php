<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$query = trim((string) ($_GET['q'] ?? ''));
$productos = [];

if ($query !== '' && strlen(normalizeSearchText($query)) >= 2) {
    $productos = searchProductos($conn, $query, 60);
}

$conn->close();

$pageTitle = $query !== '' ? 'Buscar ' . $query . ' | Boticardo' : 'Buscar productos | Boticardo';
$pageDescription = 'Busca productos de farmacia, medicamentos, vitaminas, dermocosmética e higiene en Boticardo.';
$canonicalUrl = $siteUrl . '/buscar.php';

require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content">
    <section class="search-page">
        <div class="container">
            <div class="section-header search-page__header">
                <span class="section-header__eyebrow">Buscador</span>
                <h1 class="section-header__title">Buscar productos</h1>
                <p class="section-header__subtitle">
                    Escribe el nombre del producto o la marca. La búsqueda tolera pequeños errores de escritura.
                </p>
            </div>

            <form class="search-page__form" action="buscar.php" method="get" role="search">
                <label for="search-page-input" class="visually-hidden">Buscar productos</label>
                <input
                    id="search-page-input"
                    class="search-page__input"
                    type="search"
                    name="q"
                    value="<?= e($query) ?>"
                    placeholder="Ejemplo: ibuprofeno, cinfa, gelocatil..."
                    autocomplete="off"
                    minlength="2"
                >
                <button class="search-page__button" type="submit">Buscar</button>
            </form>

            <?php if ($query === ''): ?>
                <div class="search-empty" role="status">
                    <h2>Empieza escribiendo lo que necesitas</h2>
                    <p>Por ejemplo: <strong>ibuprofeno</strong>, <strong>vitamina C</strong>, <strong>ISDIN</strong> o <strong>sensodyne</strong>.</p>
                </div>
            <?php elseif (strlen(normalizeSearchText($query)) < 2): ?>
                <div class="search-empty" role="status">
                    <h2>La búsqueda es demasiado corta</h2>
                    <p>Escribe al menos 2 caracteres para encontrar productos.</p>
                </div>
            <?php elseif (!$productos): ?>
                <div class="search-empty" role="status">
                    <h2>No hemos encontrado productos para “<?= e($query) ?>”</h2>
                    <p>Prueba con otra palabra, una marca o una categoría parecida.</p>
                    <a href="catalogo.php" class="btn btn--outline">Ver catálogo completo</a>
                </div>
            <?php else: ?>
                <div class="search-results-header">
                    <div>
                        <h2>Resultados para “<?= e($query) ?>”</h2>
                        <p>
                            <?= count($productos) === 1 ? '1 producto encontrado' : count($productos) . ' productos encontrados' ?>.
                            También mostramos coincidencias aproximadas si hay errores de escritura.
                        </p>
                    </div>
                    <a href="catalogo.php" class="search-results-header__link">Ver todo el catálogo</a>
                </div>

                <div class="products__grid search-results-grid">
                    <?php foreach ($productos as $producto): ?>
                        <?php
                        $productoId = (int) ($producto['id'] ?? 0);
                        $nombreProducto = (string) ($producto['nombre'] ?? 'Producto de farmacia');
                        $marcaProducto = (string) ($producto['marca'] ?: 'Boticardo');
                        $imagenProducto = basename((string) ($producto['imagen'] ?? 'placeholder.jpg'));
                        $precioNumero = (float) ($producto['precio'] ?? 0);
                        $precioMaquina = number_format($precioNumero, 2, '.', '');
                        $precioVisible = number_format($precioNumero, 2, ',', '.');
                        ?>
                        <article class="product-card" aria-labelledby="producto-busqueda-<?= md5((string) $productoId . $nombreProducto) ?>">
                            <div class="product-card__image-wrap">
                                <img
                                    src="img/productos/<?= e($imagenProducto) ?>"
                                    alt="<?= e($nombreProducto) ?> de <?= e($marcaProducto) ?>"
                                    class="product-card__image"
                                    width="600"
                                    height="600"
                                    loading="lazy"
                                    decoding="async"
                                    onerror="this.onerror=null;this.src='img/productos/placeholder.jpg'"
                                >
                            </div>
                            <div class="product-card__body">
                                <span class="product-card__brand"><?= e($marcaProducto) ?></span>
                                <h2 class="product-card__name" id="producto-busqueda-<?= md5((string) $productoId . $nombreProducto) ?>"><?= e($nombreProducto) ?></h2>
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
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
