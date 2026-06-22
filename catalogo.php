<?php
// Cargamos toda la lógica desde nuestros archivos separados
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/schema.php';

/**
 * Declaración para el IDE: Estas variables provienen de config.php y schema.php
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

// Sobrescribimos el título y la descripción específicamente para el catálogo
$pageTitle = 'Catálogo de productos | Boticardo';
$pageDescription = 'Descubre nuestro catálogo completo de medicamentos, dermocosmética, vitaminas y mucho más en Farmacia Boticardo.';

// Ejecutamos la función para obtener TODOS los productos
$todosLosProductos = getAllProductos($conn);
$conn->close();
?>
<!DOCTYPE html>
<html lang="es-ES">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>" />
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
    <meta name="referrer" content="strict-origin-when-cross-origin" />
    <meta name="theme-color" content="#6BBFB5" />
    <link rel="canonical" href="<?= e($canonicalUrl) ?>catalogo" />
    <meta property="og:type" content="website" />
    <meta property="og:locale" content="es_ES" />
    <meta property="og:site_name" content="<?= e($siteName) ?>" />
    <meta property="og:title" content="<?= e($pageTitle) ?>" />
    <meta property="og:description" content="<?= e($pageDescription) ?>" />
    <meta property="og:url" content="<?= e($canonicalUrl) ?>catalogo" />
    <?php if ($socialImageExists): ?>
        <meta property="og:image" content="<?= e($socialImageUrl) ?>" />
        <meta property="og:image:width" content="1200" />
        <meta property="og:image:height" content="630" />
        <meta property="og:image:alt" content="Catálogo de productos de Boticardo" />
    <?php endif; ?>

    <meta name="twitter:card" content="<?= $socialImageExists ? 'summary_large_image' : 'summary' ?>" />
    <meta name="twitter:title" content="<?= e($pageTitle) ?>" />
    <meta name="twitter:description" content="<?= e($pageDescription) ?>" />
    <?php if ($socialImageExists): ?>
        <meta name="twitter:image" content="<?= e($socialImageUrl) ?>" />
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:ital,wght@0,700;1,400&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="styles.css" />
    <script type="application/ld+json"><?= json_encode(
                $structuredData,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES |
                JSON_HEX_TAG |
                JSON_HEX_AMP |
                JSON_HEX_APOS |
                JSON_HEX_QUOT
        ) ?></script>
</head>
<body>

<header class="header" role="banner">
    <div class="header__topbar">
        <div class="container">
            <div class="header__topbar-inner">

                <div class="header__topbar-item">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.8a19.79 19.79 0 01-3.07-8.67A2 2 0 012 .18h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                    <a href="tel:<?= e($phoneE164) ?>"><?= e($phoneDisplay) ?></a>
                </div>

            </div>
        </div>
    </div>
    <div class="header__body">
        <div class="container">
            <div class="header__inner">

                <a href="/" class="header__logo" aria-label="Boticardo — Ir al inicio">
                    <div class="header__logo-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4M16 2v4M8 18v4M16 18v4M2 8h4M18 8h4M2 16h4M18 16h4"/><rect x="6" y="6" width="12" height="12" rx="2"/></svg>
                    </div>
                    <div class="header__logo-text">
                        <span class="header__logo-name">Boticardo</span>
                    </div>
                </a>

                <form class="header__search" role="search" action="/buscar" method="get">
                    <label for="search-input" class="visually-hidden">Buscar productos en Boticardo</label>
                    <svg class="header__search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input id="search-input" name="q" class="header__search-input" type="search" placeholder="Busca medicamentos, vitaminas, cosmética…" autocomplete="off" enterkeyhint="search" minlength="2"/>
                    <button class="header__search-btn" type="submit">Buscar</button>
                </form>
                <div class="header__actions">
                    <a href="/carrito" id="cart-link" class="header__action-btn" aria-label="Carrito vacío">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
                        <span id="cart-count" class="header__cart-badge" aria-hidden="true" hidden style="display: none;">0</span>
                    </a>
                    <span id="cart-status" class="visually-hidden" aria-live="polite"></span>
                    <a href="/login" class="header__user-btn" aria-label="Iniciar sesión en Boticardo">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <span>Iniciar sesión</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<nav class="subnav" aria-label="Categorías principales">
    <div class="container">
        <ul class="subnav__list" role="list">
            <li class="subnav__item">
                <a href="/ofertas" class="subnav__link">
                    <span class="subnav__link-icon" aria-hidden="true"></span>
                    Ofertas
                    <span class="subnav__badge" aria-label="Sección con descuentos">-10%</span>
                </a>
            </li>

            <li class="subnav__item">
                <a href="/medicamentos" class="subnav__link">
                    <span class="subnav__link-icon" aria-hidden="true"></span>
                    Medicamentos
                </a>
            </li>


            <li class="subnav__item">
                <a href="/dermocosmetica" class="subnav__link">
                    <span class="subnav__link-icon" aria-hidden="true"></span>
                    Dermocosmética
                </a>
            </li>

            <li class="subnav__item">
                <a href="/vitaminas" class="subnav__link">
                    <span class="subnav__link-icon" aria-hidden="true"></span>
                    Vitaminas
                </a>
            </li>

            <li class="subnav__item">
                <a href="/bebe" class="subnav__link">
                    <span class="subnav__link-icon" aria-hidden="true"></span>
                    Bebé y Mamá
                </a>
            </li>

            <li class="subnav__item">
                <a href="/higiene" class="subnav__link">
                    <span class="subnav__link-icon" aria-hidden="true"></span>
                    Higiene
                </a>
            </li>

            <li class="subnav__item">
                <a href="/consejo" class="subnav__link">
                    <span class="subnav__link-icon" aria-hidden="true"></span>
                    Consejo Farmacéutico
                </a>
            </li>

        </ul>
    </div>
</nav>

<main id="main-content">

    <section class="products" aria-labelledby="products-title" style="padding-top: 3rem;">
        <div class="container">

            <div class="section-header">
                <span class="section-header__eyebrow">Nuestro catálogo</span>
                <h1 class="section-header__title" id="products-title" style="font-size: var(--text-3xl);">Todos los productos</h1>
                <p class="section-header__subtitle">Encuentra todo lo que necesitas para tu salud y bienestar, organizado alfabéticamente.</p>
            </div>

            <div class="products__grid">
                <?php if ($todosLosProductos): ?>
                    <?php foreach ($todosLosProductos as $producto): ?>
                        <?php
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
                                        class="product-card__wishlist"
                                        type="button"
                                        aria-label="Añadir <?= e($nombreProducto) ?> a favoritos"
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
                                <h2 class="product-card__name" id="producto-<?= md5($nombreProducto) ?>"><?= e($nombreProducto) ?></h2>
                                <div class="product-card__pricing">
                                    <data class="product-card__price" value="<?= e($precioMaquina) ?>"><?= e($precioVisible) ?> €</data>
                                </div>
                            </div>
                            <div class="product-card__footer">
                                <button
                                        class="product-card__add-btn"
                                        type="button"
                                        aria-label="Añadir <?= e($nombreProducto) ?> al carrito"
                                        data-product-name="<?= e($nombreProducto) ?>"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
                                    Añadir al carrito
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Actualmente no hay productos disponibles en el catálogo.</p>
                <?php endif; ?>
            </div>

        </div>
    </section>
</main>

<footer class="footer" role="contentinfo">
    <div class="container">

        <div class="footer__grid">

            <div class="footer__brand">
                <p class="footer__brand-name">Boticardo</p>
                <p class="footer__brand-desc">
                    Tu farmacia de confianza, ahora también online. Más de 10.000 productos
                    con el asesoramiento de farmacéuticos colegiados.
                </p>
            </div>

            <div>
                <p class="footer__col-title">Comprar</p>
                <ul class="footer__links" role="list">
                    <li><a href="/medicamentos" class="footer__link">Medicamentos</a></li>
                    <li><a href="/dermocosmetica" class="footer__link">Dermocosmética</a></li>
                    <li><a href="/vitaminas" class="footer__link">Vitaminas</a></li>
                    <li><a href="/bebe" class="footer__link">Bebé y Mamá</a></li>
                    <li><a href="/ofertas" class="footer__link">Ofertas</a></li>
                </ul>
            </div>

            <div>
                <p class="footer__col-title">Ayuda</p>
                <ul class="footer__links" role="list">
                    <li><a href="/consejo" class="footer__link">Consejo farmacéutico</a></li>
                    <li><a href="/envios" class="footer__link">Envíos y entregas</a></li>
                    <li><a href="/devoluciones" class="footer__link">Devoluciones</a></li>
                    <li><a href="/faq" class="footer__link">Preguntas frecuentes</a></li>
                    <li><a href="/contacto" class="footer__link">Contacto</a></li>
                </ul>
            </div>

            <div>
                <p class="footer__col-title">Mi cuenta</p>
                <ul class="footer__links" role="list">
                    <li><a href="/login" class="footer__link">Iniciar sesión</a></li>
                    <li><a href="/registro" class="footer__link">Crear cuenta</a></li>
                    <li><a href="/pedidos" class="footer__link">Mis pedidos</a></li>
                    <li><a href="/receta" class="footer__link">Receta electrónica</a></li>
                    <li><a href="/favoritos" class="footer__link">Mis favoritos</a></li>
                </ul>
            </div>

        </div>

        <div class="footer__bottom">
            <p class="footer__copy">© <?= date('Y') ?> Boticardo. Todos los derechos reservados.</p>
            <nav class="footer__legal-links" aria-label="Avisos legales">
                <a href="/privacidad" class="footer__legal-link">Política de privacidad</a>
                <a href="/cookies" class="footer__legal-link">Cookies</a>
                <a href="/aviso-legal" class="footer__legal-link">Aviso legal</a>
            </nav>
        </div>

    </div>
</footer>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const cartLink = document.getElementById('cart-link');
        const cartBadge = document.getElementById('cart-count');
        const cartStatus = document.getElementById('cart-status');
        const addToCartButtons = document.querySelectorAll('.product-card__add-btn');
        const storageKey = 'boticardoCartCount';

        if (!cartLink || !cartBadge) {
            return;
        }

        function readStoredCount() {
            try {
                const storedValue = localStorage.getItem(storageKey);
                const parsedValue = Number.parseInt(storedValue ?? '0', 10);

                return Number.isFinite(parsedValue) && parsedValue > 0
                    ? parsedValue
                    : 0;
            } catch (error) {
                return 0;
            }
        }

        function saveCount(count) {
            try {
                localStorage.setItem(storageKey, String(count));
            } catch (error) {
            }
        }

        function updateCartBadge(count) {
            const safeCount = Math.max(0, count);

            cartBadge.textContent = String(safeCount);

            if (safeCount === 0) {
                cartBadge.hidden = true;
                cartBadge.style.display = 'none';
                cartLink.setAttribute('aria-label', 'Carrito vacío');
                return;
            }

            cartBadge.hidden = false;
            cartBadge.style.display = 'flex';
            cartLink.setAttribute(
                'aria-label',
                `Carrito de compra (${safeCount} ${safeCount === 1 ? 'producto' : 'productos'})`
            );
        }

        let cartCount = readStoredCount();
        updateCartBadge(cartCount);

        addToCartButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                cartCount += 1;
                saveCount(cartCount);
                updateCartBadge(cartCount);

                if (cartStatus) {
                    const productName = button.dataset.productName || 'Producto';
                    cartStatus.textContent = `${productName} añadido al carrito. Total: ${cartCount}.`;
                }
            });
        });
    });
</script>
</body>
</html>