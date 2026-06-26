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
                    <li><a href="catalogo.php?categoria=1" class="footer__link">Medicamentos</a></li>
                    <li><a href="catalogo.php?categoria=2" class="footer__link">Dermocosmética</a></li>
                    <li><a href="catalogo.php?categoria=3" class="footer__link">Vitaminas</a></li>
                    <li><a href="catalogo.php?categoria=4" class="footer__link">Bebé y Mamá</a></li>
                    <li><a href="ofertas.php" class="footer__link">Ofertas</a></li>
                </ul>
            </div>
            <div>
                <p class="footer__col-title">Ayuda</p>
                <ul class="footer__links" role="list">
                    <li><a href="consejo.php" class="footer__link">Consejo farmacéutico</a></li>
                    <li><a href="envios.php" class="footer__link">Envíos y entregas</a></li>
                    <li><a href="devoluciones.php" class="footer__link">Devoluciones</a></li>
                    <li><a href="faq.php" class="footer__link">Preguntas frecuentes</a></li>
                    <li><a href="contacto.php" class="footer__link">Contacto</a></li>
                </ul>
            </div>
            <div>
                <p class="footer__col-title">Mi cuenta</p>
                <ul class="footer__links" role="list">
                    <li><a href="login.php" class="footer__link">Iniciar sesión</a></li>
                    <li><a href="registro.php" class="footer__link">Crear cuenta</a></li>
                    <li><a href="pedidos.php" class="footer__link">Mis pedidos</a></li>
                    <li><a href="receta.php" class="footer__link">Receta electrónica</a></li>
                    <li><a href="favoritos.php" class="footer__link">Mis favoritos</a></li>
                </ul>
            </div>
        </div>
        <div class="footer__bottom">
            <p class="footer__copy">© <?= date('Y') ?> Boticardo. Todos los derechos reservados.</p>
            <nav class="footer__legal-links" aria-label="Avisos legales">
                <a href="privacidad.php" class="footer__legal-link">Política de privacidad</a>
                <a href="cookies.php" class="footer__legal-link">Cookies</a>
                <a href="aviso-legal.php" class="footer__legal-link">Aviso legal</a>
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

        if (!cartLink || !cartBadge) return;

        function readStoredCount() {
            try {
                const storedValue = localStorage.getItem(storageKey);
                const parsedValue = Number.parseInt(storedValue ?? '0', 10);
                return Number.isFinite(parsedValue) && parsedValue > 0 ? parsedValue : 0;
            } catch (error) { return 0; }
        }

        function saveCount(count) {
            try { localStorage.setItem(storageKey, String(count)); } catch (error) {}
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
            cartLink.setAttribute('aria-label', `Carrito de compra (${safeCount} ${safeCount === 1 ? 'producto' : 'productos'})`);
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