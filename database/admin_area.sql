-- Cambios para panel de administración de Boticardo.
-- Ejecuta esto si prefieres aplicar la migración manualmente.

ALTER TABLE usuarios
    ADD COLUMN rol ENUM('cliente','admin') NOT NULL DEFAULT 'cliente' AFTER email;

-- Si la columna ya existe, ignora el error anterior y ejecuta solo el UPDATE del admin.
-- Cambia el email por tu cuenta real:
UPDATE usuarios SET rol = 'admin' WHERE email = 'admin@gmail.com';

ALTER TABLE pedidos
    MODIFY estado ENUM('pendiente','pagado','preparando','enviado','completado','cancelado','fallido') NOT NULL DEFAULT 'pendiente';

ALTER TABLE pedidos
    ADD COLUMN admin_notified_at DATETIME NULL AFTER paid_at,
    ADD COLUMN admin_notes TEXT NULL AFTER admin_notified_at,
    ADD COLUMN tracking_code VARCHAR(120) NULL AFTER admin_notes;
