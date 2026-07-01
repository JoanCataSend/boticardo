-- =========================================================
-- Boticardo - Panel admin ampliado
-- Productos, categorías, laboratorios, cupones, banners y páginas legales.
-- Ejecutar una vez en phpMyAdmin sobre la BBDD boticardo_bd.
-- El código también intenta crear estas tablas/columnas automáticamente.
-- =========================================================

ALTER TABLE productos
  ADD COLUMN IF NOT EXISTS codigo_nacional VARCHAR(20) DEFAULT NULL AFTER codigo_sku,
  ADD COLUMN IF NOT EXISTS principio_activo VARCHAR(190) DEFAULT NULL AFTER descripcion,
  ADD COLUMN IF NOT EXISTS modo_empleo TEXT DEFAULT NULL AFTER principio_activo,
  ADD COLUMN IF NOT EXISTS advertencias TEXT DEFAULT NULL AFTER modo_empleo,
  ADD COLUMN IF NOT EXISTS contraindicaciones TEXT DEFAULT NULL AFTER advertencias,
  ADD COLUMN IF NOT EXISTS conservacion TEXT DEFAULT NULL AFTER contraindicaciones,
  ADD COLUMN IF NOT EXISTS en_oferta TINYINT(1) NOT NULL DEFAULT 0 AFTER precio,
  ADD COLUMN IF NOT EXISTS precio_original DECIMAL(10,2) DEFAULT NULL AFTER en_oferta,
  ADD COLUMN IF NOT EXISTS descuento_porcentaje DECIMAL(5,2) DEFAULT NULL AFTER precio_original,
  ADD COLUMN IF NOT EXISTS oferta_inicio DATETIME DEFAULT NULL AFTER descuento_porcentaje,
  ADD COLUMN IF NOT EXISTS oferta_fin DATETIME DEFAULT NULL AFTER oferta_inicio,
  ADD COLUMN IF NOT EXISTS etiqueta_oferta VARCHAR(80) DEFAULT NULL AFTER oferta_fin,
  ADD COLUMN IF NOT EXISTS destacar_oferta TINYINT(1) NOT NULL DEFAULT 0 AFTER etiqueta_oferta;

CREATE TABLE IF NOT EXISTS cupones (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL,
  descripcion VARCHAR(255) NULL,
  tipo ENUM('porcentaje','importe') NOT NULL DEFAULT 'porcentaje',
  valor DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  importe_minimo DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  usos_maximos INT UNSIGNED NULL,
  usos_actuales INT UNSIGNED NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  fecha_inicio DATETIME NULL,
  fecha_fin DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cupones_codigo (codigo),
  KEY idx_cupones_activo (activo, fecha_inicio, fecha_fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS banners_portada (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(160) NOT NULL,
  subtitulo VARCHAR(255) NULL,
  etiqueta VARCHAR(80) NULL,
  texto_boton VARCHAR(80) NULL,
  enlace_boton VARCHAR(255) NULL,
  imagen VARCHAR(255) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  orden INT NOT NULL DEFAULT 0,
  fecha_inicio DATETIME NULL,
  fecha_fin DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_banners_activo (activo, orden, fecha_inicio, fecha_fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS paginas_legales (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(80) NOT NULL,
  titulo VARCHAR(160) NOT NULL,
  descripcion VARCHAR(255) NULL,
  contenido_html MEDIUMTEXT NULL,
  publicado TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_paginas_legales_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO paginas_legales (slug, titulo, descripcion, publicado) VALUES
('aviso-legal', 'Aviso legal', 'Información legal de Boticardo.', 0),
('privacidad', 'Política de privacidad', 'Información sobre tratamiento de datos personales.', 0),
('cookies', 'Política de cookies', 'Información sobre cookies y tecnologías similares.', 0),
('condiciones-compra', 'Condiciones de compra', 'Condiciones aplicables a pedidos y pagos.', 0),
('envios-devoluciones', 'Envíos y devoluciones', 'Información sobre envíos, cambios y devoluciones.', 0)
ON DUPLICATE KEY UPDATE titulo = VALUES(titulo);

-- Ejemplo opcional de cupón. Puedes borrarlo o cambiarlo desde el panel.
INSERT INTO cupones (codigo, descripcion, tipo, valor, importe_minimo, usos_maximos, activo)
VALUES ('BIENVENIDA10', 'Cupón de bienvenida del 10%', 'porcentaje', 10.00, 25.00, 200, 0)
ON DUPLICATE KEY UPDATE codigo = codigo;
