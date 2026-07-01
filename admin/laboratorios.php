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
        $pais = trim((string) ($_POST['pais_origen'] ?? '')) ?: null;

        try {
            if ($action === 'save') {
                if ($nombre === '') {
                    throw new RuntimeException('El nombre del laboratorio es obligatorio.');
                }

                if ($id > 0) {
                    $stmt = $conn->prepare('UPDATE laboratorios SET nombre = ?, pais_origen = ? WHERE id = ? LIMIT 1');
                    $stmt->bind_param('ssi', $nombre, $pais, $id);
                } else {
                    $stmt = $conn->prepare('INSERT INTO laboratorios (nombre, pais_origen) VALUES (?, ?)');
                    $stmt->bind_param('ss', $nombre, $pais);
                }
                $stmt->execute();
                $stmt->close();
                $messages[] = 'Laboratorio guardado correctamente.';
            } elseif ($action === 'delete' && $id > 0) {
                $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM productos WHERE laboratorio_id = ?');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ((int) ($row['total'] ?? 0) > 0) {
                    throw new RuntimeException('No puedes eliminar un laboratorio que tiene productos asignados.');
                }

                $stmt = $conn->prepare('DELETE FROM laboratorios WHERE id = ? LIMIT 1');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();
                $messages[] = 'Laboratorio eliminado correctamente.';
            }
        } catch (Throwable $error) {
            $errors[] = $error->getMessage();
        }
    }
}

$laboratorios = adminFetchLaboratorios($conn);
adminRenderHeader('Laboratorios', 'laboratorios');
?>
<section class="admin-page-header">
    <div>
        <p class="admin-eyebrow">Marcas</p>
        <h1>Laboratorios</h1>
        <p>Gestiona marcas/laboratorios visibles en los productos y filtros.</p>
    </div>
</section>

<?php foreach ($messages as $message): ?><div class="admin-message admin-message--success"><?= e($message) ?></div><?php endforeach; ?>
<?php foreach ($errors as $error): ?><div class="admin-message admin-message--error"><?= e($error) ?></div><?php endforeach; ?>

<section class="admin-detail-grid">
    <article class="admin-card">
        <div class="admin-card__header"><h2>Laboratorios existentes</h2><span><?= count($laboratorios) ?> laboratorio<?= count($laboratorios) === 1 ? '' : 's' ?></span></div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Nombre</th><th>País</th><th>Productos</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($laboratorios as $laboratorio): ?>
                    <tr>
                        <td><strong><?= e((string) $laboratorio['nombre']) ?></strong></td>
                        <td><?= e((string) ($laboratorio['pais_origen'] ?? '—')) ?></td>
                        <td><?= (int) $laboratorio['productos_total'] ?></td>
                        <td>
                            <form method="post" class="admin-inline-delete" onsubmit="return confirm('¿Eliminar este laboratorio?');">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $laboratorio['id'] ?>">
                                <button class="admin-danger-link" type="submit" <?= (int) $laboratorio['productos_total'] > 0 ? 'disabled' : '' ?>>Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

    <aside class="admin-card admin-card--side">
        <div class="admin-card__header"><h2>Añadir laboratorio</h2></div>
        <form class="admin-form" method="post">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="save">
            <label>Nombre
                <input type="text" name="nombre" required maxlength="100">
            </label>
            <label>País de origen
                <input type="text" name="pais_origen" maxlength="50">
            </label>
            <button class="btn btn--primary" type="submit">Guardar laboratorio</button>
        </form>
    </aside>
</section>

<section class="admin-card">
    <div class="admin-card__header"><h2>Editar rápido</h2></div>
    <div class="admin-edit-list">
        <?php foreach ($laboratorios as $laboratorio): ?>
            <form class="admin-form admin-form-grid admin-edit-row" method="post">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int) $laboratorio['id'] ?>">
                <label>Nombre
                    <input type="text" name="nombre" value="<?= e((string) $laboratorio['nombre']) ?>" required maxlength="100">
                </label>
                <label>País
                    <input type="text" name="pais_origen" value="<?= e((string) ($laboratorio['pais_origen'] ?? '')) ?>" maxlength="50">
                </label>
                <button class="btn btn--outline" type="submit">Actualizar</button>
            </form>
        <?php endforeach; ?>
    </div>
</section>
<?php adminRenderFooter(); ?>
