-- Boticardo - soporte para reserva de stock en pedidos pendientes
-- Ejecuta este SQL solo si quieres preparar la BBDD manualmente.
-- El código también intenta crear esta columna automáticamente desde includes/orders.php.

ALTER TABLE pedidos
  ADD COLUMN IF NOT EXISTS stock_reservado TINYINT(1) NOT NULL DEFAULT 0 AFTER tracking_code;
