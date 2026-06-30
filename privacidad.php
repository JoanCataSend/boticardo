<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Política de privacidad | Boticardo';
$pageDescription = 'Política de privacidad de Boticardo: qué datos recogemos, para qué los usamos, base legal, conservación y derechos de las personas usuarias.';
$canonicalUrl = $siteUrl . '/privacidad.php';

$fullAddress = $streetAddress . ', ' . $postalCode . ' ' . $locality . ', ' . $region . ', España';
$lastUpdated = '30/06/2026';
?>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<main id="main-content" class="legal-page">
    <section class="legal-hero">
        <div class="container legal-hero__inner">
            <span class="legal-hero__eyebrow">Protección de datos</span>
            <h1>Política de privacidad</h1>
            <p>Te explicamos cómo tratamos tus datos cuando navegas, creas una cuenta, realizas pedidos o contactas con Boticardo.</p>
            <p class="legal-hero__date">Última actualización: <?= e($lastUpdated) ?></p>
        </div>
    </section>

    <section class="legal-section">
        <div class="container legal-layout">
            <aside class="legal-sidebar" aria-label="Índice de privacidad">
                <p class="legal-sidebar__title">En esta página</p>
                <a href="#responsable">Responsable</a>
                <a href="#datos">Datos tratados</a>
                <a href="#finalidades">Finalidades</a>
                <a href="#legitimacion">Base legal</a>
                <a href="#destinatarios">Destinatarios</a>
                <a href="#conservacion">Conservación</a>
                <a href="#derechos">Derechos</a>
            </aside>

            <article class="legal-card">
                <div class="legal-alert legal-alert--warning">
                    <strong>Importante:</strong>
                    No se que hay que poner en  esta parte asi que la dejo asii y ya despues lo cambiaremos
                </div>

                <section id="responsable" class="legal-block">
                    <h2>1. Responsable del tratamiento</h2>
                    <dl class="legal-data-list">
                        <div>
                            <dt>Responsable</dt>
                            <dd><?= e($legalName) ?></dd>
                        </div>
                        <div>
                            <dt>Nombre comercial</dt>
                            <dd><?= e($siteName) ?></dd>
                        </div>
                        <div>
                            <dt>Domicilio</dt>
                            <dd><?= e($fullAddress) ?></dd>
                        </div>
                        <div>
                            <dt>Email de contacto</dt>
                            <dd><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></dd>
                        </div>
                        <div>
                            <dt>Teléfono</dt>
                            <dd><a href="tel:<?= e($phoneE164) ?>"><?= e($phoneDisplay) ?></a></dd>
                        </div>
                        <div>
                            <dt>NIF/CIF</dt>
                            <dd>Pendiente de completar</dd>
                        </div>
                    </dl>
                </section>

                <section id="datos" class="legal-block">
                    <h2>2. Qué datos podemos tratar</h2>
                    <p>Según el uso que hagas de la web, podemos tratar las siguientes categorías de datos:</p>
                    <ul class="legal-list">
                        <li><strong>Datos identificativos:</strong> nombre, apellidos y datos de cuenta.</li>
                        <li><strong>Datos de contacto:</strong> email, teléfono y dirección de envío o facturación.</li>
                        <li><strong>Datos de pedido:</strong> productos comprados, importes, método de pago, estado del pedido y comunicaciones relacionadas.</li>
                        <li><strong>Datos técnicos:</strong> dirección IP, navegador, dispositivo, fecha y hora de acceso, cookies técnicas y registros de seguridad.</li>
                        <li><strong>Consultas:</strong> información que nos envíes voluntariamente mediante el formulario de contacto o por email.</li>
                    </ul>
                    <p>No debes enviar datos de salud especialmente sensibles mediante formularios generales si no son necesarios para atender tu consulta. Para dudas sanitarias concretas, contacta directamente con la farmacia.</p>
                </section>

                <section id="finalidades" class="legal-block">
                    <h2>3. Para qué usamos tus datos</h2>
                    <ul class="legal-list">
                        <li>Crear y gestionar tu cuenta de usuario.</li>
                        <li>Verificar tu correo electrónico mediante código de confirmación.</li>
                        <li>Gestionar pedidos, pagos, envíos, devoluciones y atención postventa.</li>
                        <li>Responder consultas enviadas desde el formulario de contacto, email o teléfono.</li>
                        <li>Prevenir fraude, accesos no autorizados, abuso de formularios y problemas de seguridad.</li>
                        <li>Cumplir obligaciones legales, contables, fiscales, sanitarias y de consumo.</li>
                    </ul>
                </section>

                <section id="legitimacion" class="legal-block">
                    <h2>4. Base legal del tratamiento</h2>
                    <dl class="legal-data-list legal-data-list--compact">
                        <div>
                            <dt>Ejecución de contrato</dt>
                            <dd>Para gestionar compras, pedidos, pagos, envíos y servicios solicitados.</dd>
                        </div>
                        <div>
                            <dt>Consentimiento</dt>
                            <dd>Para formularios de contacto, comunicaciones no obligatorias o funcionalidades que lo requieran.</dd>
                        </div>
                        <div>
                            <dt>Obligación legal</dt>
                            <dd>Para cumplir normativa fiscal, contable, sanitaria, consumo y protección de datos.</dd>
                        </div>
                        <div>
                            <dt>Interés legítimo</dt>
                            <dd>Para proteger la web, prevenir fraude, mantener la seguridad y responder incidencias.</dd>
                        </div>
                    </dl>
                </section>

                <section id="destinatarios" class="legal-block">
                    <h2>5. Destinatarios y proveedores</h2>
                    <p>No vendemos tus datos personales. Podemos comunicar datos solo cuando sea necesario para prestar el servicio o cumplir obligaciones legales.</p>
                    <ul class="legal-list">
                        <li><strong>Pasarela de pago:</strong> para procesar pagos online de forma segura.</li>
                        <li><strong>Empresas de transporte:</strong> para entregar pedidos si se configura envío a domicilio.</li>
                        <li><strong>Proveedor de hosting y correo:</strong> para alojar la web y enviar comunicaciones transaccionales.</li>
                        <li><strong>Autoridades públicas:</strong> cuando exista obligación legal.</li>
                        <li><strong>Servicios externos:</strong> Google Maps, Google Login, Apple Login u otros servicios integrados si los activas.</li>
                    </ul>
                    <p>Algunos proveedores pueden tratar datos fuera del Espacio Económico Europeo. En ese caso, deberán aplicarse las garantías legalmente previstas.</p>
                </section>

                <section id="conservacion" class="legal-block">
                    <h2>6. Plazo de conservación</h2>
                    <p>Conservaremos tus datos durante el tiempo necesario para cumplir la finalidad para la que fueron recogidos y, después, durante los plazos exigidos por la normativa aplicable o para atender posibles responsabilidades.</p>
                    <ul class="legal-list">
                        <li>Datos de cuenta: mientras mantengas tu cuenta activa.</li>
                        <li>Pedidos y facturación: durante los plazos legales contables, fiscales y de consumo.</li>
                        <li>Consultas de contacto: durante el tiempo necesario para responder y gestionar la incidencia.</li>
                        <li>Registros técnicos: durante el plazo imprescindible para seguridad y mantenimiento.</li>
                    </ul>
                </section>

                <section id="derechos" class="legal-block">
                    <h2>7. Tus derechos</h2>
                    <p>Puedes solicitar el acceso, rectificación, supresión, oposición, limitación del tratamiento y portabilidad de tus datos cuando proceda.</p>
                    <p>Para ejercer tus derechos, escríbenos a <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a> indicando el derecho que deseas ejercer y aportando información suficiente para identificarte.</p>
                    <p>Si consideras que no hemos tratado tus datos correctamente, puedes presentar una reclamación ante la Agencia Española de Protección de Datos.</p>
                </section>

                <section id="seguridad" class="legal-block">
                    <h2>8. Seguridad</h2>
                    <p>Aplicamos medidas técnicas y organizativas razonables para proteger los datos personales frente a accesos no autorizados, pérdida, destrucción o uso indebido. Aun así, ningún sistema conectado a internet puede considerarse completamente infalible.</p>
                </section>
            </article>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
