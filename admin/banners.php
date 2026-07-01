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
$csrfToken = authCsrfToken();
$messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!authValidateCsrf((string) ($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'La sesión ha caducado. Recarga la página.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        $id = max(0, (int) ($_POST['id'] ?? 0));

        try {
            if ($action === 'save') {
                $titulo = trim((string) ($_POST['titulo'] ?? ''));
                $subtitulo = trim((string) ($_POST['subtitulo'] ?? '')) ?: null;
                $etiqueta = trim((string) ($_POST['etiqueta'] ?? '')) ?: null;
                $textoBoton = trim((string) ($_POST['texto_boton'] ?? '')) ?: null;
                $enlaceBoton = trim((string) ($_POST['enlace_boton'] ?? '')) ?: null;
                $orden = (int) ($_POST['orden'] ?? 0);
                $activo = !empty($_POST['activo']) ? 1 : 0;
                $fechaInicio = adminNormalizeDateTime($_POST['fecha_inicio'] ?? null);
                $fechaFin = adminNormalizeDateTime($_POST['fecha_fin'] ?? null);
                $imagenActual = (string) ($_POST['imagen_actual'] ?? '');

                if ($titulo === '') {
                    throw new RuntimeException('El título del banner es obligatorio.');
                }

                $imagen = adminHandleImageUpload('imagen', $imagenActual, 'landing') ?: null;

                if ($id > 0) {
                    $stmt = $conn->prepare("
                        UPDATE banners_portada
                        SET titulo = ?, subtitulo = ?, etiqueta = ?, texto_boton = ?, enlace_boton = ?, imagen = ?, activo = ?, orden = ?, fecha_inicio = ?, fecha_fin = ?
                        WHERE id = ?
                        LIMIT 1
                    ");
                    $stmt->bind_param('ssssssiissi', $titulo, $subtitulo, $etiqueta, $textoBoton, $enlaceBoton, $imagen, $activo, $orden, $fechaInicio, $fechaFin, $id);
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO banners_portada (titulo, subtitulo, etiqueta, texto_boton, enlace_boton, imagen, activo, orden, fecha_inicio, fecha_fin)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param('ssssssiiss', $titulo, $subtitulo, $etiqueta, $textoBoton, $enlaceBoton, $imagen, $activo, $orden, $fechaInicio, $fechaFin);
                }
                $stmt->execute();
                $stmt->close();
                $messages[] = 'Banner guardado correctamente.';
            } elseif ($action === 'toggle' && $id > 0) {
                $stmt = $conn->prepare('UPDATE banners_portada SET activo = IF(activo = 1, 0, 1) WHERE id = ? LIMIT 1');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();
                $messages[] = 'Estado del banner actualizado.';
            } elseif ($action === 'delete' && $id > 0) {
                $stmt = $conn->prepare('DELETE FROM banners_portada WHERE id = ? LIMIT 1');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();
                $messages[] = 'Banner eliminado correctamente.';
            }
        } catch (Throwable $error) {
            $errors[] = $error->getMessage();
        }
    }
}

$editId = isset($_GET['id']) ? max(0, (int) $_GET['id']) : 0;
$editingBanner = null;
if ($editId > 0) {
    $stmt = $conn->prepare('SELECT * FROM banners_portada WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $editingBanner = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
}

$banners = [];
$result = $conn->query('SELECT * FROM banners_portada ORDER BY orden ASC, created_at DESC, id DESC');
while ($row = $result->fetch_assoc()) {
    $banners[] = $row;
}
$result->free();

adminRenderHeader('Banners', 'banners');
?>
<section class="admin-page-header">
    <div>
        <p class="admin-eyebrow">Portada</p>
        <h1>Banners y ofertas de portada</h1>
        <p>Configura los bloques promocionales que aparecen en la página de inicio.</p>
    </div>
</section>

<?php foreach ($messages as $message): ?><div class="admin-message admin-message--success"><?= e($message) ?></div><?php endforeach; ?>
<?php foreach ($errors as $error): ?><div class="admin-message admin-message--error"><?= e($error) ?></div><?php endforeach; ?>

<section class="admin-detail-grid">
    <article class="admin-card">
        <div class="admin-card__header"><h2>Banners existentes</h2><span><?= count($banners) ?> banner<?= count($banners) === 1 ? '' : 's' ?></span></div>
        <?php if ($banners === []): ?>
            <p class="admin-empty">Todavía no hay banners. Crea uno y aparecerá en portada cuando esté activo.</p>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Banner</th><th>Orden</th><th>Fechas</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($banners as $banner): ?>
                        <?php $image = basename((string) ($banner['imagen'] ?? '')); ?>
                        <tr>
                            <td>
                                <div class="admin-product-cell">
                                    <?php if ($image !== ''): ?><img src="../img/landing/<?= e($image) ?>" alt="" loading="lazy"><?php endif; ?>
                                    <div><strong><?= e((string) $banner['titulo']) ?></strong><br><small><?= e((string) ($banner['subtitulo'] ?? '')) ?></small></div>
                                </div>
                            </td>
                            <td><?= (int) $banner['orden'] ?></td>
                            <td><small><?= $banner['fecha_inicio'] ? e(date('d/m/Y H:i', strtotime((string) $banner['fecha_inicio']))) : 'Sin inicio' ?> · <?= $banner['fecha_fin'] ? e(date('d/m/Y H:i', strtotime((string) $banner['fecha_fin']))) : 'Sin fin' ?></small></td>
                            <td><span class="admin-status"><?= (int) $banner['activo'] === 1 ? 'Activo' : 'Inactivo' ?></span></td>
                            <td class="admin-actions-cell">
                                <a href="banners.php?id=<?= (int) $banner['id'] ?>" class="admin-table__action">Editar</a>
                                <form method="post" class="admin-inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= (int) $banner['id'] ?>">
                                    <button class="admin-table__action" type="submit"><?= (int) $banner['activo'] === 1 ? 'Pausar' : 'Activar' ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>

    <aside class="admin-card admin-card--side">
        <div class="admin-card__header"><h2><?= $editingBanner ? 'Editar banner' : 'Nuevo banner' ?></h2></div>
        <form class="admin-form" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int) ($editingBanner['id'] ?? 0) ?>">
            <input type="hidden" name="imagen_actual" value="<?= e((string) ($editingBanner['imagen'] ?? '')) ?>">

            <?php if (!empty($editingBanner['imagen'])): ?>
                <div class="admin-image-preview admin-image-preview--wide"><img src="../img/landing/<?= e(basename((string) $editingBanner['imagen'])) ?>" alt=""></div>
            <?php endif; ?>
            <label>Título
                <input type="text" name="titulo" value="<?= e((string) ($editingBanner['titulo'] ?? '')) ?>" required maxlength="160">
            </label>
            <label>Subtítulo
                <textarea name="subtitulo" rows="3" maxlength="255"><?= e((string) ($editingBanner['subtitulo'] ?? '')) ?></textarea>
            </label>
            <label>Etiqueta
                <input type="text" name="etiqueta" value="<?= e((string) ($editingBanner['etiqueta'] ?? '')) ?>" maxlength="80" placeholder="Oferta destacada">
            </label>
            <label>Texto botón
                <input type="text" name="texto_boton" value="<?= e((string) ($editingBanner['texto_boton'] ?? '')) ?>" maxlength="80" placeholder="Ver oferta">
            </label>
            <label>Enlace botón
                <input type="text" name="enlace_boton" value="<?= e((string) ($editingBanner['enlace_boton'] ?? '')) ?>" maxlength="255" placeholder="ofertas.php">
            </label>
            <label>Imagen
                <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp">
            </label>
            <label>Orden
                <input type="number" name="orden" value="<?= (int) ($editingBanner['orden'] ?? 0) ?>" step="1">
            </label>
            <label>Fecha inicio
                <input type="datetime-local" name="fecha_inicio" value="<?= e(adminDateTimeInput($editingBanner['fecha_inicio'] ?? null)) ?>">
            </label>
            <label>Fecha fin
                <input type="datetime-local" name="fecha_fin" value="<?= e(adminDateTimeInput($editingBanner['fecha_fin'] ?? null)) ?>">
            </label>
            <label class="admin-check-row"><input type="checkbox" name="activo" value="1" <?= (int) ($editingBanner['activo'] ?? 1) === 1 ? 'checked' : '' ?>> Banner activo</label>
            <button class="btn btn--primary" type="submit">Guardar banner</button>
            <?php if ($editingBanner): ?><a href="banners.php" class="admin-table__action">Cancelar edición</a><?php endif; ?>
        </form>
    </aside>
</section>
<?php adminRenderFooter(); ?>
