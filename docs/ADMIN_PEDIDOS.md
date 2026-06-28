# Panel de administración y avisos de pedidos

## Acceso admin

El panel está en:

```text
/admin/index.php
```

Solo puede entrar un usuario con `rol = 'admin'` en la tabla `usuarios`.

Para convertir tu cuenta en admin, ejecuta en la base de datos:

```sql
UPDATE usuarios SET rol = 'admin' WHERE email = 'tu-correo@ejemplo.com';
```

## Secciones creadas

- `/admin/index.php`: resumen.
- `/admin/pedidos.php`: listado de pedidos.
- `/admin/pedido.php?id=...`: detalle del pedido y cambio manual de estado.
- `/admin/clientes.php`: clientes registrados.
- `/admin/cliente.php?id=...`: ficha de cliente.

## Estados de pedido

- `pendiente`: pedido creado, pago todavía no confirmado.
- `pagado`: pago confirmado, pendiente de preparar.
- `preparando`: la farmacia está preparando el paquete.
- `enviado`: pedido enviado.
- `completado`: pedido terminado.
- `cancelado`: pedido cancelado.
- `fallido`: pago fallido.

## Aviso por correo

Cuando Stripe confirma el pago mediante webhook, Boticardo intenta enviar un correo a `ADMIN_ORDER_EMAIL`.

Configura en el servidor:

```bash
ADMIN_ORDER_EMAIL="correo-de-la-farmacia@dominio.com"
MAIL_FROM_EMAIL="no-reply@boticardo.es"
MAIL_FROM_NAME="Boticardo"
```

Si no configuras `ADMIN_ORDER_EMAIL`, se usa el email definido en `includes/config.php`.

Importante: esta versión usa `mail()` de PHP. En hosting real puede funcionar directamente, pero para máxima fiabilidad conviene usar SMTP profesional más adelante.
