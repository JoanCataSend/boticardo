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
        try {
            $id = max(0, (int) ($_POST['id'] ?? 0));
            $titulo = trim((string) ($_POST['titulo'] ?? ''));
            $descripcion = trim((string) ($_POST['descripcion'] ?? '')) ?: null;
            $contenidoHtml = contentCleanHtml((string) ($_POST['contenido_html'] ?? ''));
            $publicado = !empty($_POST['publicado']) ? 1 : 0;

            if ($id <= 0) {
                throw new RuntimeException('Página legal no válida.');
            }
            if ($titulo === '') {
                throw new RuntimeException('El título es obligatorio.');
            }

            $stmt = $conn->prepare('UPDATE paginas_legales SET titulo = ?, descripcion = ?, contenido_html = ?, publicado = ? WHERE id = ? LIMIT 1');
            $stmt->bind_param('sssii', $titulo, $descripcion, $contenidoHtml, $publicado, $id);
            $stmt->execute();
            $stmt->close();
            $messages[] = 'Página legal guardada correctamente.';
        } catch (Throwable $error) {
            $errors[] = $error->getMessage();
        }
    }
}

$pages = [];
$result = $conn->query('SELECT * FROM paginas_legales ORDER BY FIELD(slug, "aviso-legal", "privacidad", "cookies", "condiciones-compra", "envios-devoluciones"), slug ASC');
while ($row = $result->fetch_assoc()) {
    $pages[] = $row;
}
$result->free();

$editSlug = isset($_GET['slug']) ? trim((string) $_GET['slug']) : ($pages[0]['slug'] ?? '');
$editingPage = null;
foreach ($pages as $page) {
    if ((string) $page['slug'] === $editSlug) {
        $editingPage = $page;
        break;
    }
}
if (!$editingPage && $pages !== []) {
    $editingPage = $pages[0];
}

$urlBySlug = [
    'aviso-legal' => '../aviso-legal.php',
    'privacidad' => '../privacidad.php',
    'cookies' => '../cookies.php',
    'condiciones-compra' => '../condiciones-compra.php',
    'envios-devoluciones' => '../envios-devoluciones.php',
];

adminRenderHeader('Páginas legales', 'legales');
?>
<section class="admin-page-header">
    <div>
        <p class="admin-eyebrow">Legal</p>
        <h1>Páginas legales</h1>
        <p>Edita los textos legales sin tocar archivos PHP. Si una página no está publicada, se muestra el texto fijo de respaldo.</p>
    </div>
</section>

<?php foreach ($messages as $message): ?><div class="admin-message admin-message--success"><?= e($message) ?></div><?php endforeach; ?>
<?php foreach ($errors as $error): ?><div class="admin-message admin-message--error"><?= e($error) ?></div><?php endforeach; ?>

<section class="admin-detail-grid">
    <article class="admin-card">
        <div class="admin-card__header"><h2>Páginas</h2></div>
        <div class="admin-legal-tabs">
            <?php foreach ($pages as $page): ?>
                <a href="paginas-legales.php?slug=<?= e((string) $page['slug']) ?>" class="<?= $editingPage && $editingPage['slug'] === $page['slug'] ? 'admin-filter-tabs__link--active' : '' ?>">
                    <?= e((string) $page['titulo']) ?>
                    <small><?= (int) $page['publicado'] === 1 ? 'Publicado' : 'Borrador' ?></small>
                </a>
            <?php endforeach; ?>
        </div>
    </article>

    <?php if ($editingPage): ?>
        <article class="admin-card">
            <div class="admin-card__header">
                <h2>Editando: <?= e((string) $editingPage['titulo']) ?></h2>
                <?php if (isset($urlBySlug[$editingPage['slug']])): ?><a href="<?= e($urlBySlug[$editingPage['slug']]) ?>" target="_blank" rel="noopener">Ver página</a><?php endif; ?>
            </div>
            <form class="admin-form" method="post">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="id" value="<?= (int) $editingPage['id'] ?>">
                <label>Título
                    <input type="text" name="titulo" value="<?= e((string) $editingPage['titulo']) ?>" required maxlength="160">
                </label>
                <label>Descripción SEO / resumen
                    <input type="text" name="descripcion" value="<?= e((string) ($editingPage['descripcion'] ?? '')) ?>" maxlength="255">
                </label>
                <label>Contenido HTML permitido
                    <textarea name="contenido_html" rows="18" spellcheck="false"><?= e((string) ($editingPage['contenido_html'] ?? '')) ?></textarea>
                </label>
                <p class="admin-help-text">Puedes usar etiquetas simples como &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;strong&gt; y &lt;a&gt;. No se permiten scripts.</p>
                <label class="admin-check-row">
                    <input type="checkbox" name="publicado" value="1" <?= (int) ($editingPage['publicado'] ?? 0) === 1 ? 'checked' : '' ?>>
                    Publicar esta versión en la web
                </label>
                <button class="btn btn--primary" type="submit">Guardar página legal</button>
            </form>
        </article>
    <?php endif; ?>
</section>
<?php adminRenderFooter(); ?>
