-- Tablas necesarias para pedidos y pagos con Stripe Checkout.
-- Puedes ejecutar este SQL manualmente o dejar que includes/orders.php cree las tablas automáticamente.

CREATE TABLE IF NOT EXISTS pedidos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    public_id CHAR(32) NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    estado ENUM('pendiente','pagado','preparando','enviado','completado','cancelado','fallido') NOT NULL DEFAULT 'pendiente',
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    envio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    moneda CHAR(3) NOT NULL DEFAULT 'EUR',
    nombre_envio VARCHAR(160) NOT NULL,
    email_envio VARCHAR(190) NOT NULL,
    telefono_envio VARCHAR(30) NOT NULL,
    direccion_envio VARCHAR(255) NOT NULL,
    codigo_postal VARCHAR(12) NOT NULL,
    localidad VARCHAR(120) NOT NULL,
    provincia VARCHAR(120) NOT NULL,
    pais CHAR(2) NOT NULL DEFAULT 'ES',
    stripe_checkout_session_id VARCHAR(255) NULL,
    stripe_payment_intent VARCHAR(255) NULL,
    stripe_payment_method_types VARCHAR(255) NULL,
    stripe_event_id VARCHAR(255) NULL,
    paid_at DATETIME NULL,
    admin_notified_at DATETIME NULL,
    admin_notes TEXT NULL,
    tracking_code VARCHAR(120) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pedidos_public_id (public_id),
    UNIQUE KEY uq_pedidos_stripe_session (stripe_checkout_session_id),
    KEY idx_pedidos_usuario (usuario_id),
    KEY idx_pedidos_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pedido_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT UNSIGNED NOT NULL,
    producto_id INT UNSIGNED NOT NULL,
    nombre_producto VARCHAR(190) NOT NULL,
    marca_producto VARCHAR(190) NULL,
    precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    cantidad INT UNSIGNED NOT NULL DEFAULT 1,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pedido_items_pedido (pedido_id),
    CONSTRAINT fk_pedido_items_pedido
        FOREIGN KEY (pedido_id) REFERENCES pedidos(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stripe_webhook_events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id VARCHAR(255) NOT NULL,
    event_type VARCHAR(120) NOT NULL,
    processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_stripe_event_id (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
