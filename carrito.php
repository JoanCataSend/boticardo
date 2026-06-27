<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Carrito de compra | Boticardo';
$pageDescription = 'Revisa los productos añadidos al carrito de Boticardo antes de finalizar tu compra.';
$canonicalUrl = $siteUrl . '/carrito.php';

$checkoutNeedsLogin = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'update_quantities') {
        $quantities = $_POST['quantity'] ?? [];

        if (is_array($quantities)) {
            foreach ($quantities as $productId => $quantity) {
                cartUpdateProduct((int) $productId, (int) $quantity);
            }
        }

        header('Location: carrito.php?actualizado=1');
        exit;
    }

    if ($action === 'remove') {
        cartRemoveProduct((int) ($_POST['product_id'] ?? 0));
        header('Location: carrito.php?eliminado=1');
        exit;
    }

    if ($action === 'clear') {
        cartClear();
        header('Location: carrito.php?vaciado=1');
        exit;
    }

    if ($action === 'checkout') {
        if (!isUserLoggedIn()) {
            $checkoutNeedsLogin = true;
        } else {
            header('Location: checkout.php');
            exit;
        }
    }
}

require_once __DIR__ . '/includes/schema.php';

$cartSummary = cartSummary($conn);
$cartItems = $cartSummary['items'];
$cartQuantity = (int) $cartSummary['quantity'];
$cartSubtotal = (float) $cartSummary['subtotal'];
$conn->close();

require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content">
    <section class="cart-page">
        <div class="container">
            <div class="section-header">
                <span class="section-header__eyebrow">Tu compra</span>
                <h1 class="section-header__title">Carrito</h1>
                <p class="section-header__subtitle">
                    Revisa los productos antes de continuar con el pago.
                </p>
            </div>

            <?php if ($checkoutNeedsLogin): ?>
                <div class="cart-notice cart-notice--warning" role="alert">
                    Para finalizar la compra tienes que iniciar sesión. Tu carrito se mantiene guardado mientras tanto.
                    <a href="login.php?redirect=carrito.php" class="cart-notice__link">Iniciar sesión</a>
                </div>
            <?php elseif (isset($_GET['actualizado'])): ?>
                <div class="cart-notice" role="status">Carrito actualizado correctamente.</div>
            <?php elseif (isset($_GET['eliminado'])): ?>
                <div class="cart-notice" role="status">Producto eliminado del carrito.</div>
            <?php elseif (isset($_GET['vaciado'])): ?>
                <div class="cart-notice" role="status">El carrito se ha vaciado.</div>
            <?php endif; ?>

            <?php if ($cartItems === []): ?>
                <div class="cart-empty">
                    <h2>Tu carrito está vacío</h2>
                    <p>Añade productos desde el catálogo o desde la página principal.</p>
                    <a href="catalogo.php" class="btn btn--primary">Ver productos</a>
                </div>
            <?php else: ?>
                <div class="cart-layout">
                    <form class="cart-items" action="carrito.php" method="post">
                        <input type="hidden" name="action" value="update_quantities">

                        <div class="cart-items__header">
                            <h2>Productos añadidos</h2>
                            <span><?= $cartQuantity ?> <?= $cartQuantity === 1 ? 'producto' : 'productos' ?></span>
                        </div>

                        <?php foreach ($cartItems as $item): ?>
                            <?php
                            $productId = (int) $item['id'];
                            $productName = (string) $item['nombre'];
                            $productBrand = (string) ($item['marca'] ?: 'Boticardo');
                            $productImage = basename((string) ($item['imagen'] ?? 'placeholder.jpg'));
                            $productPrice = (float) $item['precio'];
                            $productQuantity = (int) $item['quantity'];
                            $productSubtotal = (float) $item['subtotal'];
                            ?>
                            <article class="cart-item">
                                <div class="cart-item__image-wrap">
                                    <img
                                        src="img/productos/<?= e($productImage) ?>"
                                        alt="<?= e($productName) ?>"
                                        class="cart-item__image"
                                        loading="lazy"
                                        onerror="this.onerror=null;this.src='img/productos/placeholder.jpg'"
                                    />
                                </div>

                                <div class="cart-item__info">
                                    <span class="cart-item__brand"><?= e($productBrand) ?></span>
                                    <h3 class="cart-item__name"><?= e($productName) ?></h3>
                                    <p class="cart-item__price"><?= number_format($productPrice, 2, ',', '.') ?> € / unidad</p>
                                </div>

                                <div class="cart-item__quantity">
                                    <label for="quantity-<?= $productId ?>">Cantidad</label>
                                    <input
                                        id="quantity-<?= $productId ?>"
                                        name="quantity[<?= $productId ?>]"
                                        type="number"
                                        min="0"
                                        max="99"
                                        value="<?= $productQuantity ?>"
                                        inputmode="numeric"
                                    />
                                </div>

                                <div class="cart-item__subtotal">
                                    <span>Subtotal</span>
                                    <strong><?= number_format($productSubtotal, 2, ',', '.') ?> €</strong>
                                </div>

                                <button
                                    class="cart-item__remove"
                                    type="submit"
                                    name="action"
                                    value="remove"
                                    form="remove-product-<?= $productId ?>"
                                >
                                    Eliminar
                                </button>
                            </article>
                        <?php endforeach; ?>

                        <div class="cart-items__actions">
                            <button class="btn btn--outline" type="submit">Actualizar cantidades</button>
                        </div>
                    </form>

                    <?php foreach ($cartItems as $item): ?>
                        <form id="remove-product-<?= (int) $item['id'] ?>" action="carrito.php" method="post" hidden>
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="product_id" value="<?= (int) $item['id'] ?>">
                        </form>
                    <?php endforeach; ?>

                    <aside class="cart-summary" aria-label="Resumen del carrito">
                        <h2>Resumen</h2>

                        <div class="cart-summary__row">
                            <span>Productos</span>
                            <strong><?= $cartQuantity ?></strong>
                        </div>

                        <div class="cart-summary__row">
                            <span>Subtotal</span>
                            <strong><?= number_format($cartSubtotal, 2, ',', '.') ?> €</strong>
                        </div>

                        <p class="cart-summary__note">
                            Los gastos de envío y posibles descuentos se calcularán en el siguiente paso.
                        </p>

                        <form action="carrito.php" method="post" class="cart-summary__checkout-form">
                            <input type="hidden" name="action" value="checkout">
                            <button class="btn btn--primary cart-summary__checkout" type="submit">
                                Finalizar compra
                            </button>
                        </form>

                        <?php if (!isUserLoggedIn()): ?>
                            <p class="cart-summary__login-note">
                                Puedes guardar productos sin iniciar sesión, pero para pagar tendrás que acceder a tu cuenta.
                            </p>
                        <?php endif; ?>

                        <form action="carrito.php" method="post">
                            <input type="hidden" name="action" value="clear">
                            <button class="cart-summary__clear" type="submit">
                                Vaciar carrito
                            </button>
                        </form>
                    </aside>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
