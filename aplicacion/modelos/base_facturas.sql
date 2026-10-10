-- =====================================================================
-- Facturas PUE / PPD y complementos de pago (uno o varios por factura)
-- oc_facturas usa utf8mb4_general_ci e id INT UNSIGNED: la tabla nueva debe igualarlos (las consultas comparan
-- uuid_factura = uuid_cfdi; con el COLLATE por defecto de MySQL 8, utf8mb4_0900_ai_ci, darían "Illegal mix of collations").
-- Las columnas metodo_pago / forma_pago / moneda ya existen en tu tabla: omite el ALTER del paso 1.
-- =====================================================================

-- 1) Datos del CFDI de la factura que definen si lleva complemento
ALTER TABLE oc_facturas
  ADD COLUMN metodo_pago CHAR(3)    NULL COMMENT 'PUE o PPD (atributo MetodoPago del CFDI)' AFTER monto,
  ADD COLUMN forma_pago  VARCHAR(3) NULL COMMENT 'FormaPago del CFDI (99 = por definir, típico en PPD)' AFTER metodo_pago,
  ADD COLUMN moneda      VARCHAR(3) NULL AFTER forma_pago;

-- 2) Complementos de pago: N por factura (una parcialidad = un complemento)
CREATE TABLE oc_facturas_complementos (
  id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  id_factura       INT UNSIGNED  NOT NULL,                -- = oc_facturas.id (ajusta el tipo)
  uuid_factura     CHAR(36)      NOT NULL,                -- UUID de la factura a la que apunta (si se refactura, los anteriores dejan de contar)
  uuid_complemento CHAR(36)      NULL,                    -- UUID del CFDI tipo "P" (NULL = complemento anterior a este cambio)
  num_parcialidad  INT           NULL,
  fecha_pago       DATETIME      NULL,
  monto_pagado     DECIMAL(14,2) NULL,                    -- NULL = complemento anterior a este cambio (sin datos del XML)
  saldo_anterior   DECIMAL(14,2) NULL,
  saldo_insoluto   DECIMAL(14,2) NULL,
  archivo_xml      VARCHAR(255)  NULL,
  archivo_pdf      VARCHAR(255)  NULL,
  fecha_carga      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_factura_complemento (id_factura, uuid_complemento),
  KEY idx_factura (id_factura, uuid_factura),
  CONSTRAINT fk_comp_factura FOREIGN KEY (id_factura) REFERENCES oc_facturas (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT chk_comp_monto CHECK (monto_pagado IS NULL OR monto_pagado >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;   -- mismo COLLATE que oc_facturas

-- 3) Conserva los complementos que ya se habían cargado (un solo archivo por factura)
INSERT INTO oc_facturas_complementos (id_factura, uuid_factura, archivo_xml, archivo_pdf, fecha_carga)
SELECT f.id, UPPER(f.uuid_cfdi),
       CASE WHEN LOWER(f.archivo_complemento) LIKE '%.xml' THEN f.archivo_complemento END,
       CASE WHEN LOWER(f.archivo_complemento) LIKE '%.pdf' THEN f.archivo_complemento END,
       COALESCE(f.fecha_carga_complemento, NOW())
  FROM oc_facturas f
 WHERE f.archivo_complemento IS NOT NULL AND f.archivo_complemento <> '' AND f.uuid_cfdi IS NOT NULL;

-- Las facturas ya cargadas quedan con metodo_pago = NULL: el sistema lo lee del XML guardado
-- la primera vez que se abre la factura / sus complementos y lo persiste (no hace falta script).