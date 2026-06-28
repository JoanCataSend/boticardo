# Pagos Boticardo: tarjeta + Bizum con Stripe Checkout

## Flujo seguro

1. El cliente añade productos al carrito.
2. En `checkout.php` introduce datos de envío y revisa el resumen.
3. `checkout/crear_sesion_stripe.php` recalcula el carrito desde la base de datos, crea un pedido `pendiente` y genera una sesión de Stripe Checkout.
4. El cliente paga en Stripe con tarjeta o Bizum.
5. Stripe llama a `webhooks/stripe.php`.
6. Solo si la firma del webhook es válida y el importe coincide, el pedido pasa a `pagado`.
7. `pedido-confirmado.php` solo muestra el estado; no marca nada como pagado.

## Variables de entorno necesarias

Ponlas en el servidor, no dentro del código subido a Git:

```bash
APP_BASE_URL="https://boticardo.es"
STRIPE_SECRET_KEY="sk_live_xxx"
STRIPE_WEBHOOK_SECRET="whsec_xxx"
STRIPE_CURRENCY="eur"
```

Para pruebas usa claves `sk_test_...` y el webhook secret de test.

## Webhook que debes crear en Stripe

Endpoint:

```text
https://boticardo.es/webhooks/stripe.php
```

Eventos mínimos:

```text
checkout.session.completed
checkout.session.async_payment_succeeded
```

## Activar Bizum

En Stripe Dashboard tienes que activar Bizum como método de pago para España. Si Bizum no está activo en tu cuenta, Stripe puede rechazar la sesión o no mostrar Bizum.

## Seguridad aplicada

- No se guardan tarjetas ni datos bancarios.
- El precio se recalcula en servidor desde la base de datos.
- El pedido empieza como `pendiente`.
- El pedido solo pasa a `pagado` desde el webhook verificado.
- El webhook verifica `Stripe-Signature` con HMAC SHA-256.
- Se comprueba que el importe recibido coincide con el total del pedido.
- Los eventos de Stripe son idempotentes con `stripe_webhook_events.event_id` único.
- El botón de pago exige usuario logueado y token CSRF.

## Prueba rápida

1. Configura claves test de Stripe.
2. Añade productos al carrito.
3. Inicia sesión.
4. Entra en `checkout.php`.
5. Rellena dirección.
6. Pulsa pagar.
7. Usa tarjetas de prueba de Stripe en modo test.
8. Comprueba que el pedido pasa a `pagado` solo tras webhook.
