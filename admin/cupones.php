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
                $codigo = strtoupper(preg_replace('/[^A-Z0-9_-]/i', '', trim((string) ($_POST['codigo'] ?? ''))));
                $descripcion = trim((string) ($_POST['descripcion'] ?? '')) ?: null;
                $tipo = (string) ($_POST['tipo'] ?? 'porcentaje');
                if (!in_array($tipo, ['porcentaje', 'importe'], true)) {
                    $tipo = 'porcentaje';
                }
                $valor = max(0, (float) str_replace(',', '.', (string) ($_POST['valor'] ?? '0')));
                $importeMinimo = max(0, (float) str_replace(',', '.', (string) ($_POST['importe_minimo'] ?? '0')));
                $usosMaximosRaw = trim((string) ($_POST['usos_maximos'] ?? ''));
                $usosMaximos = $usosMaximosRaw !== '' ? max(0, (int) $usosMaximosRaw) : null;
                $activo = !empty($_POST['activo']) ? 1 : 0;
                $fechaInicio = adminNormalizeDateTime($_POST['fecha_inicio'] ?? null);
                $fechaFin = adminNormalizeDateTime($_POST['fecha_fin'] ?? null);

                if ($codigo === '') {
                    throw new RuntimeException('El código del cupón es obligatorio.');
                }
                if ($valor <= 0) {
                    throw new RuntimeException('El valor del descuento debe ser mayor que cero.');
                }
                if ($tipo === 'porcentaje' && $valor > 100) {
                    throw new RuntimeException('Un cupón porcentual no puede superar el 100 %.');
                }

                if ($id > 0) {
                    $stmt = $conn->prepare("
                        UPDATE cupones
                        SET codigo = ?, descripcion = ?, tipo = ?, valor = ?, importe_minimo = ?, usos_maximos = ?, activo = ?, fecha_inicio = ?, fecha_fin = ?
                        WHERE id = ?
                        LIMIT 1
                    ");
                    $stmt->bind_param('sssddiissi', $codigo, $descripcion, $tipo, $valor, $importeMinimo, $usosMaximos, $activo, $fechaInicio, $fechaFin, $id);
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO cupones (codigo, descripcion, tipo, valor, importe_minimo, usos_maximos, activo, fecha_inicio, fecha_fin)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param('sssddiiss', $codigo, $descripcion, $tipo, $valor, $importeMinimo, $usosMaximos, $activo, $fechaInicio, $fechaFin);
                }
                $stmt->execute();
                $stmt->close();
                $messages[] = 'Cupón guardado correctamente.';
            } elseif ($action === 'toggle' && $id > 0) {
                $stmt = $conn->prepare('UPDATE cupones SET activo = IF(activo = 1, 0, 1) WHERE id = ? LIMIT 1');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();
                $messages[] = 'Estado del cupón actualizado.';
            } elseif ($action === 'delete' && $id > 0) {
                $stmt = $conn->prepare('DELETE FROM cupones WHERE id = ? LIMIT 1');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();
                $messages[] = 'Cupón eliminado correctamente.';
            }
        } catch (Throwable $error) {
            $errors[] = $error->getMessage();
        }
    }
}

$editId = isset($_GET['id']) ? max(0, (int) $_GET['id']) : 0;
$editingCoupon = null;
if ($editId > 0) {
    $stmt = $conn->prepare('SELECT * FROM cupones WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $editingCoupon = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
}

$cupones = [];
$result = $conn->query('SELECT * FROM cupones ORDER BY created_at DESC, id DESC LIMIT 300');
while ($row = $result->fetch_assoc()) {
    $cupones[] = $row;
}
$result->free();

adminRenderHeader('Cupones', 'cupones');
?>
<section class="admin-page-header">
    <div>
        <p class="admin-eyebrow">Promociones</p>
        <h1>Cupones y descuentos</h1>
        <p>Crea descuentos por porcentaje o importe fijo para futuras campañas.</p>
    </div>
</section>

<?php foreach ($messages as $message): ?><div class="admin-message admin-message--success"><?= e($message) ?></div><?php endforeach; ?>
<?php foreach ($errors as $error): ?><div class="admin-message admin-message--error"><?= e($error) ?></div><?php endforeach; ?>

<section class="admin-detail-grid">
    <article class="admin-card">
        <div class="admin-card__header"><h2>Cupones existentes</h2><span><?= count($cupones) ?> cupón<?= count($cupones) === 1 ? '' : 'es' ?></span></div>
        <?php if ($cupones === []): ?>
            <p class="admin-empty">Todavía no hay cupones.</p>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Código</th><th>Descuento</th><th>Mínimo</th><th>Usos</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($cupones as $coupon): ?>
                        <tr>
                            <td><strong><?= e((string) $coupon['codigo']) ?></strong><br><small><?= e((string) ($coupon['descripcion'] ?? '')) ?></small></td>
                            <td><?= $coupon['tipo'] === 'porcentaje' ? e(number_format((float) $coupon['valor'], 2, ',', '.') . ' %') : e(adminFormatMoney((float) $coupon['valor'])) ?></td>
                            <td><?= e(adminFormatMoney((float) $coupon['importe_minimo'])) ?></td>
                            <td><?= (int) $coupon['usos_actuales'] ?><?= $coupon['usos_maximos'] !== null ? ' / ' . (int) $coupon['usos_maximos'] : '' ?></td>
                            <td><span class="admin-status"><?= e(adminCouponStatus($coupon)) ?></span></td>
                            <td class="admin-actions-cell">
                                <a href="cupones.php?id=<?= (int) $coupon['id'] ?>" class="admin-table__action">Editar</a>
                                <form method="post" class="admin-inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= (int) $coupon['id'] ?>">
                                    <button class="admin-table__action" type="submit"><?= (int) $coupon['activo'] === 1 ? 'Pausar' : 'Activar' ?></button>
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
        <div class="admin-card__header"><h2><?= $editingCoupon ? 'Editar cupón' : 'Nuevo cupón' ?></h2></div>
        <form class="admin-form" method="post">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int) ($editingCoupon['id'] ?? 0) ?>">
            <label>Código
                <input type="text" name="codigo" value="<?= e((string) ($editingCoupon['codigo'] ?? '')) ?>" required maxlength="40" placeholder="VERANO20">
            </label>
            <label>Descripción
                <input type="text" name="descripcion" value="<?= e((string) ($editingCoupon['descripcion'] ?? '')) ?>" maxlength="255">
            </label>
            <label>Tipo
                <select name="tipo">
                    <option value="porcentaje" <?= ($editingCoupon['tipo'] ?? '') === 'porcentaje' ? 'selected' : '' ?>>Porcentaje</option>
                    <option value="importe" <?= ($editingCoupon['tipo'] ?? '') === 'importe' ? 'selected' : '' ?>>Importe fijo</option>
                </select>
            </label>
            <label>Valor
                <input type="number" name="valor" value="<?= e((string) ($editingCoupon['valor'] ?? '')) ?>" required min="0" step="0.01">
            </label>
            <label>Importe mínimo
                <input type="number" name="importe_minimo" value="<?= e((string) ($editingCoupon['importe_minimo'] ?? '0.00')) ?>" min="0" step="0.01">
            </label>
            <label>Usos máximos
                <input type="number" name="usos_maximos" value="<?= e((string) ($editingCoupon['usos_maximos'] ?? '')) ?>" min="0" step="1" placeholder="Sin límite">
            </label>
            <label>Fecha inicio
                <input type="datetime-local" name="fecha_inicio" value="<?= e(adminDateTimeInput($editingCoupon['fecha_inicio'] ?? null)) ?>">
            </label>
            <label>Fecha fin
                <input type="datetime-local" name="fecha_fin" value="<?= e(adminDateTimeInput($editingCoupon['fecha_fin'] ?? null)) ?>">
            </label>
            <label class="admin-check-row">
                <input type="checkbox" name="activo" value="1" <?= (int) ($editingCoupon['activo'] ?? 1) === 1 ? 'checked' : '' ?>>
                Cupón activo
            </label>
            <button class="btn btn--primary" type="submit">Guardar cupón</button>
            <?php if ($editingCoupon): ?><a href="cupones.php" class="admin-table__action">Cancelar edición</a><?php endif; ?>
        </form>
    </aside>
</section>
<?php adminRenderFooter(); ?>
