<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/orders.php';
require_once __DIR__ . '/includes/account.php';
require_once __DIR__ . '/includes/stripe_checkout.php';
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Finalizar compra | Boticardo';
$pageDescription = 'Revisa tus datos y paga de forma segura con tarjeta bancaria o Bizum en Boticardo.';
$canonicalUrl = $siteUrl . '/checkout.php';

$currentUser = authCurrentUser();
if (!$currentUser) {
    header('Location: login.php?redirect=checkout.php');
    exit;
}

require_once __DIR__ . '/includes/schema.php';

accountSyncSessionWithDatabase($conn, (int) $currentUser['id']);
$defaultAddress = accountGetDefaultAddress($conn, (int) $currentUser['id']);

$cartSummary = cartSummary($conn);
$cartItems = $cartSummary['items'];
$cartQuantity = (int) $cartSummary['quantity'];
$cartSubtotal = (float) $cartSummary['subtotal'];
$homeShippingCost = orderCalculateShippingCost($cartSubtotal, 'domicilio');
$pickupShippingCost = orderCalculateShippingCost($cartSubtotal, 'recogida');
$shippingCost = $homeShippingCost;
$cartTotal = round($cartSubtotal + $shippingCost, 2);
$conn->close();

if ($cartItems === []) {
    header('Location: carrito.php');
    exit;
}

$stripeConfigured = stripeIsConfigured();
$csrfToken = authCsrfToken();

require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content">
    <section class="checkout-page">
        <div class="container">
            <div class="section-header">
                <span class="section-header__eyebrow">Pago seguro</span>
                <h1 class="section-header__title">Finalizar compra</h1>
                <p class="section-header__subtitle">
                    Revisa tus datos. El pago con tarjeta o Bizum se hará fuera de Boticardo, en la página segura de Stripe.
                </p>
            </div>

            <?php if (!$stripeConfigured): ?>
                <div class="cart-notice cart-notice--warning" role="alert">
                    Puedes ver la página, pero el botón de pago está desactivado.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="cart-notice cart-notice--warning" role="alert">
                    <?= e((string) $_GET['error']) ?>
                </div>
            <?php endif; ?>

            <div class="checkout-layout">
                <form class="checkout-form" action="checkout/crear_sesion_stripe.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

                    <section class="checkout-card">
                        <h2>Método de entrega</h2>
                        <div class="checkout-delivery-options" role="radiogroup" aria-label="Método de entrega">
                            <label class="checkout-delivery-option">
                                <input type="radio" name="metodo_entrega" value="domicilio" checked>
                                <span>
                                    <strong>Envío a domicilio</strong>
                                    <small><?= $homeShippingCost === 0.0 ? 'Gratis' : number_format($homeShippingCost, 2, ',', '.') . ' €' ?></small>
                                </span>
                            </label>
                            <label class="checkout-delivery-option">
                                <input type="radio" name="metodo_entrega" value="recogida">
                                <span>
                                    <strong>Recoger en farmacia</strong>
                                    <small>Sin gastos de envío</small>
                                </span>
                            </label>
                        </div>
                        <p class="checkout-delivery-note" id="checkout-delivery-note">
                            Selecciona recogida en farmacia si prefieres pasar a por el pedido. Te avisaremos cuando esté listo.
                        </p>
                    </section>

                    <section class="checkout-card">
                        <h2>Datos de contacto y entrega</h2>

                        <div class="checkout-grid">
                            <div class="checkout-field checkout-field--full">
                                <label for="nombre_envio">Nombre y apellidos</label>
                                <input id="nombre_envio" name="nombre_envio" type="text" value="<?= e((string) ($defaultAddress['nombre'] ?? $currentUser['nombre'] ?? '')) ?>" required maxlength="160" autocomplete="name">
                            </div>

                            <div class="checkout-field">
                                <label for="email_envio">Email</label>
                                <input id="email_envio" name="email_envio" type="email" value="<?= e((string) ($currentUser['email'] ?? '')) ?>" required maxlength="190" autocomplete="email">
                            </div>

                            <div class="checkout-field">
                                <label for="telefono_envio">Teléfono</label>
                                <input id="telefono_envio" name="telefono_envio" type="tel" value="<?= e((string) ($defaultAddress['telefono'] ?? $currentUser['telefono'] ?? '')) ?>" required maxlength="30" autocomplete="tel" placeholder="Ej. 600 123 123">
                            </div>

                            <div class="checkout-address-fields" id="checkout-address-fields">
                                <div class="checkout-field checkout-field--full">
                                    <label for="direccion_envio">Dirección</label>
                                    <input id="direccion_envio" name="direccion_envio" type="text" value="<?= e((string) ($defaultAddress['direccion'] ?? '')) ?>" required maxlength="255" autocomplete="street-address" placeholder="Calle, número, piso...">
                                </div>

                                <div class="checkout-field">
                                    <label for="codigo_postal">Código postal</label>
                                    <input id="codigo_postal" name="codigo_postal" type="text" value="<?= e((string) ($defaultAddress['codigo_postal'] ?? '')) ?>" required maxlength="5" inputmode="numeric" autocomplete="postal-code">
                                </div>

                                <div class="checkout-field">
                                    <label for="localidad">Localidad</label>
                                    <input id="localidad" name="localidad" type="text" value="<?= e((string) ($defaultAddress['localidad'] ?? '')) ?>" required maxlength="120" autocomplete="address-level2">
                                </div>

                                <div class="checkout-field checkout-field--full">
                                    <label for="provincia">Provincia</label>
                                    <input id="provincia" name="provincia" type="text" value="<?= e((string) ($defaultAddress['provincia'] ?? '')) ?>" required maxlength="120" autocomplete="address-level1">
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="checkout-card">
                        <h2>Método de pago</h2>
                        <div class="payment-method-box">
                            <div class="payment-method-box__icons" aria-hidden="true">
                            </div>
                            <div>
                                <strong>Tarjeta bancaria o Bizum</strong>
                                <p>Al pulsar pagar se abrirá Stripe Checkout. Allí podrás elegir tarjeta o Bizum si está activo en tu cuenta de Stripe.</p>
                            </div>
                        </div>

                        <label class="checkout-terms">
                            <input type="checkbox" name="accept_terms" value="1" required>
                            <span>Confirmo que los datos del pedido son correctos y acepto continuar al pago seguro.</span>
                        </label>
                    </section>

                    <button class="btn btn--primary checkout-pay-btn" type="submit" <?= $stripeConfigured ? '' : 'disabled' ?>>
                        Pagar de forma segura
                    </button>
                </form>

                <aside class="checkout-summary" aria-label="Resumen del pedido">
                    <h2>Resumen del pedido</h2>

                    <div class="checkout-summary__items">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="checkout-summary__item">
                                <div>
                                    <strong><?= e((string) $item['nombre']) ?></strong>
                                    <span><?= (int) $item['quantity'] ?> × <?= number_format((float) $item['precio'], 2, ',', '.') ?> €</span>
                                </div>
                                <span><?= number_format((float) $item['subtotal'], 2, ',', '.') ?> €</span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="checkout-summary__row">
                        <span>Productos</span>
                        <strong><?= $cartQuantity ?></strong>
                    </div>

                    <div class="checkout-summary__row">
                        <span>Subtotal</span>
                        <strong><?= number_format($cartSubtotal, 2, ',', '.') ?> €</strong>
                    </div>

                    <div class="checkout-summary__row">
                        <span>Entrega</span>
                        <strong id="checkout-delivery-label">Envío a domicilio</strong>
                    </div>

                    <div class="checkout-summary__row">
                        <span>Envío</span>
                        <strong id="checkout-shipping-cost" data-home-cost="<?= e(number_format($homeShippingCost, 2, '.', '')) ?>" data-pickup-cost="<?= e(number_format($pickupShippingCost, 2, '.', '')) ?>"><?= $shippingCost === 0.0 ? 'Gratis' : number_format($shippingCost, 2, ',', '.') . ' €' ?></strong>
                    </div>

                    <div class="checkout-summary__total">
                        <span>Total</span>
                        <strong id="checkout-total" data-subtotal="<?= e(number_format($cartSubtotal, 2, '.', '')) ?>"><?= number_format($cartTotal, 2, ',', '.') ?> €</strong>
                    </div>

                    <p class="checkout-security-note">
                        Boticardo no guarda números de tarjeta ni datos bancarios. El pedido solo se marca como pagado cuando Stripe confirma el pago mediante webhook seguro.
                    </p>
                </aside>
            </div>
        </div>
    </section>
</main>

<script>
(function () {
    const deliveryInputs = document.querySelectorAll('input[name="metodo_entrega"]');
    const addressFields = document.getElementById('checkout-address-fields');
    const deliveryLabel = document.getElementById('checkout-delivery-label');
    const shippingCost = document.getElementById('checkout-shipping-cost');
    const total = document.getElementById('checkout-total');
    const note = document.getElementById('checkout-delivery-note');
    const requiredAddressInputs = addressFields ? addressFields.querySelectorAll('input') : [];

    function formatMoney(value) {
        return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value);
    }

    function updateDelivery() {
        const selected = document.querySelector('input[name="metodo_entrega"]:checked');
        const method = selected ? selected.value : 'domicilio';
        const isPickup = method === 'recogida';
        const subtotal = total ? Number(total.dataset.subtotal || '0') : 0;
        const cost = shippingCost ? Number(isPickup ? shippingCost.dataset.pickupCost : shippingCost.dataset.homeCost) : 0;

        if (addressFields) {
            addressFields.classList.toggle('checkout-address-fields--hidden', isPickup);
        }

        requiredAddressInputs.forEach(function (input) {
            input.required = !isPickup;
            input.disabled = isPickup;
        });

        if (deliveryLabel) {
            deliveryLabel.textContent = isPickup ? 'Recoger en farmacia' : 'Envío a domicilio';
        }

        if (shippingCost) {
            shippingCost.textContent = cost === 0 ? 'Gratis' : formatMoney(cost);
        }

        if (total) {
            total.textContent = formatMoney(subtotal + cost);
        }

        if (note) {
            note.textContent = isPickup
                ? 'No se añadirán gastos de envío. Te avisaremos cuando el pedido esté listo para recoger en farmacia.'
                : 'Recibirás el pedido en la dirección indicada. El envío es gratis si se alcanza el mínimo establecido.';
        }
    }

    deliveryInputs.forEach(function (input) {
        input.addEventListener('change', updateDelivery);
    });

    updateDelivery();
}());
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
