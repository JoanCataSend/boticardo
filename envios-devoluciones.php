<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/content.php';

$pageTitle = 'Envíos y devoluciones | Boticardo';
$pageDescription = 'Información sobre envíos, entregas, recogida, devoluciones, incidencias y derecho de desistimiento en Boticardo.';
$canonicalUrl = $siteUrl . '/envios-devoluciones.php';

$lastUpdated = '30/06/2026';

$legalOverride = contentGetPublishedLegalPage($conn, 'envios-devoluciones');
if ($legalOverride !== null) {
    $pageTitle = (string) $legalOverride['titulo'] . ' | Boticardo';
    $pageDescription = (string) ($legalOverride['descripcion'] ?: $pageDescription);
}
?>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<?php if ($legalOverride !== null): ?>
    <?php contentRenderLegalOverride($legalOverride, 'envios-devoluciones', $lastUpdated); ?>
    <?php require_once __DIR__ . '/includes/footer.php'; ?>
    <?php exit; ?>
<?php endif; ?>
<main id="main-content" class="legal-page">
    <section class="legal-hero">
        <div class="container legal-hero__inner">
            <span class="legal-hero__eyebrow">Pedidos</span>
            <h1>Envíos y devoluciones</h1>
            <p>Información práctica sobre entrega de pedidos, incidencias y devoluciones.</p>
            <p class="legal-hero__date">Última actualización: <?= e($lastUpdated) ?></p>
        </div>
    </section>

    <section class="legal-section">
        <div class="container legal-layout">
            <aside class="legal-sidebar" aria-label="Índice de envíos y devoluciones">
                <p class="legal-sidebar__title">En esta página</p>
                <a href="#ambito">Ámbito de envío</a>
                <a href="#plazos">Plazos</a>
                <a href="#costes">Costes</a>
                <a href="#recogida">Recogida</a>
                <a href="#incidencias">Incidencias</a>
                <a href="#devoluciones">Devoluciones</a>
                <a href="#desistimiento">Desistimiento</a>
            </aside>

            <article class="legal-card">
                <div class="legal-alert legal-alert--warning">
                    <strong>Pendiente de configuración final:</strong>
                    ajusta esta página cuando sepas transportista, zonas de reparto, gastos de envío, importes mínimos y plazos reales.
                </div>

                <section id="ambito" class="legal-block">
                    <h2>1. Ámbito de envío</h2>
                    <p>Los pedidos realizados en <?= e($siteName) ?> podrán enviarse a las zonas disponibles durante el proceso de compra. Las zonas, métodos y limitaciones de entrega se mostrarán antes de confirmar el pedido.</p>
                    <p>Algunos productos pueden estar sujetos a restricciones de envío por normativa sanitaria, disponibilidad, conservación o condiciones del transportista.</p>
                </section>

                <section id="plazos" class="legal-block">
                    <h2>2. Plazos de entrega</h2>
                    <p>Los plazos de entrega son orientativos y pueden variar según destino, disponibilidad del producto, día de la semana, festivos, volumen de pedidos o incidencias del transportista.</p>
                    <p>Si un pedido sufre retraso relevante, intentaremos avisarte mediante los datos de contacto facilitados.</p>
                </section>

                <section id="costes" class="legal-block">
                    <h2>3. Gastos de envío</h2>
                    <p>Los gastos de envío, si existen, se mostrarán en el carrito o durante el proceso de compra antes de confirmar el pago.</p>
                    <p>Si se ofrecen promociones de envío gratuito, estas se aplicarán según las condiciones indicadas en la web en cada momento.</p>
                </section>

                <section id="recogida" class="legal-block">
                    <h2>4. Recogida en farmacia</h2>
                    <p>Cuando esté disponible, podrás recoger tu pedido directamente en la farmacia. En ese caso, te indicaremos cuándo está preparado para su recogida.</p>
                    <p>Para recoger pedidos puede ser necesario acreditar la identidad o mostrar la confirmación del pedido.</p>
                </section>

                <section id="incidencias" class="legal-block">
                    <h2>5. Incidencias con el pedido</h2>
                    <p>Si recibes un producto dañado, equivocado, incompleto o con signos de manipulación, contacta con nosotros lo antes posible en <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a> o en el teléfono <a href="tel:<?= e($phoneE164) ?>"><?= e($phoneDisplay) ?></a>.</p>
                    <p>Para poder revisar la incidencia, conserva el embalaje, el producto y la documentación del pedido. Si es posible, adjunta fotografías del estado en que llegó el paquete.</p>
                </section>

                <section id="devoluciones" class="legal-block">
                    <h2>6. Devoluciones</h2>
                    <p>Las devoluciones se gestionarán conforme a la normativa de consumidores y usuarios y a las restricciones aplicables a productos sanitarios, higiene, cosmética, medicamentos y productos que no puedan devolverse por motivos de salud, seguridad, conservación o trazabilidad.</p>
                    <p>Por motivos sanitarios, no se aceptarán devoluciones de productos abiertos, usados, desprecintados o manipulados cuando no sea posible garantizar su seguridad, higiene o correcta conservación.</p>
                    <p>En caso de error en el pedido, producto defectuoso o daño durante el transporte, revisaremos la incidencia para ofrecer una solución adecuada.</p>
                </section>

                <section id="desistimiento" class="legal-block">
                    <h2>7. Derecho de desistimiento</h2>
                    <p>En compras online, el consumidor puede tener derecho a desistir dentro del plazo legal aplicable. No obstante, existen excepciones para determinados productos, especialmente aquellos que no sean aptos para devolución por razones de protección de la salud o higiene si han sido desprecintados tras la entrega.</p>
                    <p>Para solicitar una devolución o ejercer el desistimiento cuando proceda, contacta con nosotros indicando número de pedido, email de compra, producto afectado y motivo de la solicitud.</p>
                </section>

                <section id="contacto-envios" class="legal-block">
                    <h2>8. Contacto para envíos y devoluciones</h2>
                    <p>Puedes gestionar cualquier duda o incidencia desde la página de <a href="contacto.php">contacto</a>, escribiendo a <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a> o llamando al <a href="tel:<?= e($phoneE164) ?>"><?= e($phoneDisplay) ?></a>.</p>
                </section>
            </article>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
