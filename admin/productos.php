<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/cart.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin.php';
require_once __DIR__ . '/../includes/admin_catalog.php';

adminRequire();
adminCatalogEnsure($conn);

$q = trim((string) ($_GET['q'] ?? ''));
$categoriaId = isset($_GET['categoria']) ? max(0, (int) $_GET['categoria']) : null;
$stockFilter = isset($_GET['stock']) ? (string) $_GET['stock'] : null;
if (!in_array($stockFilter, ['agotados', 'bajo', 'ofertas'], true)) {
    $stockFilter = null;
}

$products = adminFetchProducts($conn, $q !== '' ? $q : null, $categoriaId, $stockFilter);
$categorias = adminFetchCategorias($conn);

adminRenderHeader('Productos', 'productos');
?>
<section class="admin-page-header">
    <div>
        <p class="admin-eyebrow">Catálogo</p>
        <h1>Productos</h1>
        <p>Gestiona productos, imágenes, precios, stock, ofertas e información farmacéutica.</p>
    </div>
    <a href="producto-editar.php" class="btn btn--primary">Añadir producto</a>
</section>

<section class="admin-card">
    <form class="admin-filter-form" action="productos.php" method="get">
        <label>
            Buscar
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Nombre, SKU o código nacional">
        </label>
        <label>
            Categoría
            <select name="categoria">
                <option value="0">Todas</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?= (int) $categoria['id'] ?>" <?= (int) $categoria['id'] === (int) $categoriaId ? 'selected' : '' ?>><?= e((string) $categoria['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Filtro
            <select name="stock">
                <option value="">Todos</option>
                <option value="agotados" <?= $stockFilter === 'agotados' ? 'selected' : '' ?>>Agotados</option>
                <option value="bajo" <?= $stockFilter === 'bajo' ? 'selected' : '' ?>>Stock bajo</option>
                <option value="ofertas" <?= $stockFilter === 'ofertas' ? 'selected' : '' ?>>En oferta</option>
            </select>
        </label>
        <button class="btn btn--outline" type="submit">Filtrar</button>
        <a href="productos.php" class="admin-table__action">Limpiar</a>
    </form>
</section>

<section class="admin-card">
    <div class="admin-card__header">
        <h2>Listado de productos</h2>
        <span><?= count($products) ?> resultado<?= count($products) === 1 ? '' : 's' ?></span>
    </div>

    <?php if ($products === []): ?>
        <p class="admin-empty">No hay productos con estos filtros.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table admin-table--products">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Laboratorio</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Oferta</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <?php
                        $image = basename((string) ($product['imagen'] ?? 'placeholder.jpg'));
                        $stock = (int) ($product['stock'] ?? 0);
                        ?>
                        <tr>
                            <td>
                                <div class="admin-product-cell">
                                    <img src="../img/productos/<?= e($image) ?>" alt="" loading="lazy" onerror="this.onerror=null;this.src='../img/productos/placeholder.jpg'">
                                    <div>
                                        <strong><?= e((string) $product['nombre']) ?></strong><br>
                                        <small>SKU: <?= e((string) $product['codigo_sku']) ?> · CN: <?= e((string) ($product['codigo_nacional'] ?? '—')) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?= e((string) ($product['categoria_nombre'] ?? '—')) ?></td>
                            <td><?= e((string) ($product['laboratorio_nombre'] ?? '—')) ?></td>
                            <td><?= e(adminFormatMoney((float) $product['precio'])) ?></td>
                            <td><span class="admin-stock-badge <?= $stock <= 0 ? 'admin-stock-badge--empty' : ($stock <= 5 ? 'admin-stock-badge--low' : '') ?>"><?= $stock ?></span></td>
                            <td><?= ((int) ($product['en_oferta'] ?? 0) === 1) ? 'Sí' : 'No' ?></td>
                            <td><a href="producto-editar.php?id=<?= (int) $product['id'] ?>" class="admin-table__action">Editar</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php adminRenderFooter(); ?>
