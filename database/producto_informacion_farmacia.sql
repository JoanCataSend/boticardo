-- Añade información farmacéutica ampliada para la ficha de producto.
-- Ejecutar una sola vez desde phpMyAdmin sobre la base de datos boticardo_bd.
-- Importante: codigo_nacional se deja preparado; en una farmacia real debe rellenarse con el Código Nacional oficial.

ALTER TABLE productos
  ADD COLUMN IF NOT EXISTS codigo_nacional VARCHAR(20) DEFAULT NULL AFTER codigo_sku,
  ADD COLUMN IF NOT EXISTS principio_activo VARCHAR(190) DEFAULT NULL AFTER descripcion,
  ADD COLUMN IF NOT EXISTS modo_empleo TEXT DEFAULT NULL AFTER principio_activo,
  ADD COLUMN IF NOT EXISTS advertencias TEXT DEFAULT NULL AFTER modo_empleo,
  ADD COLUMN IF NOT EXISTS contraindicaciones TEXT DEFAULT NULL AFTER advertencias,
  ADD COLUMN IF NOT EXISTS conservacion TEXT DEFAULT NULL AFTER contraindicaciones;

-- Valor temporal para que la ficha no aparezca vacía.
-- Sustituye estos valores por los Códigos Nacionales reales si vas a usar productos reales.
UPDATE productos
SET codigo_nacional = codigo_sku
WHERE codigo_nacional IS NULL OR codigo_nacional = '';

UPDATE productos
SET
  principio_activo = 'Ácido acetilsalicílico',
  modo_empleo = 'Usar siguiendo las indicaciones del prospecto o las recomendaciones del farmacéutico. No superar la pauta indicada en el envase.',
  advertencias = 'No utilizar en caso de alergia al principio activo o a otros antiinflamatorios. Consultar al farmacéutico si se toman anticoagulantes, si existe úlcera gástrica o si hay dudas sobre su uso.',
  contraindicaciones = 'Contraindicado en personas con alergia conocida al ácido acetilsalicílico o con antecedentes de reacciones graves a antiinflamatorios. Consultar siempre el prospecto.',
  conservacion = 'Conservar en lugar fresco y seco, protegido de la luz directa y fuera del alcance de los niños.'
WHERE id = 1;

UPDATE productos
SET
  principio_activo = 'Ibuprofeno',
  modo_empleo = 'Usar únicamente según la pauta indicada por un profesional sanitario o en el prospecto del medicamento.',
  advertencias = 'Puede producir molestias digestivas. Consultar antes de usar si existe enfermedad gástrica, renal, cardiovascular, embarazo, lactancia o tratamiento con otros medicamentos.',
  contraindicaciones = 'No utilizar en caso de alergia al ibuprofeno u otros antiinflamatorios, úlcera activa o antecedentes de reacciones graves. Producto sujeto a indicación profesional si requiere receta.',
  conservacion = 'Conservar en su envase original, en lugar fresco y seco, lejos de fuentes de calor.'
WHERE id = 2;

UPDATE productos
SET
  principio_activo = 'Paracetamol',
  modo_empleo = 'Usar siguiendo las indicaciones del prospecto o la recomendación de un profesional sanitario. Evitar duplicar medicamentos que contengan paracetamol.',
  advertencias = 'No superar la dosis indicada en el prospecto. Consultar si existe enfermedad hepática, consumo habitual de alcohol o tratamiento con otros medicamentos.',
  contraindicaciones = 'No utilizar en caso de alergia al paracetamol o enfermedad hepática grave salvo indicación médica.',
  conservacion = 'Conservar en lugar seco, a temperatura ambiente y fuera del alcance de los niños.'
WHERE id = 3;

UPDATE productos
SET
  principio_activo = 'Filtros solares SPF 50',
  modo_empleo = 'Aplicar generosamente sobre la piel seca antes de la exposición solar y reaplicar con frecuencia, especialmente después del baño, sudoración o secado con toalla.',
  advertencias = 'Evitar el contacto directo con los ojos. La exposición solar excesiva es perjudicial incluso usando protección solar.',
  contraindicaciones = 'No usar sobre piel irritada o lesionada si produce molestias. Suspender el uso en caso de reacción adversa.',
  conservacion = 'Mantener bien cerrado, protegido del calor y de la luz directa.'
WHERE id = 4;

UPDATE productos
SET
  principio_activo = 'Vitamina C y activos antioxidantes',
  modo_empleo = 'Aplicar según las indicaciones del envase, normalmente sobre la piel limpia antes de la crema habitual.',
  advertencias = 'Evitar el contacto con ojos y mucosas. Puede aumentar la sensibilidad de la piel; usar protección solar durante el día.',
  contraindicaciones = 'No usar si existe sensibilidad conocida a alguno de sus componentes. Suspender el uso si aparece irritación persistente.',
  conservacion = 'Conservar cerrado, protegido de la luz directa y del calor.'
WHERE id = 5;

UPDATE productos
SET
  principio_activo = 'Vitaminas y minerales',
  modo_empleo = 'Tomar siguiendo las indicaciones del envase. No superar la dosis diaria recomendada.',
  advertencias = 'Los complementos alimenticios no sustituyen una dieta variada y equilibrada. Consultar si se está embarazada, en lactancia o bajo tratamiento médico.',
  contraindicaciones = 'No utilizar en caso de alergia a alguno de sus componentes. Mantener fuera del alcance de los niños.',
  conservacion = 'Conservar en lugar fresco y seco, bien cerrado.'
WHERE id = 6;

UPDATE productos
SET
  principio_activo = 'Vitamina C',
  modo_empleo = 'Disolver o tomar según las indicaciones del envase. No superar la cantidad diaria recomendada.',
  advertencias = 'Consultar con un profesional sanitario en caso de embarazo, lactancia, enfermedad renal o tratamiento médico.',
  contraindicaciones = 'No utilizar en caso de alergia a alguno de sus componentes.',
  conservacion = 'Conservar en lugar seco, protegido de la humedad y cerrar bien el envase.'
WHERE id = 7;

UPDATE productos
SET
  principio_activo = 'Fórmula infantil',
  modo_empleo = 'Preparar siguiendo estrictamente las instrucciones del fabricante y las recomendaciones del pediatra o profesional sanitario.',
  advertencias = 'Una preparación incorrecta puede afectar a la salud del bebé. Usar agua segura y respetar las cantidades indicadas por el fabricante.',
  contraindicaciones = 'No utilizar si el envase está dañado o si el producto no es adecuado para la edad del bebé. Consultar con pediatría en caso de duda.',
  conservacion = 'Conservar el bote bien cerrado, en lugar fresco y seco. Una vez abierto, seguir el plazo indicado por el fabricante.'
WHERE id = 8;

UPDATE productos
SET
  principio_activo = 'No aplica',
  modo_empleo = 'Usar bajo supervisión de un adulto y limpiar según las instrucciones del fabricante antes de cada uso.',
  advertencias = 'Revisar el estado del chupete antes de cada uso y desecharlo si presenta signos de deterioro.',
  contraindicaciones = 'No utilizar si está roto, deformado o deteriorado.',
  conservacion = 'Guardar limpio y seco cuando no se utilice.'
WHERE id = 9;

UPDATE productos
SET
  principio_activo = 'Flúor y activos para sensibilidad dental',
  modo_empleo = 'Cepillar los dientes según las recomendaciones de higiene bucodental y las indicaciones del envase.',
  advertencias = 'No ingerir. Mantener fuera del alcance de los niños pequeños. Consultar al dentista si la sensibilidad persiste.',
  contraindicaciones = 'No utilizar en caso de alergia a alguno de sus componentes.',
  conservacion = 'Cerrar el tubo después de cada uso y conservar a temperatura ambiente.'
WHERE id = 10;

UPDATE productos
SET
  principio_activo = 'No aplica',
  modo_empleo = 'Aplicar sobre el cabello húmedo, masajear suavemente y aclarar con abundante agua.',
  advertencias = 'Evitar el contacto con los ojos. En caso de contacto, aclarar con agua abundante.',
  contraindicaciones = 'No usar si existe sensibilidad conocida a alguno de sus componentes o si aparece irritación persistente.',
  conservacion = 'Conservar cerrado, alejado del calor y fuera del alcance de los niños.'
WHERE id = 11;
