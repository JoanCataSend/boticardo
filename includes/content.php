<?php
declare(strict_types=1);

/**
 * Contenido editable de tienda: banners de portada, cupones y páginas legales.
 */

function contentTableExists(mysqli $conn, string $tableName): bool
{
    try {
        $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1');
        $stmt->bind_param('s', $tableName);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $result->free();
        $stmt->close();
        return (int) ($row['total'] ?? 0) > 0;
    } catch (Throwable $error) {
        error_log('Boticardo - Error comprobando tabla: ' . $error->getMessage());
        return false;
    }
}

function contentEnsureTables(mysqli $conn): void
{
    $conn->query("\n        CREATE TABLE IF NOT EXISTS cupones (\n            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,\n            codigo VARCHAR(40) NOT NULL,\n            descripcion VARCHAR(255) NULL,\n            tipo ENUM('porcentaje','importe') NOT NULL DEFAULT 'porcentaje',\n            valor DECIMAL(10,2) NOT NULL DEFAULT 0.00,\n            importe_minimo DECIMAL(10,2) NOT NULL DEFAULT 0.00,\n            usos_maximos INT UNSIGNED NULL,\n            usos_actuales INT UNSIGNED NOT NULL DEFAULT 0,\n            activo TINYINT(1) NOT NULL DEFAULT 1,\n            fecha_inicio DATETIME NULL,\n            fecha_fin DATETIME NULL,\n            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n            UNIQUE KEY uq_cupones_codigo (codigo),\n            KEY idx_cupones_activo (activo, fecha_inicio, fecha_fin)\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    $conn->query("\n        CREATE TABLE IF NOT EXISTS banners_portada (\n            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,\n            titulo VARCHAR(160) NOT NULL,\n            subtitulo VARCHAR(255) NULL,\n            etiqueta VARCHAR(80) NULL,\n            texto_boton VARCHAR(80) NULL,\n            enlace_boton VARCHAR(255) NULL,\n            imagen VARCHAR(255) NULL,\n            activo TINYINT(1) NOT NULL DEFAULT 1,\n            orden INT NOT NULL DEFAULT 0,\n            fecha_inicio DATETIME NULL,\n            fecha_fin DATETIME NULL,\n            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n            KEY idx_banners_activo (activo, orden, fecha_inicio, fecha_fin)\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    $conn->query("\n        CREATE TABLE IF NOT EXISTS paginas_legales (\n            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,\n            slug VARCHAR(80) NOT NULL,\n            titulo VARCHAR(160) NOT NULL,\n            descripcion VARCHAR(255) NULL,\n            contenido_html MEDIUMTEXT NULL,\n            publicado TINYINT(1) NOT NULL DEFAULT 0,\n            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n            UNIQUE KEY uq_paginas_legales_slug (slug)\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    contentSeedLegalPages($conn);
}

function contentSeedLegalPages(mysqli $conn): void
{
    $pages = [
        'aviso-legal' => ['Aviso legal', 'Información legal de Boticardo.'],
        'privacidad' => ['Política de privacidad', 'Información sobre tratamiento de datos personales.'],
        'cookies' => ['Política de cookies', 'Información sobre cookies y tecnologías similares.'],
        'condiciones-compra' => ['Condiciones de compra', 'Condiciones aplicables a pedidos y pagos.'],
        'envios-devoluciones' => ['Envíos y devoluciones', 'Información sobre envíos, cambios y devoluciones.'],
    ];

    $stmt = $conn->prepare("
        INSERT INTO paginas_legales (slug, titulo, descripcion, publicado)
        VALUES (?, ?, ?, 0)
        ON DUPLICATE KEY UPDATE titulo = titulo
    ");

    foreach ($pages as $slug => [$title, $description]) {
        $stmt->bind_param('sss', $slug, $title, $description);
        $stmt->execute();
    }

    $stmt->close();
}

function contentGetActiveBanners(mysqli $conn, int $limit = 3): array
{
    contentEnsureTables($conn);
    $limit = max(1, min(6, $limit));
    $banners = [];

    try {
        $sql = "\n            SELECT *\n            FROM banners_portada\n            WHERE activo = 1\n              AND (fecha_inicio IS NULL OR fecha_inicio <= NOW())\n              AND (fecha_fin IS NULL OR fecha_fin >= NOW())\n            ORDER BY orden ASC, id DESC\n            LIMIT $limit\n        ";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $banners[] = $row;
        }
        $result->free();
    } catch (Throwable $error) {
        error_log('Boticardo - Error al cargar banners: ' . $error->getMessage());
    }

    return $banners;
}

function contentGetPublishedLegalPage(mysqli $conn, string $slug): ?array
{
    contentEnsureTables($conn);
    $stmt = $conn->prepare('SELECT * FROM paginas_legales WHERE slug = ? AND publicado = 1 LIMIT 1');
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    $page = $result->fetch_assoc() ?: null;
    $result->free();
    $stmt->close();

    if (!$page || trim((string) ($page['contenido_html'] ?? '')) === '') {
        return null;
    }

    return $page;
}

function contentCleanHtml(string $html): string
{
    $allowedTags = '<p><br><strong><b><em><i><u><ul><ol><li><h2><h3><h4><a><dl><dt><dd><blockquote><hr><div><section><span>';
    $clean = strip_tags($html, $allowedTags);

    // Evita javascript: en enlaces añadidos desde el panel.
    $clean = preg_replace('/href\s*=\s*(["\'])\s*javascript:[^"\']*\1/i', 'href="#"', $clean) ?? $clean;
    $clean = preg_replace('/on[a-z]+\s*=\s*(["\']).*?\1/i', '', $clean) ?? $clean;

    return trim($clean);
}

function contentRenderLegalOverride(array $page, string $slug, string $lastUpdated): void
{
    $title = (string) ($page['titulo'] ?? 'Información legal');
    $description = (string) ($page['descripcion'] ?? 'Información legal de Boticardo.');
    $html = (string) ($page['contenido_html'] ?? '');
    ?>
    <main id="main-content" class="legal-page">
        <section class="legal-hero">
            <div class="container legal-hero__inner">
                <span class="legal-hero__eyebrow">Información legal</span>
                <h1><?= e($title) ?></h1>
                <p><?= e($description) ?></p>
                <p class="legal-hero__date">Última actualización: <?= e($lastUpdated) ?></p>
            </div>
        </section>

        <section class="legal-section">
            <div class="container legal-layout legal-layout--single">
                <article class="legal-card">
                    <?= $html ?>
                </article>
            </div>
        </section>
    </main>
    <?php
}
