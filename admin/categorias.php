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
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $descripcion = trim((string) ($_POST['descripcion'] ?? '')) ?: null;

        try {
            if ($action === 'save') {
                if ($nombre === '') {
                    throw new RuntimeException('El nombre de la categoría es obligatorio.');
                }

                if ($id > 0) {
                    $stmt = $conn->prepare('UPDATE categorias SET nombre = ?, descripcion = ? WHERE id = ? LIMIT 1');
                    $stmt->bind_param('ssi', $nombre, $descripcion, $id);
                } else {
                    $stmt = $conn->prepare('INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)');
                    $stmt->bind_param('ss', $nombre, $descripcion);
                }
                $stmt->execute();
                $stmt->close();
                $messages[] = 'Categoría guardada correctamente.';
            } elseif ($action === 'delete' && $id > 0) {
                $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM productos WHERE categoria_id = ?');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ((int) ($row['total'] ?? 0) > 0) {
                    throw new RuntimeException('No puedes eliminar una categoría que tiene productos asignados.');
                }

                $stmt = $conn->prepare('DELETE FROM categorias WHERE id = ? LIMIT 1');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();
                $messages[] = 'Categoría eliminada correctamente.';
            }
        } catch (Throwable $error) {
            $errors[] = $error->getMessage();
        }
    }
}

$categorias = adminFetchCategorias($conn);
adminRenderHeader('Categorías', 'categorias');
?>
<section class="admin-page-header">
    <div>
        <p class="admin-eyebrow">Catálogo</p>
        <h1>Categorías</h1>
        <p>Gestiona las familias del catálogo.</p>
    </div>
</section>

<?php foreach ($messages as $message): ?><div class="admin-message admin-message--success"><?= e($message) ?></div><?php endforeach; ?>
<?php foreach ($errors as $error): ?><div class="admin-message admin-message--error"><?= e($error) ?></div><?php endforeach; ?>

<section class="admin-detail-grid">
    <article class="admin-card">
        <div class="admin-card__header"><h2>Categorías existentes</h2><span><?= count($categorias) ?> categoría<?= count($categorias) === 1 ? '' : 's' ?></span></div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Nombre</th><th>Descripción</th><th>Productos</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($categorias as $categoria): ?>
                    <tr>
                        <td><strong><?= e((string) $categoria['nombre']) ?></strong></td>
                        <td><?= e((string) ($categoria['descripcion'] ?? '—')) ?></td>
                        <td><?= (int) $categoria['productos_total'] ?></td>
                        <td>
                            <form method="post" class="admin-inline-delete" onsubmit="return confirm('¿Eliminar esta categoría?');">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $categoria['id'] ?>">
                                <button class="admin-danger-link" type="submit" <?= (int) $categoria['productos_total'] > 0 ? 'disabled' : '' ?>>Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

    <aside class="admin-card admin-card--side">
        <div class="admin-card__header"><h2>Añadir categoría</h2></div>
        <form class="admin-form" method="post">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="save">
            <label>Nombre
                <input type="text" name="nombre" required maxlength="100">
            </label>
            <label>Descripción
                <textarea name="descripcion" rows="4"></textarea>
            </label>
            <button class="btn btn--primary" type="submit">Guardar categoría</button>
        </form>
    </aside>
</section>

<section class="admin-card">
    <div class="admin-card__header"><h2>Editar rápido</h2></div>
    <div class="admin-edit-list">
        <?php foreach ($categorias as $categoria): ?>
            <form class="admin-form admin-form-grid admin-edit-row" method="post">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int) $categoria['id'] ?>">
                <label>Nombre
                    <input type="text" name="nombre" value="<?= e((string) $categoria['nombre']) ?>" required maxlength="100">
                </label>
                <label>Descripción
                    <input type="text" name="descripcion" value="<?= e((string) ($categoria['descripcion'] ?? '')) ?>">
                </label>
                <button class="btn btn--outline" type="submit">Actualizar</button>
            </form>
        <?php endforeach; ?>
    </div>
</section>
<?php adminRenderFooter(); ?>
