<?php
// Cargamos toda la lógica desde nuestros archivos separados
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/schema.php';
/**
 * Declaración para el IDE: Estas variables provienen de config.php y schema.php
 * @var string $pageTitle
 * @var string $pageDescription
 * @var string $canonicalUrl
 * @var string $siteName
 * @var string $siteUrl
 * @var bool   $socialImageExists
 * @var string $socialImageUrl
 * @var string $heroImagePath
 * @var bool   $heroImageExists
 * @var string $phoneE164
 * @var string $phoneDisplay
 * @var string $email
 * @var string $streetAddress
 * @var string $postalCode
 * @var string $locality
 * @var string $region
 * @var string $horarioVerano
 * @var string $horarioVeranoT
 * @var string $horarioVeranoV
 * @var string $mapsUrl
 * @var string $mapsEmbedUrl
 * @var array  $structuredData
 */
// Ejecutamos la función para obtener los productos y cerramos la conexión
$productosMasVendidos = getProductosMasVendidos($conn);
$conn->close();
?>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<main id="main-content">


    <section class="hero" aria-label="Promoción principal">
        <div class="container">
            <div class="hero__inner">

                <div class="hero__content">
                    <div class="hero__eyebrow">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        Farmacéuticos colegiados · Envío en 24h
                    </div>

                    <h1 class="hero__title">
                        Farmacia online y de confianza
                        <span>en Manzanera, Teruel</span>
                    </h1>

                    <p class="hero__description">
                        Encuentra productos de farmacia, vitaminas, dermocosmética e higiene con
                        asesoramiento profesional. Compra online o visítanos en Manzanera.
                    </p>

                    <div class="hero__actions">
                        <a href="catalogo.php" class="hero__btn-primary">Ver el catálogo</a>
                        <a href="/consejo" class="hero__btn-secondary">
                            Hablar con un farmacéutico
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </a>
                    </div>

                    <div class="hero__dots" role="tablist" aria-label="Diapositivas del carrusel">
                        <button class="hero__dot hero__dot--active" role="tab" aria-selected="true" aria-label="Diapositiva 1"></button>
                        <button class="hero__dot" role="tab" aria-selected="false" aria-label="Diapositiva 2"></button>
                        <button class="hero__dot" role="tab" aria-selected="false" aria-label="Diapositiva 3"></button>
                    </div>
                </div>

                <div class="hero__image-wrap">
                    <?php if ($heroImageExists): ?>
                        <img
                                src="<?= e($heroImagePath) ?>"
                                alt="Farmacia Boticardo en Manzanera, Teruel"
                                class="hero__image"
                                width="840"
                                height="640"
                                fetchpriority="high"
                                decoding="async"
                        />
                    <?php else: ?>
                        <div class="hero__image-placeholder" role="img" aria-label="Próximamente: fotografía de la farmacia Boticardo en Manzanera">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            <span>Añade una fotografía real de la farmacia</span>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </section>


    <div class="trust-bar" role="region" aria-label="Nuestras garantías">
        <div class="container">
            <ul class="trust-bar__list" role="list">

                <li class="trust-bar__item">
                    <div class="trust-bar__icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <div class="trust-bar__text">
                        <span class="trust-bar__title">Farmacéuticos</span>
                        <span class="trust-bar__subtitle">Asesoramiento profesional</span>
                    </div>
                </li>

                <li class="trust-bar__divider" aria-hidden="true"></li>

                <li class="trust-bar__item">
                    <div class="trust-bar__icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    </div>
                    <div class="trust-bar__text">
                        <span class="trust-bar__title">Envío Gratis</span>
                        <span class="trust-bar__subtitle">En pedidos superiores a 39€</span>
                    </div>
                </li>

                <li class="trust-bar__divider" aria-hidden="true"></li>

                <li class="trust-bar__item">
                    <div class="trust-bar__icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
                    </div>
                    <div class="trust-bar__text">
                        <span class="trust-bar__title">Devolución gratis</span>
                        <span class="trust-bar__subtitle">30 días para devoluciones</span>
                    </div>
                </li>

                <li class="trust-bar__divider" aria-hidden="true"></li>

                <li class="trust-bar__item">
                    <div class="trust-bar__icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                    </div>
                    <div class="trust-bar__text">
                        <span class="trust-bar__title">Pago 100% seguro</span>
                        <span class="trust-bar__subtitle">Visa, Mastercard y Bizum</span>
                    </div>
                </li>

            </ul>
        </div>
    </div>

    <section class="products" aria-labelledby="products-title">
        <div class="container">

            <div class="section-header">
                <span class="section-header__eyebrow">Lo más popular</span>
                <h2 class="section-header__title" id="products-title">Productos más vendidos</h2>
                <p class="section-header__subtitle">Los favoritos de nuestros clientes, siempre disponibles.</p>
            </div>

            <div class="products__grid">
                <?php if ($productosMasVendidos): ?>
                    <?php foreach ($productosMasVendidos as $producto): ?>
                        <?php
                        $productoId = (int) ($producto['id'] ?? 0);
                        $nombreProducto = (string) ($producto['nombre'] ?? 'Producto de farmacia');
                        $marcaProducto = (string) ($producto['marca'] ?: 'Boticardo');
                        $imagenProducto = basename((string) ($producto['imagen'] ?? 'placeholder.jpg'));
                        $precioNumero = (float) ($producto['precio'] ?? 0);
                        $precioMaquina = number_format($precioNumero, 2, '.', '');
                        $precioVisible = number_format($precioNumero, 2, ',', '.');
                        ?>
                        <article class="product-card" aria-labelledby="producto-<?= md5($nombreProducto) ?>">
                            <div class="product-card__image-wrap">
                                <button
                                        class="product-card__wishlist <?= favoritesHas($productoId) ? 'product-card__wishlist--active' : '' ?>"
                                        type="button"
                                        aria-label="<?= favoritesHas($productoId) ? 'Quitar' : 'Añadir' ?> <?= e($nombreProducto) ?> <?= favoritesHas($productoId) ? 'de' : 'a' ?> favoritos"
                                        aria-pressed="<?= favoritesHas($productoId) ? 'true' : 'false' ?>"
                                        data-favorite-product-id="<?= $productoId ?>"
                                        data-product-name="<?= e($nombreProducto) ?>"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
                                </button>
                                <img
                                        src="img/productos/<?= e($imagenProducto) ?>"
                                        alt="<?= e($nombreProducto) ?> de <?= e($marcaProducto) ?>"
                                        class="product-card__image"
                                        width="600"
                                        height="600"
                                        loading="lazy"
                                        decoding="async"
                                        onerror="this.onerror=null;this.src='img/productos/placeholder.jpg'"
                                />
                            </div>
                            <div class="product-card__body">
                                <span class="product-card__brand"><?= e($marcaProducto) ?></span>
                                <h3 class="product-card__name" id="producto-<?= md5($nombreProducto) ?>"><?= e($nombreProducto) ?></h3>
                                <div class="product-card__pricing">
                                    <data class="product-card__price" value="<?= e($precioMaquina) ?>"><?= e($precioVisible) ?> €</data>
                                </div>
                            </div>
                            <div class="product-card__footer">
                                <button
                                        class="product-card__add-btn"
                                        type="button"
                                        aria-label="Añadir <?= e($nombreProducto) ?> al carrito"
                                        data-product-id="<?= $productoId ?>"
                                        data-product-name="<?= e($nombreProducto) ?>"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
                                    Añadir al carrito
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Los productos más vendidos estarán disponibles próximamente.</p>
                <?php endif; ?>
            </div>
            <div class="products__cta">
                <a href="catalogo.php" class="btn btn--outline">Ver todos los productos</a>
            </div>

        </div>
    </section>
    <section class="location" aria-labelledby="location-title">
        <div class="container">

            <div class="section-header">
                <span class="section-header__eyebrow">Encuéntranos</span>
                <h2 class="section-header__title" id="location-title">Visítanos en tienda</h2>
                <p class="section-header__subtitle">También puedes venir en persona. Te atendemos con la misma dedicación.</p>
            </div>

            <div class="location__grid">

                <div class="location__info">

                    <div class="location__status">
                        Atención presencial en Manzanera
                    </div>

                    <div class="location__contact-item">
                        <div class="location__contact-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <div>
                            <p class="location__contact-label">Dirección</p>
                            <p class="location__contact-value">
                                <?= e($streetAddress) ?><br />
                                <?= e($postalCode . ' ' . $locality . ', ' . $region) ?>
                            </p>
                        </div>
                    </div>

                    <div class="location__contact-item">
                        <div class="location__contact-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.8a19.79 19.79 0 01-3.07-8.67A2 2 0 012 .18h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                        </div>
                        <div>
                            <p class="location__contact-label">Teléfono</p>
                            <p class="location__contact-value">
                                <a href="tel:<?= e($phoneE164) ?>"><?= e($phoneDisplay) ?></a>
                            </p>
                        </div>
                    </div>

                    <div class="location__contact-item">
                        <div class="location__contact-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        </div>
                        <div>
                            <p class="location__contact-label">Email</p>
                            <p class="location__contact-value">
                                <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
                            </p>
                        </div>
                    </div>

                    <div class="location__hours">
                        <p class="location__hours-title">Horario de apertura</p>

                        <div class="location__hours-row">
                            <span class="location__hours-day">Lunes — Viernes</span>
                            <span class="location__hours-time"><?= e($horarioVerano) ?><br /> <?= e($horarioVeranoT) ?></span>

                        </div>
                        <div class="location__hours-row">
                            <span class="location__hours-day">Sábados</span>
                            <span class="location__hours-time"><?= e($horarioVeranoV) ?></span>
                        </div>
                        <div class="location__hours-row">
                            <span class="location__hours-day">Domingos</span>
                            <span class="location__hours-closed">Cerrado</span>
                        </div>
                    </div>

                    <a
                            href="<?= e($mapsUrl) ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn btn--primary location__directions-btn"
                            aria-label="Cómo llegar a Boticardo en Manzanera con Google Maps"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
                        Cómo llegar
                    </a>

                </div>

                <div class="location__map-wrap">
                    <iframe
                            src="<?= e($mapsEmbedUrl) ?>"
                            title="Mapa de Boticardo en <?= e($streetAddress . ', ' . $locality) ?>"
                            loading="lazy"
                            allowfullscreen
                            referrerpolicy="no-referrer-when-downgrade"
                    ></iframe>
                </div>

            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
