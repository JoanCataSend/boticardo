<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Política de cookies | Boticardo';
$pageDescription = 'Información sobre las cookies utilizadas en Boticardo, su finalidad, duración y cómo puedes configurarlas desde tu navegador.';
$canonicalUrl = $siteUrl . '/cookies.php';

$lastUpdated = '30/06/2026';
?>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<main id="main-content" class="legal-page">
    <section class="legal-hero">
        <div class="container legal-hero__inner">
            <span class="legal-hero__eyebrow">Cookies</span>
            <h1>Política de cookies</h1>
            <p>Información sobre las cookies y tecnologías similares que pueden utilizarse en esta web.</p>
            <p class="legal-hero__date">Última actualización: <?= e($lastUpdated) ?></p>
        </div>
    </section>

    <section class="legal-section">
        <div class="container legal-layout">
            <aside class="legal-sidebar" aria-label="Índice de cookies">
                <p class="legal-sidebar__title">En esta página</p>
                <a href="#que-son">Qué son</a>
                <a href="#cookies-usadas">Cookies usadas</a>
                <a href="#terceros">Terceros</a>
                <a href="#configurar">Configurar cookies</a>
                <a href="#actualizaciones">Actualizaciones</a>
            </aside>

            <article class="legal-card">
                <section id="que-son" class="legal-block">
                    <h2>1. Qué son las cookies</h2>
                    <p>Las cookies son pequeños archivos que se almacenan en tu navegador cuando visitas una página web. Pueden servir para recordar la sesión, mantener productos en el carrito, mejorar la seguridad o permitir servicios externos.</p>
                </section>

                <section id="cookies-usadas" class="legal-block">
                    <h2>2. Cookies utilizadas en Boticardo</h2>
                    <p>Actualmente la web utiliza principalmente cookies técnicas necesarias para que la tienda funcione correctamente.</p>

                    <div class="legal-table-wrap">
                        <table class="legal-table">
                            <thead>
                            <tr>
                                <th>Cookie / tecnología</th>
                                <th>Tipo</th>
                                <th>Finalidad</th>
                                <th>Duración aproximada</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>PHPSESSID</td>
                                <td>Técnica</td>
                                <td>Mantener la sesión del usuario, carrito, favoritos y seguridad básica de formularios.</td>
                                <td>Sesión</td>
                            </tr>
                            <tr>
                                <td>Cookies de pasarela de pago</td>
                                <td>Técnica / seguridad</td>
                                <td>Permitir el pago seguro cuando se redirige a servicios externos como la pasarela de pago.</td>
                                <td>Según proveedor</td>
                            </tr>
                            <tr>
                                <td>Cookies de servicios externos</td>
                                <td>Terceros</td>
                                <td>Funciones como mapas incrustados o inicio de sesión social, si se activan o se muestran en la página.</td>
                                <td>Según proveedor</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section id="terceros" class="legal-block">
                    <h2>3. Servicios de terceros</h2>
                    <p>Algunas zonas de la web pueden integrar servicios externos, como mapas, pasarelas de pago o inicio de sesión social. Estos servicios pueden instalar cookies propias o tratar datos técnicos de navegación según sus propias políticas.</p>
                    <ul class="legal-list">
                        <li><strong>Google Maps:</strong> se utiliza para mostrar la ubicación de la farmacia en la página de contacto.</li>
                        <li><strong>Google / Apple Login:</strong> pueden utilizarse si activas el inicio de sesión social.</li>
                        <li><strong>Stripe u otra pasarela:</strong> puede utilizar cookies necesarias para seguridad y prevención de fraude durante el pago.</li>
                    </ul>
                </section>

                <section id="configurar" class="legal-block">
                    <h2>4. Cómo configurar o eliminar cookies</h2>
                    <p>Puedes permitir, bloquear o eliminar cookies desde la configuración de tu navegador. Ten en cuenta que bloquear cookies técnicas puede impedir que funcionen correctamente el carrito, la sesión o el proceso de compra.</p>
                    <ul class="legal-list legal-list--links">
                        <li><a href="https://support.google.com/chrome/answer/95647" rel="nofollow noopener" target="_blank">Configurar cookies en Google Chrome</a></li>
                        <li><a href="https://support.mozilla.org/es/kb/Borrar%20cookies" rel="nofollow noopener" target="_blank">Configurar cookies en Mozilla Firefox</a></li>
                        <li><a href="https://support.apple.com/es-es/guide/safari/sfri11471/mac" rel="nofollow noopener" target="_blank">Configurar cookies en Safari</a></li>
                        <li><a href="https://support.microsoft.com/es-es/windows/administrar-cookies-en-microsoft-edge" rel="nofollow noopener" target="_blank">Configurar cookies en Microsoft Edge</a></li>
                    </ul>
                </section>

                <section id="actualizaciones" class="legal-block">
                    <h2>5. Actualizaciones de esta política</h2>
                    <p>Esta política puede actualizarse cuando se incorporen nuevas funcionalidades, servicios externos, cookies analíticas, publicitarias o cambios técnicos relevantes.</p>
                    <p>Si en el futuro se usan cookies no técnicas que requieran consentimiento, la web deberá incorporar un sistema de aviso y configuración de cookies antes de instalarlas.</p>
                </section>
            </article>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
