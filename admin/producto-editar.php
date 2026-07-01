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

$id = isset($_GET['id']) ? max(0, (int) $_GET['id']) : 0;
$product = $id > 0 ? adminFetchProduct($conn, $id) : null;
if ($id > 0 && !$product) {
    http_response_code(404);
    exit('Producto no encontrado.');
}

$errors = [];
$csrfToken = authCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!authValidateCsrf((string) ($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'La sesión ha caducado. Recarga la página y vuelve a intentarlo.';
    }

    $postId = max(0, (int) ($_POST['id'] ?? 0));
    $currentImage = (string) ($_POST['imagen_actual'] ?? ($product['imagen'] ?? 'placeholder.jpg'));
    $codigoSku = trim((string) ($_POST['codigo_sku'] ?? ''));
    $codigoNacional = trim((string) ($_POST['codigo_nacional'] ?? '')) ?: null;
    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $descripcion = trim((string) ($_POST['descripcion'] ?? '')) ?: null;
    $principioActivo = trim((string) ($_POST['principio_activo'] ?? '')) ?: null;
    $modoEmpleo = trim((string) ($_POST['modo_empleo'] ?? '')) ?: null;
    $advertencias = trim((string) ($_POST['advertencias'] ?? '')) ?: null;
    $contraindicaciones = trim((string) ($_POST['contraindicaciones'] ?? '')) ?: null;
    $conservacion = trim((string) ($_POST['conservacion'] ?? '')) ?: null;
    $precio = max(0, (float) str_replace(',', '.', (string) ($_POST['precio'] ?? '0')));
    $stock = max(0, (int) ($_POST['stock'] ?? 0));
    $ventasTotales = max(0, (int) ($_POST['ventas_totales'] ?? 0));
    $requiereReceta = !empty($_POST['requiere_receta']) ? 1 : 0;
    $categoriaId = (int) ($_POST['categoria_id'] ?? 0);
    $categoriaId = $categoriaId > 0 ? $categoriaId : null;
    $laboratorioId = (int) ($_POST['laboratorio_id'] ?? 0);
    $laboratorioId = $laboratorioId > 0 ? $laboratorioId : null;
    $enOferta = !empty($_POST['en_oferta']) ? 1 : 0;
    $precioOriginalRaw = trim((string) ($_POST['precio_original'] ?? ''));
    $precioOriginal = $precioOriginalRaw !== '' ? max(0, (float) str_replace(',', '.', $precioOriginalRaw)) : null;
    $descuentoRaw = trim((string) ($_POST['descuento_porcentaje'] ?? ''));
    $descuentoPorcentaje = $descuentoRaw !== '' ? max(0, (float) str_replace(',', '.', $descuentoRaw)) : null;
    $ofertaInicio = adminNormalizeDateTime($_POST['oferta_inicio'] ?? null);
    $ofertaFin = adminNormalizeDateTime($_POST['oferta_fin'] ?? null);
    $etiquetaOferta = trim((string) ($_POST['etiqueta_oferta'] ?? '')) ?: null;
    $destacarOferta = !empty($_POST['destacar_oferta']) ? 1 : 0;

    if ($codigoSku === '') {
        $errors[] = 'El SKU es obligatorio.';
    }
    if ($nombre === '') {
        $errors[] = 'El nombre del producto es obligatorio.';
    }
    if ($precio <= 0) {
        $errors[] = 'El precio debe ser mayor que cero.';
    }

    try {
        $imagen = adminHandleImageUpload('imagen', $currentImage, 'productos') ?: 'placeholder.jpg';
    } catch (Throwable $error) {
        $errors[] = $error->getMessage();
        $imagen = basename($currentImage) ?: 'placeholder.jpg';
    }

    if ($errors === []) {
        if ($postId > 0) {
            $stmt = $conn->prepare("
                UPDATE productos
                SET codigo_sku = ?, codigo_nacional = ?, nombre = ?, descripcion = ?, principio_activo = ?, modo_empleo = ?, advertencias = ?, contraindicaciones = ?, conservacion = ?, imagen = ?, precio = ?, stock = ?, ventas_totales = ?, requiere_receta = ?, categoria_id = ?, laboratorio_id = ?, en_oferta = ?, precio_original = ?, descuento_porcentaje = ?, oferta_inicio = ?, oferta_fin = ?, etiqueta_oferta = ?, destacar_oferta = ?
                WHERE id = ?
                LIMIT 1
            ");
            $stmt->bind_param(
                'ssssssssssdiiiiiiddsssii',
                $codigoSku,
                $codigoNacional,
                $nombre,
                $descripcion,
                $principioActivo,
                $modoEmpleo,
                $advertencias,
                $contraindicaciones,
                $conservacion,
                $imagen,
                $precio,
                $stock,
                $ventasTotales,
                $requiereReceta,
                $categoriaId,
                $laboratorioId,
                $enOferta,
                $precioOriginal,
                $descuentoPorcentaje,
                $ofertaInicio,
                $ofertaFin,
                $etiquetaOferta,
                $destacarOferta,
                $postId
            );
            $stmt->execute();
            $stmt->close();
            header('Location: producto-editar.php?id=' . $postId . '&guardado=1');
            exit;
        }

        $stmt = $conn->prepare("
            INSERT INTO productos (codigo_sku, codigo_nacional, nombre, descripcion, principio_activo, modo_empleo, advertencias, contraindicaciones, conservacion, imagen, precio, stock, ventas_totales, requiere_receta, categoria_id, laboratorio_id, en_oferta, precio_original, descuento_porcentaje, oferta_inicio, oferta_fin, etiqueta_oferta, destacar_oferta)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            'ssssssssssdiiiiiiddsssi',
            $codigoSku,
            $codigoNacional,
            $nombre,
            $descripcion,
            $principioActivo,
            $modoEmpleo,
            $advertencias,
            $contraindicaciones,
            $conservacion,
            $imagen,
            $precio,
            $stock,
            $ventasTotales,
            $requiereReceta,
            $categoriaId,
            $laboratorioId,
            $enOferta,
            $precioOriginal,
            $descuentoPorcentaje,
            $ofertaInicio,
            $ofertaFin,
            $etiquetaOferta,
            $destacarOferta
        );
        $stmt->execute();
        $newId = (int) $stmt->insert_id;
        $stmt->close();
        header('Location: producto-editar.php?id=' . $newId . '&guardado=1');
        exit;
    }

    $product = array_merge($product ?? [], $_POST, ['imagen' => $imagen]);
    $id = $postId;
}

$categorias = adminFetchCategorias($conn);
$laboratorios = adminFetchLaboratorios($conn);
$isEditing = $id > 0;

adminRenderHeader($isEditing ? 'Editar producto' : 'Nuevo producto', 'productos');
?>
<section class="admin-page-header">
    <div>
        <p class="admin-eyebrow">Catálogo</p>
        <h1><?= $isEditing ? 'Editar producto' : 'Nuevo producto' ?></h1>
        <p>Completa los datos del producto, stock, oferta e información farmacéutica.</p>
    </div>
    <a href="productos.php" class="btn btn--outline">Volver a productos</a>
</section>

<?php if (isset($_GET['guardado'])): ?>
    <div class="admin-message admin-message--success">Producto guardado correctamente.</div>
<?php endif; ?>
<?php foreach ($errors as $error): ?>
    <div class="admin-message admin-message--error"><?= e($error) ?></div>
<?php endforeach; ?>

<form class="admin-detail-grid" action="producto-editar.php<?= $isEditing ? '?id=' . (int) $id : '' ?>" method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="id" value="<?= (int) $id ?>">
    <input type="hidden" name="imagen_actual" value="<?= e((string) ($product['imagen'] ?? 'placeholder.jpg')) ?>">

    <section class="admin-card">
        <div class="admin-card__header"><h2>Datos principales</h2></div>
        <div class="admin-form admin-form-grid">
            <label>SKU
                <input type="text" name="codigo_sku" value="<?= e((string) ($product['codigo_sku'] ?? '')) ?>" required maxlength="50">
            </label>
            <label>Código nacional
                <input type="text" name="codigo_nacional" value="<?= e((string) ($product['codigo_nacional'] ?? '')) ?>" maxlength="20">
            </label>
            <label class="admin-field--full">Nombre
                <input type="text" name="nombre" value="<?= e((string) ($product['nombre'] ?? '')) ?>" required maxlength="150">
            </label>
            <label>Categoría
                <select name="categoria_id">
                    <option value="0">Sin categoría</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?= (int) $categoria['id'] ?>" <?= (int) ($product['categoria_id'] ?? 0) === (int) $categoria['id'] ? 'selected' : '' ?>><?= e((string) $categoria['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Laboratorio / marca
                <select name="laboratorio_id">
                    <option value="0">Sin laboratorio</option>
                    <?php foreach ($laboratorios as $laboratorio): ?>
                        <option value="<?= (int) $laboratorio['id'] ?>" <?= (int) ($product['laboratorio_id'] ?? 0) === (int) $laboratorio['id'] ? 'selected' : '' ?>><?= e((string) $laboratorio['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Precio actual
                <input type="number" name="precio" value="<?= e((string) ($product['precio'] ?? '0.00')) ?>" required min="0" step="0.01">
            </label>
            <label>Stock disponible
                <input type="number" name="stock" value="<?= (int) ($product['stock'] ?? 0) ?>" min="0" step="1">
            </label>
            <label>Ventas totales
                <input type="number" name="ventas_totales" value="<?= (int) ($product['ventas_totales'] ?? 0) ?>" min="0" step="1">
            </label>
            <label class="admin-check-row">
                <input type="checkbox" name="requiere_receta" value="1" <?= !empty($product['requiere_receta']) ? 'checked' : '' ?>>
                Requiere receta
            </label>
            <label class="admin-field--full">Descripción
                <textarea name="descripcion" rows="4"><?= e((string) ($product['descripcion'] ?? '')) ?></textarea>
            </label>
        </div>

        <div class="admin-card__header admin-card__header--spaced"><h2>Información farmacéutica</h2></div>
        <div class="admin-form">
            <label>Principio activo
                <input type="text" name="principio_activo" value="<?= e((string) ($product['principio_activo'] ?? '')) ?>" maxlength="190">
            </label>
            <label>Modo de empleo
                <textarea name="modo_empleo" rows="3"><?= e((string) ($product['modo_empleo'] ?? '')) ?></textarea>
            </label>
            <label>Advertencias
                <textarea name="advertencias" rows="3"><?= e((string) ($product['advertencias'] ?? '')) ?></textarea>
            </label>
            <label>Contraindicaciones
                <textarea name="contraindicaciones" rows="3"><?= e((string) ($product['contraindicaciones'] ?? '')) ?></textarea>
            </label>
            <label>Conservación
                <textarea name="conservacion" rows="3"><?= e((string) ($product['conservacion'] ?? '')) ?></textarea>
            </label>
        </div>
    </section>

    <aside class="admin-card admin-card--side">
        <div class="admin-card__header"><h2>Imagen y oferta</h2></div>
        <div class="admin-form">
            <?php $image = basename((string) ($product['imagen'] ?? 'placeholder.jpg')); ?>
            <div class="admin-image-preview">
                <img src="../img/productos/<?= e($image) ?>" alt="" onerror="this.onerror=null;this.src='../img/productos/placeholder.jpg'">
            </div>
            <label>Nueva imagen
                <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp">
            </label>
            <p class="admin-help-text">Formatos permitidos: JPG, PNG o WEBP. Máximo 3 MB.</p>

            <label class="admin-check-row">
                <input type="checkbox" name="en_oferta" value="1" <?= !empty($product['en_oferta']) ? 'checked' : '' ?>>
                Producto en oferta
            </label>
            <label>Precio original
                <input type="number" name="precio_original" value="<?= e((string) ($product['precio_original'] ?? '')) ?>" min="0" step="0.01" placeholder="Precio tachado">
            </label>
            <label>Descuento %
                <input type="number" name="descuento_porcentaje" value="<?= e((string) ($product['descuento_porcentaje'] ?? '')) ?>" min="0" step="0.01">
            </label>
            <label>Etiqueta
                <input type="text" name="etiqueta_oferta" value="<?= e((string) ($product['etiqueta_oferta'] ?? '')) ?>" maxlength="80" placeholder="Ej. -20%">
            </label>
            <label>Inicio oferta
                <input type="datetime-local" name="oferta_inicio" value="<?= e(adminDateTimeInput($product['oferta_inicio'] ?? null)) ?>">
            </label>
            <label>Fin oferta
                <input type="datetime-local" name="oferta_fin" value="<?= e(adminDateTimeInput($product['oferta_fin'] ?? null)) ?>">
            </label>
            <label class="admin-check-row">
                <input type="checkbox" name="destacar_oferta" value="1" <?= !empty($product['destacar_oferta']) ? 'checked' : '' ?>>
                Destacar en ofertas
            </label>
            <button class="btn btn--primary" type="submit">Guardar producto</button>
        </div>
    </aside>
</form>
<?php adminRenderFooter(); ?>
