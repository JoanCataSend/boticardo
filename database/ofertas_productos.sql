-- =========================================================
-- Boticardo - Campos de ofertas para la tabla productos
-- Compatible con MariaDB 10.4+
--
-- IMPORTANTE:
-- - productos.precio sigue siendo el precio final que cobra el carrito.
-- - productos.precio_original sirve para mostrar el precio tachado.
-- - en_oferta = 1 hace que el producto aparezca en ofertas.php.
-- - Los productos con requiere_receta = 1 no se mostrarán en ofertas.php.
-- =========================================================

ALTER TABLE `productos`
  ADD COLUMN IF NOT EXISTS `en_oferta` TINYINT(1) NOT NULL DEFAULT 0 AFTER `precio`,
  ADD COLUMN IF NOT EXISTS `precio_original` DECIMAL(10,2) DEFAULT NULL AFTER `en_oferta`,
  ADD COLUMN IF NOT EXISTS `descuento_porcentaje` DECIMAL(5,2) DEFAULT NULL AFTER `precio_original`,
  ADD COLUMN IF NOT EXISTS `oferta_inicio` DATETIME DEFAULT NULL AFTER `descuento_porcentaje`,
  ADD COLUMN IF NOT EXISTS `oferta_fin` DATETIME DEFAULT NULL AFTER `oferta_inicio`,
  ADD COLUMN IF NOT EXISTS `etiqueta_oferta` VARCHAR(80) DEFAULT NULL AFTER `oferta_fin`,
  ADD COLUMN IF NOT EXISTS `destacar_oferta` TINYINT(1) NOT NULL DEFAULT 0 AFTER `etiqueta_oferta`;

CREATE INDEX IF NOT EXISTS `idx_productos_oferta` ON `productos` (`en_oferta`, `oferta_inicio`, `oferta_fin`);

-- Ejemplos de ofertas activas usando tus productos actuales.
-- Puedes cambiar precios, descuentos, etiquetas o fechas cuando quieras.

UPDATE `productos`
SET
  `en_oferta` = 1,
  `precio_original` = 5.50,
  `descuento_porcentaje` = 18.00,
  `oferta_inicio` = NOW(),
  `oferta_fin` = DATE_ADD(NOW(), INTERVAL 60 DAY),
  `etiqueta_oferta` = '-18%',
  `destacar_oferta` = 1
WHERE `codigo_sku` = 'MED-001';

UPDATE `productos`
SET
  `en_oferta` = 1,
  `precio_original` = 27.95,
  `descuento_porcentaje` = 19.50,
  `oferta_inicio` = NOW(),
  `oferta_fin` = DATE_ADD(NOW(), INTERVAL 60 DAY),
  `etiqueta_oferta` = 'Oferta solar',
  `destacar_oferta` = 1
WHERE `codigo_sku` = 'DER-001';

UPDATE `productos`
SET
  `en_oferta` = 1,
  `precio_original` = 45.00,
  `descuento_porcentaje` = 16.00,
  `oferta_inicio` = NOW(),
  `oferta_fin` = DATE_ADD(NOW(), INTERVAL 60 DAY),
  `etiqueta_oferta` = '-16%',
  `destacar_oferta` = 0
WHERE `codigo_sku` = 'DER-002';

UPDATE `productos`
SET
  `en_oferta` = 1,
  `precio_original` = 17.95,
  `descuento_porcentaje` = 17.00,
  `oferta_inicio` = NOW(),
  `oferta_fin` = DATE_ADD(NOW(), INTERVAL 60 DAY),
  `etiqueta_oferta` = '-17%',
  `destacar_oferta` = 0
WHERE `codigo_sku` = 'VIT-001';

UPDATE `productos`
SET
  `en_oferta` = 1,
  `precio_original` = 5.95,
  `descuento_porcentaje` = 19.00,
  `oferta_inicio` = NOW(),
  `oferta_fin` = DATE_ADD(NOW(), INTERVAL 60 DAY),
  `etiqueta_oferta` = '-19%',
  `destacar_oferta` = 0
WHERE `codigo_sku` = 'HIG-001';

UPDATE `productos`
SET
  `en_oferta` = 1,
  `precio_original` = 7.95,
  `descuento_porcentaje` = 18.00,
  `oferta_inicio` = NOW(),
  `oferta_fin` = DATE_ADD(NOW(), INTERVAL 60 DAY),
  `etiqueta_oferta` = '-18%',
  `destacar_oferta` = 0
WHERE `codigo_sku` = 'BEB-002';
