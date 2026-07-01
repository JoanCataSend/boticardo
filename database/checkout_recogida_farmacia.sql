-- Añade el método de entrega a los pedidos para distinguir envío a domicilio y recogida en farmacia.
ALTER TABLE pedidos
    ADD COLUMN IF NOT EXISTS metodo_entrega ENUM('domicilio','recogida') NOT NULL DEFAULT 'domicilio' AFTER estado;
