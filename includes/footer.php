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

        function updateCartBadge(count) {
            if (!cartLink || !cartBadge) return;

            const safeCount = Math.max(0, Number.parseInt(String(count), 10) || 0);
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
            animateCartBadge();
        }

        function animateCartBadge() {
            if (!cartBadge || !cartLink) return;

            const header = document.querySelector('.header');

            cartBadge.classList.remove('header__cart-badge--bump');
            cartLink.classList.remove('header__action-btn--cart-success');
            if (header) header.classList.remove('header--cart-highlight');

            void cartBadge.offsetWidth;

            cartBadge.classList.add('header__cart-badge--bump');
            cartLink.classList.add('header__action-btn--cart-success');
            if (header) header.classList.add('header--cart-highlight');

            window.setTimeout(function () {
                cartBadge.classList.remove('header__cart-badge--bump');
                cartLink.classList.remove('header__action-btn--cart-success');
                if (header) header.classList.remove('header--cart-highlight');
            }, 850);
        }

        function getCartToast() {
            let toast = document.getElementById('cart-toast');

            if (toast) return toast;

            toast = document.createElement('div');
            toast.id = 'cart-toast';
            toast.className = 'cart-toast';
            toast.setAttribute('role', 'status');
            toast.setAttribute('aria-live', 'polite');
            toast.innerHTML = `
                <span class="cart-toast__icon" aria-hidden="true">✓</span>
                <span class="cart-toast__text"></span>
                <a class="cart-toast__link" href="carrito.php">Ver carrito</a>
            `;
            document.body.appendChild(toast);
            return toast;
        }

        let cartToastTimer = null;

        function showCartToast(message) {
            const toast = getCartToast();
            const toastText = toast.querySelector('.cart-toast__text');

            if (toastText) {
                toastText.textContent = message;
            }

            toast.classList.add('cart-toast--visible');
            window.clearTimeout(cartToastTimer);
            cartToastTimer = window.setTimeout(function () {
                toast.classList.remove('cart-toast--visible');
            }, 3200);
        }

        function showButtonAddedState(button) {
            if (!button) return;

            const originalHtml = button.dataset.originalHtml || button.innerHTML;
            button.dataset.originalHtml = originalHtml;
            button.classList.add('product-card__add-btn--added');
            button.innerHTML = '✓ Añadido';

            window.setTimeout(function () {
                button.classList.remove('product-card__add-btn--added');
                button.innerHTML = button.dataset.originalHtml || originalHtml;
            }, 1400);
        }

        async function addProductToCart(productId) {
            const response = await fetch('api/carrito_accion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'Accept': 'application/json'
                },
                body: new URLSearchParams({
                    action: 'add',
                    product_id: String(productId),
                    quantity: '1'
                })
            });

            if (!response.ok) {
                throw new Error('No se pudo añadir el producto al carrito.');
            }

            return response.json();
        }

        addToCartButtons.forEach(function (button) {
            button.addEventListener('click', async function () {
                const productId = Number.parseInt(button.dataset.productId || '0', 10);
                const productName = button.dataset.productName || 'Producto';

                if (!productId) {
                    if (cartStatus) cartStatus.textContent = 'No se pudo identificar el producto.';
                    return;
                }

                button.disabled = true;
                button.classList.add('product-card__add-btn--loading');

                try {
                    const data = await addProductToCart(productId);

                    if (!data.ok) {
                        throw new Error(data.message || 'No se pudo añadir el producto al carrito.');
                    }

                    updateCartBadge(data.cart_count);

                    if (cartStatus) {
                        cartStatus.textContent = `${productName} añadido al carrito. Total: ${data.cart_count}.`;
                    }

                    showButtonAddedState(button);
                    showCartToast(`${productName} se ha añadido al carrito.`);
                } catch (error) {
                    if (cartStatus) {
                        cartStatus.textContent = error.message || 'No se pudo añadir el producto al carrito.';
                    }
                    alert(error.message || 'No se pudo añadir el producto al carrito.');
                } finally {
                    button.disabled = false;
                    button.classList.remove('product-card__add-btn--loading');
                }
            });
        });
    });
</script>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchForm = document.querySelector('.header__search');
        const searchInput = document.getElementById('search-input');
        const suggestionsBox = document.getElementById('search-suggestions');

        if (!searchForm || !searchInput || !suggestionsBox) return;

        let debounceTimer = null;
        let activeController = null;

        function escapeHtml(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function hideSuggestions() {
            suggestionsBox.hidden = true;
            suggestionsBox.innerHTML = '';
        }

        function renderSuggestions(items, query) {
            if (!items.length) {
                suggestionsBox.innerHTML = `<div class="search-suggestion--empty">No hay sugerencias para “${escapeHtml(query)}”. Pulsa Buscar para ver resultados aproximados.</div>`;
                suggestionsBox.hidden = false;
                return;
            }

            suggestionsBox.innerHTML = items.map(function (item) {
                return `
                    <a class="search-suggestion" href="${escapeHtml(item.url)}" role="option">
                        <img class="search-suggestion__image" src="${escapeHtml(item.imagen)}" alt="" loading="lazy" onerror="this.onerror=null;this.src='img/productos/placeholder.jpg'">
                        <span class="search-suggestion__content">
                            <span class="search-suggestion__name">${escapeHtml(item.nombre)}</span>
                            <span class="search-suggestion__brand">${escapeHtml(item.marca)}</span>
                        </span>
                        <span class="search-suggestion__price">${escapeHtml(item.precio)}</span>
                    </a>
                `;
            }).join('');
            suggestionsBox.hidden = false;
        }

        async function loadSuggestions(query) {
            if (activeController) {
                activeController.abort();
            }

            activeController = new AbortController();

            const response = await fetch(`api/buscar_sugerencias.php?q=${encodeURIComponent(query)}`, {
                headers: { 'Accept': 'application/json' },
                signal: activeController.signal
            });

            if (!response.ok) {
                throw new Error('No se pudieron cargar las sugerencias.');
            }

            return response.json();
        }

        searchInput.addEventListener('input', function () {
            const query = searchInput.value.trim();

            window.clearTimeout(debounceTimer);

            if (query.length < 2) {
                hideSuggestions();
                return;
            }

            debounceTimer = window.setTimeout(async function () {
                try {
                    const data = await loadSuggestions(query);
                    renderSuggestions(Array.isArray(data.items) ? data.items : [], query);
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        hideSuggestions();
                    }
                }
            }, 220);
        });

        searchInput.addEventListener('focus', function () {
            if (suggestionsBox.innerHTML.trim() !== '') {
                suggestionsBox.hidden = false;
            }
        });

        document.addEventListener('click', function (event) {
            if (!searchForm.contains(event.target)) {
                hideSuggestions();
            }
        });

        searchForm.addEventListener('submit', function () {
            hideSuggestions();
        });
    });
</script>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        const header = document.querySelector('.header');

        if (!header) return;

        function updateMobileHeaderOffset() {
            if (window.matchMedia('(max-width: 768px)').matches) {
                document.body.style.setProperty('--mobile-header-height', `${Math.ceil(header.getBoundingClientRect().height)}px`);
                document.body.classList.add('has-mobile-fixed-header');
            } else {
                document.body.style.removeProperty('--mobile-header-height');
                document.body.classList.remove('has-mobile-fixed-header');
            }
        }

        updateMobileHeaderOffset();
        window.addEventListener('resize', updateMobileHeaderOffset, { passive: true });
        window.addEventListener('orientationchange', function () {
            window.setTimeout(updateMobileHeaderOffset, 250);
        });
    });
</script>

</body>
</html>
