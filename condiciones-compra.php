<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/content.php';

$pageTitle = 'Condiciones de compra | Boticardo';
$pageDescription = 'Condiciones generales de compra en Boticardo: proceso de pedido, precios, pagos, disponibilidad, cancelaciones y atención al cliente.';
$canonicalUrl = $siteUrl . '/condiciones-compra.php';

$fullAddress = $streetAddress . ', ' . $postalCode . ' ' . $locality . ', ' . $region . ', España';
$lastUpdated = '30/06/2026';

$legalOverride = contentGetPublishedLegalPage($conn, 'condiciones-compra');
if ($legalOverride !== null) {
    $pageTitle = (string) $legalOverride['titulo'] . ' | Boticardo';
    $pageDescription = (string) ($legalOverride['descripcion'] ?: $pageDescription);
}
?>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<?php if ($legalOverride !== null): ?>
    <?php contentRenderLegalOverride($legalOverride, 'condiciones-compra', $lastUpdated); ?>
    <?php require_once __DIR__ . '/includes/footer.php'; ?>
    <?php exit; ?>
<?php endif; ?>
<main id="main-content" class="legal-page">
    <section class="legal-hero">
        <div class="container legal-hero__inner">
            <span class="legal-hero__eyebrow">Compra online</span>
            <h1>Condiciones de compra</h1>
            <p>Condiciones aplicables a los pedidos realizados a través de la web de Boticardo.</p>
            <p class="legal-hero__date">Última actualización: <?= e($lastUpdated) ?></p>
        </div>
    </section>

    <section class="legal-section">
        <div class="container legal-layout">
            <aside class="legal-sidebar" aria-label="Índice de condiciones de compra">
                <p class="legal-sidebar__title">En esta página</p>
                <a href="#identificacion">Identificación</a>
                <a href="#productos">Productos</a>
                <a href="#proceso">Proceso de compra</a>
                <a href="#precios">Precios</a>
                <a href="#pagos">Pagos</a>
                <a href="#confirmacion">Confirmación</a>
                <a href="#cancelaciones">Cancelaciones</a>
            </aside>

            <article class="legal-card">
                <div class="legal-alert legal-alert--info">
                    Estas condiciones deben revisarse cuando configures definitivamente envíos, tarifas, transportistas, facturación y productos disponibles para venta online.
                </div>

                <section id="identificacion" class="legal-block">
                    <h2>1. Identificación del vendedor</h2>
                    <p>Las compras realizadas en esta web son gestionadas por <?= e($legalName) ?>, con nombre comercial <?= e($siteName) ?>, situada en <?= e($fullAddress) ?>.</p>
                    <p>Puedes contactar con nosotros en <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a> o en el teléfono <a href="tel:<?= e($phoneE164) ?>"><?= e($phoneDisplay) ?></a>.</p>
                </section>

                <section id="productos" class="legal-block">
                    <h2>2. Productos disponibles</h2>
                    <p>La web puede mostrar productos de farmacia, parafarmacia, dermocosmética, higiene, bebé, vitaminas y otras categorías relacionadas.</p>
                    <p>La venta online de medicamentos se realizará únicamente cuando esté permitida legalmente y, en todo caso, solo para medicamentos no sujetos a prescripción médica. No se venden por internet medicamentos que requieren receta.</p>
                    <p>Las imágenes de producto tienen finalidad informativa y pueden variar ligeramente respecto al envase final por cambios del fabricante.</p>
                </section>

                <section id="proceso" class="legal-block">
                    <h2>3. Proceso de compra</h2>
                    <ol class="legal-list legal-list--ordered">
                        <li>Selecciona los productos y añádelos al carrito.</li>
                        <li>Revisa cantidades, precios y productos antes de continuar.</li>
                        <li>Inicia sesión o crea una cuenta para finalizar el pedido.</li>
                        <li>Introduce los datos necesarios para la entrega y facturación.</li>
                        <li>Selecciona el método de pago disponible.</li>
                        <li>Confirma el pedido y espera la confirmación de pago.</li>
                    </ol>
                </section>

                <section id="precios" class="legal-block">
                    <h2>4. Precios, impuestos y disponibilidad</h2>
                    <p>Los precios se muestran en euros e incluyen los impuestos aplicables, salvo error tipográfico o informático evidente.</p>
                    <p>La disponibilidad de productos puede variar. Si un producto no está disponible después de realizar el pedido, contactaremos contigo para ofrecer una solución: sustitución, espera, modificación del pedido o devolución del importe correspondiente.</p>
                </section>

                <section id="pagos" class="legal-block">
                    <h2>5. Métodos de pago</h2>
                    <p>La web está preparada para utilizar pasarela de pago externa con métodos como tarjeta bancaria o Bizum, según configuración disponible en cada momento.</p>
                    <p><?= e($siteName) ?> no almacena los datos completos de tu tarjeta. El pago se procesa mediante proveedores externos seguros.</p>
                </section>

                <section id="confirmacion" class="legal-block">
                    <h2>6. Confirmación del pedido</h2>
                    <p>Una vez completado el pago, recibirás la confirmación del pedido por pantalla y/o por email. También podrás consultar tus pedidos desde el apartado <a href="pedidos.php">Mis pedidos</a> si has iniciado sesión.</p>
                    <p>El pedido no se considerará confirmado hasta que el pago haya sido aceptado correctamente por la pasarela correspondiente.</p>
                </section>

                <section id="cancelaciones" class="legal-block">
                    <h2>7. Cancelaciones y modificaciones</h2>
                    <p>Si quieres modificar o cancelar un pedido, contacta con nosotros lo antes posible. Si el pedido aún no ha sido preparado o enviado, intentaremos gestionarlo.</p>
                    <p>Cuando el pedido ya haya sido preparado, entregado al transportista o dispensado, la modificación o cancelación podrá no ser posible por motivos logísticos, sanitarios o de trazabilidad.</p>
                </section>

                <section id="atencion" class="legal-block">
                    <h2>8. Atención al cliente</h2>
                    <p>Para cualquier incidencia relacionada con tu compra, puedes escribir a <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>, llamar al <a href="tel:<?= e($phoneE164) ?>"><?= e($phoneDisplay) ?></a> o usar el formulario de <a href="contacto.php">contacto</a>.</p>
                </section>
            </article>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
