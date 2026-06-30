<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Aviso legal | Boticardo';
$pageDescription = 'Información legal de Boticardo: titularidad, datos de contacto, condiciones de uso y responsabilidad del sitio web.';
$canonicalUrl = $siteUrl . '/aviso-legal.php';

$fullAddress = $streetAddress . ', ' . $postalCode . ' ' . $locality . ', ' . $region . ', España';
$lastUpdated = '30/06/2026';
?>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<main id="main-content" class="legal-page">
    <section class="legal-hero">
        <div class="container legal-hero__inner">
            <span class="legal-hero__eyebrow">Información legal</span>
            <h1>Aviso legal</h1>
            <p>Datos identificativos del titular de la web, condiciones de uso y responsabilidades generales de Boticardo.</p>
            <p class="legal-hero__date">Última actualización: <?= e($lastUpdated) ?></p>
        </div>
    </section>

    <section class="legal-section">
        <div class="container legal-layout">
            <aside class="legal-sidebar" aria-label="Índice del aviso legal">
                <p class="legal-sidebar__title">En esta página</p>
                <a href="#titular">Titular de la web</a>
                <a href="#actividad">Actividad</a>
                <a href="#uso-web">Uso de la web</a>
                <a href="#propiedad">Propiedad intelectual</a>
                <a href="#responsabilidad">Responsabilidad</a>
                <a href="#normativa">Normativa aplicable</a>
            </aside>

            <article class="legal-card">
                <div class="legal-alert legal-alert--warning">
                    <strong>Completar antes de publicar:</strong>
                    revisa y añade los datos oficiales que falten, como NIF/CIF, número de colegiado, colegio profesional y autorización sanitaria si corresponde.
                </div>

                <section id="titular" class="legal-block">
                    <h2>1. Titular de la web</h2>
                    <p>En cumplimiento de las obligaciones de información de los prestadores de servicios de la sociedad de la información, se informa de que este sitio web pertenece a:</p>

                    <dl class="legal-data-list">
                        <div>
                            <dt>Nombre comercial</dt>
                            <dd><?= e($siteName) ?></dd>
                        </div>
                        <div>
                            <dt>Titular</dt>
                            <dd><?= e($legalName) ?></dd>
                        </div>
                        <div>
                            <dt>Domicilio</dt>
                            <dd><?= e($fullAddress) ?></dd>
                        </div>
                        <div>
                            <dt>Teléfono</dt>
                            <dd><a href="tel:<?= e($phoneE164) ?>"><?= e($phoneDisplay) ?></a></dd>
                        </div>
                        <div>
                            <dt>Email</dt>
                            <dd><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></dd>
                        </div>
                        <div>
                            <dt>NIF/CIF</dt>
                            <dd>Pendiente de completar</dd>
                        </div>
                        <div>
                            <dt>Nº de colegiado / autorización sanitaria</dt>
                            <dd>Pendiente de completar</dd>
                        </div>
                        <div>
                            <dt>Colegio profesional</dt>
                            <dd>Pendiente de completar</dd>
                        </div>
                    </dl>
                </section>

                <section id="actividad" class="legal-block">
                    <h2>2. Actividad del sitio web</h2>
                    <p><?= e($siteName) ?> es una web de farmacia y parafarmacia que ofrece información sobre productos, pedidos online, contacto con la farmacia y servicios relacionados con la atención farmacéutica.</p>
                    <p>La venta online de medicamentos, cuando proceda, debe limitarse a medicamentos de uso humano no sujetos a prescripción médica y realizarse desde una oficina de farmacia autorizada, conforme a la normativa aplicable. Los medicamentos sujetos a receta no se comercializan por internet.</p>
                </section>

                <section id="uso-web" class="legal-block">
                    <h2>3. Condiciones de uso de la web</h2>
                    <p>La persona usuaria se compromete a utilizar la web de forma lícita, responsable y respetuosa, sin realizar actividades que puedan dañar, inutilizar o sobrecargar el sitio web, ni impedir su uso normal por parte de otras personas.</p>
                    <p>No está permitido utilizar esta web para introducir datos falsos, suplantar identidades, realizar pedidos fraudulentos, intentar acceder a zonas restringidas sin autorización o alterar el funcionamiento técnico de la plataforma.</p>
                </section>

                <section id="propiedad" class="legal-block">
                    <h2>4. Propiedad intelectual e industrial</h2>
                    <p>Los contenidos de la web, incluyendo textos, diseño, logotipos, imágenes, estructura, código y elementos gráficos, pertenecen a <?= e($siteName) ?> o se utilizan con autorización de sus titulares.</p>
                    <p>Queda prohibida la reproducción, distribución, comunicación pública o transformación de estos contenidos sin autorización expresa, salvo en los casos permitidos legalmente.</p>
                </section>

                <section id="responsabilidad" class="legal-block">
                    <h2>5. Responsabilidad</h2>
                    <p><?= e($siteName) ?> trabaja para mantener la información actualizada y correcta, pero no puede garantizar la ausencia total de errores técnicos, interrupciones o desactualizaciones puntuales.</p>
                    <p>La información publicada en la web no sustituye el consejo de un profesional sanitario. Ante dudas sobre un medicamento, producto sanitario, tratamiento, embarazo, lactancia, alergias o patologías, consulta siempre con un farmacéutico o profesional sanitario cualificado.</p>
                </section>

                <section id="enlaces" class="legal-block">
                    <h2>6. Enlaces externos</h2>
                    <p>La web puede incluir enlaces a páginas externas, como pasarelas de pago, mapas, redes sociales o servicios de terceros. <?= e($siteName) ?> no se responsabiliza de los contenidos, políticas o funcionamiento de dichos sitios externos.</p>
                </section>

                <section id="normativa" class="legal-block">
                    <h2>7. Normativa aplicable</h2>
                    <p>Este aviso legal se rige por la normativa española aplicable, incluyendo la regulación sobre servicios de la sociedad de la información, comercio electrónico, consumidores y usuarios, protección de datos y normativa sanitaria aplicable a oficinas de farmacia.</p>
                    <p>Para cualquier duda sobre este aviso legal, puedes contactar con nosotros en <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>.</p>
                </section>
            </article>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
