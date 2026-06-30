<?php
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/favorites.php';

// Detectamos en qué página estamos para marcar el menú activo
$currentPage = basename($_SERVER['PHP_SELF']);
$catActiva = isset($_GET['categoria']) && $_GET['categoria'] !== '' ? (int)$_GET['categoria'] : null;
$cartTotal = cartTotalQuantity();
$favoritesTotal = favoritesCount();
$currentUser = authCurrentUser();
$searchQuery = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
?>
<!DOCTYPE html>
<html lang="es-ES">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= e($pageTitle ?? 'Boticardo') ?></title>
    <meta name="description" content="<?= e($pageDescription ?? '') ?>" />
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
    <meta name="theme-color" content="#6BBFB5" />
    <meta name="csrf-token" content="<?= e(authCsrfToken()) ?>" />

    <?php if (isset($canonicalUrl)): ?>
        <link rel="canonical" href="<?= e($canonicalUrl) ?>" />
        <meta property="og:url" content="<?= e($canonicalUrl) ?>" />
    <?php endif; ?>

    <meta property="og:type" content="website" />
    <meta property="og:locale" content="es_ES" />
    <meta property="og:site_name" content="<?= e($siteName ?? 'Boticardo') ?>" />
    <meta property="og:title" content="<?= e($pageTitle ?? '') ?>" />
    <meta property="og:description" content="<?= e($pageDescription ?? '') ?>" />

    <?php if (!empty($socialImageExists) && !empty($socialImageUrl)): ?>
        <meta property="og:image" content="<?= e($socialImageUrl) ?>" />
        <meta property="og:image:width" content="1200" />
        <meta property="og:image:height" content="630" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:image" content="<?= e($socialImageUrl) ?>" />
    <?php else: ?>
        <meta name="twitter:card" content="summary" />
    <?php endif; ?>

    <meta name="twitter:title" content="<?= e($pageTitle ?? '') ?>" />
    <meta name="twitter:description" content="<?= e($pageDescription ?? '') ?>" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:ital,wght@0,700;1,400&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="styles.css?v=19" />

    <?php if (!empty($structuredData)): ?>
        <script type="application/ld+json"><?= json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <?php endif; ?>
</head>
<body>

<header class="header" role="banner">
    <div class="header__topbar">
        <div class="container">
            <div class="header__topbar-inner">
                <div class="header__topbar-item">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.8a19.79 19.79 0 01-3.07-8.67A2 2 0 012 .18h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                    <a href="tel:<?= e($phoneE164 ?? '') ?>"><?= e($phoneDisplay ?? '') ?></a>
                </div>
            </div>
        </div>
    </div>
    <div class="header__body">
        <div class="container">
            <div class="header__inner">

                <a href="index.php" class="header__logo" aria-label="Boticardo — Ir al inicio">
                    <div class="header__logo-icon" aria-hidden="true" style="border: none; background: transparent; overflow: hidden;">
                        <img src="<?= e($logo ?? 'img/identidad/logo.jpeg') ?>" alt="Logo Boticardo" style="width: 100%; height: 100%; object-fit: contain;" />
                    </div>
                    <div class="header__logo-text">
                        <span class="header__logo-name">Boticardo</span>
                    </div>
                </a>

                <form class="header__search" role="search" action="buscar.php" method="get" autocomplete="off">
                    <label for="search-input" class="visually-hidden">Buscar productos en Boticardo</label>
                    <svg class="header__search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input
                        id="search-input"
                        name="q"
                        class="header__search-input"
                        type="search"
                        value="<?= e($searchQuery) ?>"
                        placeholder="Busca medicamentos, vitaminas, cosmética…"
                        autocomplete="off"
                        enterkeyhint="search"
                        minlength="2"
                        aria-autocomplete="list"
                        aria-controls="search-suggestions"
                    />
                    <button class="header__search-btn" type="submit">Buscar</button>
                    <div class="header__search-suggestions" id="search-suggestions" role="listbox" aria-label="Sugerencias de búsqueda" hidden></div>
                </form>

                <div class="header__actions">
                    <a href="favoritos.php" id="favorites-link" class="header__action-btn header__favorite-link" aria-label="<?= $favoritesTotal > 0 ? e('Favoritos (' . $favoritesTotal . ' ' . ($favoritesTotal === 1 ? 'producto' : 'productos') . ')') : 'Favoritos vacíos' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
                        <span
                            id="favorites-count"
                            class="header__cart-badge header__favorites-badge"
                            aria-hidden="true"
                            <?= $favoritesTotal > 0 ? '' : 'hidden style="display: none;"' ?>
                        ><?= $favoritesTotal ?></span>
                    </a>
                    <span id="favorites-status" class="visually-hidden" aria-live="polite"></span>
                    <a href="carrito.php" id="cart-link" class="header__action-btn" aria-label="<?= $cartTotal > 0 ? e('Carrito de compra (' . $cartTotal . ' ' . ($cartTotal === 1 ? 'producto' : 'productos') . ')') : 'Carrito vacío' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
                        <span
                            id="cart-count"
                            class="header__cart-badge"
                            aria-hidden="true"
                            <?= $cartTotal > 0 ? '' : 'hidden style="display: none;"' ?>
                        ><?= $cartTotal ?></span>
                    </a>
                    <span id="cart-status" class="visually-hidden" aria-live="polite"></span>
                    <?php if ($currentUser): ?>
                        <div class="header__account">
                            <a href="cuenta.php" class="header__user-btn" aria-label="Mi cuenta en Boticardo">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                <span><?= e($currentUser['nombre'] ?: 'Mi cuenta') ?></span>
                            </a>
                            <?php if (function_exists('authIsAdmin') && authIsAdmin()): ?>
                                <a href="admin/index.php" class="header__admin-link">Admin</a>
                            <?php endif; ?>
                            <a href="logout.php" class="header__logout-link">Salir</a>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="header__user-btn" aria-label="Iniciar sesión en Boticardo">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <span>Iniciar sesión</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</header>

<nav class="subnav" aria-label="Categorías principales">
    <div class="container">
        <ul class="subnav__list" role="list">
            <li class="subnav__item">
                <a href="catalogo.php" class="subnav__link <?= ($currentPage === 'catalogo.php' && $catActiva === null) ? 'subnav__link--active' : '' ?>">
                    Ver Todo
                </a>
            </li>
            <li class="subnav__item">
                <a href="catalogo.php?categoria=1" class="subnav__link <?= ($catActiva === 1) ? 'subnav__link--active' : '' ?>">
                    Medicamentos
                </a>
            </li>
            <li class="subnav__item">
                <a href="catalogo.php?categoria=2" class="subnav__link <?= ($catActiva === 2) ? 'subnav__link--active' : '' ?>">
                    Dermocosmética
                </a>
            </li>
            <li class="subnav__item">
                <a href="catalogo.php?categoria=3" class="subnav__link <?= ($catActiva === 3) ? 'subnav__link--active' : '' ?>">
                    Vitaminas
                </a>
            </li>
            <li class="subnav__item">
                <a href="catalogo.php?categoria=4" class="subnav__link <?= ($catActiva === 4) ? 'subnav__link--active' : '' ?>">
                    Bebé y Mamá
                </a>
            </li>
            <li class="subnav__item">
                <a href="catalogo.php?categoria=5" class="subnav__link <?= ($catActiva === 5) ? 'subnav__link--active' : '' ?>">
                    Higiene
                </a>
            </li>
            <li class="subnav__item">
                <a href="ofertas.php" class="subnav__link <?= ($currentPage === 'ofertas.php') ? 'subnav__link--active' : '' ?>">
                    Ofertas <span class="subnav__badge">-10%</span>
                </a>
            </li>
        </ul>
    </div>
</nav>