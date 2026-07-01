<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/favorites.php';
require_once __DIR__ . '/includes/orders.php';
require_once __DIR__ . '/includes/account.php';
require_once __DIR__ . '/includes/db.php';

if (!authIsLoggedIn()) {
    header('Location: login.php?redirect=cuenta.php');
    exit;
}

$currentUser = authCurrentUser();
$userId = (int) ($currentUser['id'] ?? 0);

accountEnsureTables($conn);
accountSyncSessionWithDatabase($conn, $userId);

$successMessage = '';
$errorMessage = '';
$activeSection = (string) ($_GET['section'] ?? 'resumen');

function cuentaFormatMoney(float $amount): string
{
    return number_format($amount, 2, ',', '.') . ' €';
}

function cuentaFormatDate(?string $date): string
{
    if (!$date) {
        return '—';
    }

    $timestamp = strtotime($date);
    return $timestamp ? date('d/m/Y H:i', $timestamp) : '—';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $activeSection = (string) ($_POST['section'] ?? $activeSection);

    if (!authValidateCsrf($_POST['csrf_token'] ?? null)) {
        $errorMessage = 'La sesión ha caducado. Recarga la página e inténtalo de nuevo.';
    } else {
        try {
            $result = match ($action) {
                'update_profile' => accountUpdateProfile($conn, $userId, $_POST),
                'change_password' => accountChangePassword($conn, $userId, $_POST),
                'save_address' => accountSaveAddress($conn, $userId, $_POST),
                'delete_address' => accountDeleteAddress($conn, $userId, (int) ($_POST['address_id'] ?? 0)),
                'set_default_address' => accountSetDefaultAddress($conn, $userId, (int) ($_POST['address_id'] ?? 0)),
                default => ['ok' => false, 'message' => 'Acción no válida.'],
            };

            if (!empty($result['ok'])) {
                $successMessage = (string) $result['message'];
            } else {
                $errorMessage = (string) $result['message'];
            }
        } catch (Throwable $error) {
            error_log('Boticardo - Error en cuenta.php: ' . $error->getMessage());
            $errorMessage = 'No se pudo completar la operación. Inténtalo de nuevo.';
        }
    }
}

$profileUser = accountCurrentUserFresh($conn, $userId) ?? $currentUser;
accountRefreshSessionUser($profileUser);
$addresses = accountGetAddresses($conn, $userId);
$defaultAddress = accountGetDefaultAddress($conn, $userId);
$recentOrders = accountGetRecentOrders($conn, $userId, 5);
$favoriteProducts = accountGetFavoriteProducts($conn, $userId, 6);
$cartSummary = cartSummary($conn);
$cartItems = $cartSummary['items'];
$cartQuantity = (int) $cartSummary['quantity'];
$cartSubtotal = (float) $cartSummary['subtotal'];
$csrfToken = authCsrfToken();

$conn->close();

$pageTitle = 'Mi cuenta | Boticardo';
$pageDescription = 'Gestiona tus datos, direcciones, contraseña, pedidos, favoritos y carrito guardado en Boticardo.';
$canonicalUrl = $siteUrl . '/cuenta.php';

require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content">
    <section class="account-page">
        <div class="container">
            <div class="section-header account-page__header">
                <span class="section-header__eyebrow">Mi cuenta</span>
                <h1 class="section-header__title">Hola, <?= e($profileUser['nombre'] ?? 'cliente') ?></h1>
                <p class="section-header__subtitle">Gestiona tus datos, direcciones, pedidos, favoritos y carrito guardado.</p>
            </div>

            <?php if ($successMessage !== ''): ?>
                <div class="account-alert account-alert--success" role="status"><?= e($successMessage) ?></div>
            <?php endif; ?>

            <?php if ($errorMessage !== ''): ?>
                <div class="account-alert account-alert--error" role="alert"><?= e($errorMessage) ?></div>
            <?php endif; ?>

            <div class="account-layout">
                <aside class="account-sidebar" aria-label="Menú de mi cuenta">
                    <a href="#resumen" class="account-sidebar__link">Resumen</a>
                    <a href="#datos" class="account-sidebar__link">Editar datos</a>
                    <a href="#password" class="account-sidebar__link">Cambiar contraseña</a>
                    <a href="#direcciones" class="account-sidebar__link">Direcciones</a>
                    <a href="#pedidos" class="account-sidebar__link">Mis pedidos</a>
                    <a href="#favoritos" class="account-sidebar__link">Favoritos</a>
                    <a href="#carrito-guardado" class="account-sidebar__link">Carrito guardado</a>
                    <a href="logout.php" class="account-sidebar__link account-sidebar__link--danger">Cerrar sesión</a>
                </aside>

                <div class="account-content">
                    <section id="resumen" class="account-panel account-panel--hero">
                        <div>
                            <span class="account-panel__eyebrow">Resumen</span>
                            <h2>Tu espacio personal</h2>
                            <p>Tu cuenta mantiene guardados el carrito, favoritos, direcciones y pedidos para que puedas continuar desde otro inicio de sesión.</p>
                        </div>
                        <div class="account-stats-grid" aria-label="Resumen de la cuenta">
                            <div class="account-stat-card">
                                <strong><?= count($recentOrders) ?></strong>
                                <span>pedidos recientes</span>
                            </div>
                            <div class="account-stat-card">
                                <strong><?= count($favoriteProducts) ?></strong>
                                <span>favoritos visibles</span>
                            </div>
                            <div class="account-stat-card">
                                <strong><?= $cartQuantity ?></strong>
                                <span>productos en carrito</span>
                            </div>
                            <div class="account-stat-card">
                                <strong><?= count($addresses) ?></strong>
                                <span>direcciones guardadas</span>
                            </div>
                        </div>
                    </section>

                    <section id="datos" class="account-panel">
                        <div class="account-panel__header">
                            <div>
                                <span class="account-panel__eyebrow">Datos personales</span>
                                <h2>Editar datos personales</h2>
                            </div>
                        </div>

                        <form action="cuenta.php#datos" method="post" class="account-form">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="action" value="update_profile">
                            <input type="hidden" name="section" value="datos">

                            <div class="account-form__grid">
                                <div class="account-field account-field--full">
                                    <label for="profile-nombre">Nombre y apellidos</label>
                                    <input id="profile-nombre" name="nombre" type="text" value="<?= e((string) ($profileUser['nombre'] ?? '')) ?>" required maxlength="120" autocomplete="name">
                                </div>

                                <div class="account-field">
                                    <label for="profile-email">Email</label>
                                    <input id="profile-email" type="email" value="<?= e((string) ($profileUser['email'] ?? '')) ?>" disabled>
                                    <small>El email está vinculado a la verificación de la cuenta.</small>
                                </div>

                                <div class="account-field">
                                    <label for="profile-telefono">Teléfono</label>
                                    <input id="profile-telefono" name="telefono" type="tel" value="<?= e((string) ($profileUser['telefono'] ?? '')) ?>" maxlength="30" autocomplete="tel" placeholder="Ej. 600 123 123">
                                </div>

                                <div class="account-field account-field--full">
                                    <label for="profile-dni">DNI/NIF para factura, si lo necesitas</label>
                                    <input id="profile-dni" name="dni_nif" type="text" value="<?= e((string) ($profileUser['dni_nif'] ?? '')) ?>" maxlength="20" autocomplete="off">
                                </div>
                            </div>

                            <button type="submit" class="btn btn--primary">Guardar datos</button>
                        </form>
                    </section>

                    <section id="password" class="account-panel">
                        <div class="account-panel__header">
                            <div>
                                <span class="account-panel__eyebrow">Seguridad</span>
                                <h2>Cambiar contraseña</h2>
                            </div>
                        </div>

                        <form action="cuenta.php#password" method="post" class="account-form">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="action" value="change_password">
                            <input type="hidden" name="section" value="password">

                            <div class="account-form__grid">
                                <div class="account-field account-field--full">
                                    <label for="current-password">Contraseña actual</label>
                                    <input id="current-password" name="current_password" type="password" autocomplete="current-password">
                                    <small>Si tu cuenta viene de Google/Apple y todavía no tenía contraseña, deja este campo vacío.</small>
                                </div>

                                <div class="account-field">
                                    <label for="new-password">Nueva contraseña</label>
                                    <input id="new-password" name="new_password" type="password" minlength="8" autocomplete="new-password" required>
                                </div>

                                <div class="account-field">
                                    <label for="new-password-confirm">Repetir nueva contraseña</label>
                                    <input id="new-password-confirm" name="new_password_confirm" type="password" minlength="8" autocomplete="new-password" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn--primary">Actualizar contraseña</button>
                        </form>
                    </section>

                    <section id="direcciones" class="account-panel">
                        <div class="account-panel__header">
                            <div>
                                <span class="account-panel__eyebrow">Envíos</span>
                                <h2>Direcciones guardadas</h2>
                            </div>
                        </div>

                        <?php if ($addresses === []): ?>
                            <p class="account-muted">Todavía no tienes direcciones guardadas.</p>
                        <?php else: ?>
                            <div class="account-address-list">
                                <?php foreach ($addresses as $address): ?>
                                    <article class="account-address-card <?= (int) ($address['es_principal'] ?? 0) === 1 ? 'account-address-card--main' : '' ?>">
                                        <div>
                                            <span class="account-address-card__alias"><?= e((string) $address['alias']) ?></span>
                                            <?php if ((int) ($address['es_principal'] ?? 0) === 1): ?>
                                                <span class="account-pill">Principal</span>
                                            <?php endif; ?>
                                            <p>
                                                <strong><?= e((string) $address['nombre']) ?></strong><br>
                                                <?= e((string) $address['direccion']) ?><br>
                                                <?= e((string) $address['codigo_postal']) ?> <?= e((string) $address['localidad']) ?>, <?= e((string) $address['provincia']) ?><br>
                                                Tel. <?= e((string) $address['telefono']) ?>
                                            </p>
                                        </div>
                                        <div class="account-address-card__actions">
                                            <?php if ((int) ($address['es_principal'] ?? 0) !== 1): ?>
                                                <form action="cuenta.php#direcciones" method="post">
                                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                    <input type="hidden" name="action" value="set_default_address">
                                                    <input type="hidden" name="section" value="direcciones">
                                                    <input type="hidden" name="address_id" value="<?= (int) $address['id'] ?>">
                                                    <button type="submit" class="account-link-button">Hacer principal</button>
                                                </form>
                                            <?php endif; ?>
                                            <form action="cuenta.php#direcciones" method="post" onsubmit="return confirm('¿Eliminar esta dirección?');">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                <input type="hidden" name="action" value="delete_address">
                                                <input type="hidden" name="section" value="direcciones">
                                                <input type="hidden" name="address_id" value="<?= (int) $address['id'] ?>">
                                                <button type="submit" class="account-link-button account-link-button--danger">Eliminar</button>
                                            </form>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <details class="account-details" <?= $addresses === [] ? 'open' : '' ?>>
                            <summary>Añadir nueva dirección</summary>
                            <form action="cuenta.php#direcciones" method="post" class="account-form account-form--nested">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                <input type="hidden" name="action" value="save_address">
                                <input type="hidden" name="section" value="direcciones">

                                <div class="account-form__grid">
                                    <div class="account-field">
                                        <label for="address-alias">Nombre de la dirección</label>
                                        <input id="address-alias" name="alias" type="text" maxlength="80" placeholder="Casa, trabajo..." value="Principal">
                                    </div>

                                    <div class="account-field">
                                        <label for="address-nombre">Nombre receptor</label>
                                        <input id="address-nombre" name="nombre" type="text" maxlength="160" required value="<?= e((string) ($profileUser['nombre'] ?? '')) ?>" autocomplete="name">
                                    </div>

                                    <div class="account-field">
                                        <label for="address-telefono">Teléfono</label>
                                        <input id="address-telefono" name="telefono" type="tel" maxlength="30" required value="<?= e((string) ($profileUser['telefono'] ?? '')) ?>" autocomplete="tel">
                                    </div>

                                    <div class="account-field account-field--full">
                                        <label for="address-direccion">Dirección</label>
                                        <input id="address-direccion" name="direccion" type="text" maxlength="255" required autocomplete="street-address" placeholder="Calle, número, piso...">
                                    </div>

                                    <div class="account-field">
                                        <label for="address-cp">Código postal</label>
                                        <input id="address-cp" name="codigo_postal" type="text" maxlength="5" inputmode="numeric" required autocomplete="postal-code">
                                    </div>

                                    <div class="account-field">
                                        <label for="address-localidad">Localidad</label>
                                        <input id="address-localidad" name="localidad" type="text" maxlength="120" required autocomplete="address-level2">
                                    </div>

                                    <div class="account-field account-field--full">
                                        <label for="address-provincia">Provincia</label>
                                        <input id="address-provincia" name="provincia" type="text" maxlength="120" required autocomplete="address-level1">
                                    </div>
                                </div>

                                <label class="account-checkbox">
                                    <input type="checkbox" name="es_principal" value="1" <?= $addresses === [] ? 'checked' : '' ?>>
                                    <span>Usar como dirección principal</span>
                                </label>

                                <button type="submit" class="btn btn--primary">Guardar dirección</button>
                            </form>
                        </details>
                    </section>

                    <section id="pedidos" class="account-panel">
                        <div class="account-panel__header">
                            <div>
                                <span class="account-panel__eyebrow">Compras</span>
                                <h2>Mis pedidos</h2>
                            </div>
                            <a href="pedidos.php" class="btn btn--outline">Ver todos</a>
                        </div>

                        <?php if ($recentOrders === []): ?>
                            <p class="account-muted">Todavía no tienes pedidos. Cuando hagas una compra, aparecerá aquí.</p>
                        <?php else: ?>
                            <div class="account-order-list">
                                <?php foreach ($recentOrders as $order): ?>
                                    <?php $status = (string) ($order['estado'] ?? 'pendiente'); ?>
                                    <article class="account-order-row">
                                        <div>
                                            <strong>Pedido #<?= (int) $order['id'] ?></strong>
                                            <span><?= e(cuentaFormatDate((string) ($order['created_at'] ?? ''))) ?> · <?= e(orderStatusLabel($status)) ?></span>
                                        </div>
                                        <div class="account-order-row__actions">
                                            <strong><?= e(cuentaFormatMoney((float) ($order['total'] ?? 0))) ?></strong>
                                            <a href="descargar-justificante.php?pedido=<?= e(rawurlencode((string) ($order['public_id'] ?? ''))) ?>" class="account-download-link">Descargar justificante</a>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section id="favoritos" class="account-panel">
                        <div class="account-panel__header">
                            <div>
                                <span class="account-panel__eyebrow">Guardado en BBDD</span>
                                <h2>Mis favoritos</h2>
                            </div>
                            <a href="favoritos.php" class="btn btn--outline">Ver favoritos</a>
                        </div>

                        <?php if ($favoriteProducts === []): ?>
                            <p class="account-muted">No tienes favoritos guardados todavía.</p>
                        <?php else: ?>
                            <div class="account-product-list">
                                <?php foreach ($favoriteProducts as $product): ?>
                                    <a href="producto.php?id=<?= (int) $product['id'] ?>" class="account-product-row">
                                        <img src="img/productos/<?= e(basename((string) ($product['imagen'] ?? 'placeholder.jpg'))) ?>" alt="<?= e((string) $product['nombre']) ?>" loading="lazy" onerror="this.onerror=null;this.src='img/productos/placeholder.jpg';">
                                        <span>
                                            <strong><?= e((string) $product['nombre']) ?></strong>
                                            <small><?= e((string) ($product['marca'] ?? 'Boticardo')) ?> · <?= e(productStockLabel($product)) ?></small>
                                        </span>
                                        <b><?= e(cuentaFormatMoney((float) $product['precio'])) ?></b>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section id="carrito-guardado" class="account-panel">
                        <div class="account-panel__header">
                            <div>
                                <span class="account-panel__eyebrow">Guardado en BBDD</span>
                                <h2>Carrito guardado</h2>
                            </div>
                            <a href="carrito.php" class="btn btn--outline">Ir al carrito</a>
                        </div>

                        <?php if ($cartItems === []): ?>
                            <p class="account-muted">Tu carrito guardado está vacío.</p>
                        <?php else: ?>
                            <div class="account-product-list">
                                <?php foreach ($cartItems as $item): ?>
                                    <a href="producto.php?id=<?= (int) $item['id'] ?>" class="account-product-row">
                                        <img src="img/productos/<?= e(basename((string) ($item['imagen'] ?? 'placeholder.jpg'))) ?>" alt="<?= e((string) $item['nombre']) ?>" loading="lazy" onerror="this.onerror=null;this.src='img/productos/placeholder.jpg';">
                                        <span>
                                            <strong><?= e((string) $item['nombre']) ?></strong>
                                            <small><?= (int) $item['quantity'] ?> unidad<?= (int) $item['quantity'] === 1 ? '' : 'es' ?> · <?= e((string) ($item['marca'] ?? 'Boticardo')) ?></small>
                                        </span>
                                        <b><?= e(cuentaFormatMoney((float) $item['subtotal'])) ?></b>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                            <div class="account-cart-total">
                                <span>Total del carrito</span>
                                <strong><?= e(cuentaFormatMoney($cartSubtotal)) ?></strong>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
