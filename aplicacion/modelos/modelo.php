<?php

final class Modelos_Compras_Ordenes extends Modelo {
    protected $_db = null;

    // BD dedicada a órdenes de compra (mismo servidor MySQL que la BD principal)
    const DB_OC = 'saevalcas_db';

    // Estatus de oc_ordenes.status
    const ST_CANCELADA  = 0;
    const ST_GENERADA   = 1;
    const ST_REVISADA   = 2;   // se asigna sola al abrir la orden en la vista de visualizar
    const ST_AUTORIZADA = 3;

    // Debe coincidir con ISR_RESTA de procesar.js / visualizar.js / Modelos_Compras_Requisiciones
    const ISR_RESTA = false;

    // Tasas oficiales de I.V.A. (%) permitidas (igual que TASAS_IVA de Modelos_Compras_Requisiciones y de los JS)
    const TASAS_IVA = array(16, 8);

    // IDs de empleados que pueden autorizar órdenes. Vacío = cualquier usuario con sesión.
    const AUTORIZAN = array();

    // Misma carpeta que usa Modelos_Catalogos_Facturas::RUTA_ARCHIVOS
	const RUTA_FACTURAS      = '/proveedores/data/privada/facturas/';
	const MAX_PDF            = 10485760; // 10 MB
	const MAX_XML            = 5242880;  // 5 MB
	const REQUIERE_PAGO_COMPLEMENTO = false; // true: el complemento solo se carga con la factura pagada (status 2); false: también con la factura pendiente de pago (status 1)
	const VALIDAR_RFC_EMISOR = false;     // el RFC emisor del XML debe coincidir con el de la orden (si la orden lo tiene)

    public function iniciarDb($db) {
        if (!$this->_db) {
            $this->_db = $db;
        }
        $this->listadoEmp = array(1232,1238,1267,1305,1382,1387,1388,1389,1390,1400,1401);
        $this->listadoVerTodo = array(1455, 1572, 1591);

    }

    private function ejecutar($sql, array $params = array()) {
        $sth = $this->_db->prepare($sql);
        if (!$sth || !$sth->execute($params)) {
            $info = $sth ? $sth->errorInfo() : $this->_db->errorInfo();
            throw new RuntimeException('SQL: ' . ($info[2] ?? 'error desconocido'), (int) ($info[1] ?? 0));
        }
        return $sth;
    }

// Carpeta física. AJUSTA si tu raíz no es DOCUMENT_ROOT (p. ej. una constante propia).
	private function rutaFisicaFacturas() {
		return rtrim($_SERVER['DOCUMENT_ROOT'], '/') . self::RUTA_FACTURAS;
	}
 
	private function idProveedorSesion() {
		$id = (int) ($_SESSION['login_id'] ?? 0);
		if ($id <= 0) throw new InvalidArgumentException('Tu sesión expiró. Vuelve a iniciar sesión.');
		return $id;
	}
 
	private function sqlFactura($sql, array $params = array()) {
		$sth = $this->_db->prepare($sql);
		if (!$sth || !$sth->execute($params)) {
			$info = $sth ? $sth->errorInfo() : $this->_db->errorInfo();
			throw new RuntimeException('SQL: ' . ($info[2] ?? 'error'), (int) ($info[1] ?? 0));
		}
		return $sth;
	}
 
	/**
	 * La OC a facturar debe ser del proveedor en sesión, estar autorizada (3) y no cancelada.
	 * Cada OC se factura por separado: una orden sin sub-órdenes, o UNA sub-orden ($idSub obligatorio
	 * cuando la orden tiene sub-órdenes). Devuelve la orden con id_sub_orden (0 si no aplica),
	 * folio_doc (folio de la OC: '20001' o '20001-2') y total de esa OC.
	 */
	private function ordenDelProveedor($idOrden, $bloquear = false, $idSub = 0) {
		$idOrden = (int) $idOrden;
		$idSub = (int) $idSub;
		if ($idOrden <= 0 || $idSub < 0) throw new InvalidArgumentException('Orden no válida.');

		$o = $this->sqlFactura(
			"SELECT id, folio, proveedor_razon_social, proveedor_rfc, total, status, tiene_sub_ordenes
			   FROM oc_ordenes WHERE id = ? AND id_proveedor = ?" . ($bloquear ? ' FOR UPDATE' : ''),
			array($idOrden, $this->idProveedorSesion())
		)->fetch(PDO::FETCH_ASSOC);

		if (!$o) throw new InvalidArgumentException('La orden no existe.');
		if ((int) $o['status'] === self::ST_CANCELADA) throw new InvalidArgumentException('La orden está cancelada.');

		if ((int) $o['tiene_sub_ordenes'] === 1) {
			if ($idSub <= 0) throw new InvalidArgumentException('Indica la orden (sub-orden) que vas a facturar.');
			$s = $this->sqlFactura(
				"SELECT id, folio, total, status FROM oc_sub_ordenes WHERE id = ? AND id_orden = ? AND status > 0" . ($bloquear ? ' FOR UPDATE' : ''),
				array($idSub, $idOrden)
			)->fetch(PDO::FETCH_ASSOC);
			if (!$s) throw new InvalidArgumentException('La orden no existe.');
			if ((int) $s['status'] !== self::ST_AUTORIZADA) throw new InvalidArgumentException('Solo se pueden cargar facturas de órdenes autorizadas.');

			$o['id_sub_orden'] = (int) $s['id'];
			$o['folio_doc']    = (string) $s['folio'];
			$o['total']        = $s['total'];
		} else {
			if ($idSub > 0) throw new InvalidArgumentException('La orden no tiene sub-órdenes.');
			if ((int) $o['status'] !== self::ST_AUTORIZADA) throw new InvalidArgumentException('Solo se pueden cargar facturas de órdenes autorizadas.');

			$o['id_sub_orden'] = 0;
			$o['folio_doc']    = (string) $o['folio'];
		}
		return $o;
	}

	// Datos de la factura de una OC con formato para el portal
	private function formatearFactura($f) {
		if (!$f) return null;
		$f['status'] = (int) $f['status'];
		$f['num_refacturaciones'] = (int) $f['num_refacturaciones'];
		$f['monto'] = $f['monto'] !== null ? number_format((float) $f['monto'], 2) : null;
		return $f;
	}

	/**
	 * Factura de una OC.
	 *   Orden sin sub-órdenes, o con $idSub : devuelve la factura de esa OC (clave 'factura').
	 *   Orden con sub-órdenes y sin $idSub  : devuelve 'ordenes' = cada sub-orden con su propia factura
	 *                                         y si ya se puede facturar (autorizada).
	 */
	public function getFactura($idOrden, $idSub = null) {
		try {
			$idSub = (int) $idSub;

			if ($idSub <= 0) {
				$info = $this->sqlFactura(
					"SELECT id, folio, proveedor_razon_social, total, tiene_sub_ordenes
					   FROM oc_ordenes WHERE id = ? AND id_proveedor = ?",
					array((int) $idOrden, $this->idProveedorSesion())
				)->fetch(PDO::FETCH_ASSOC);
				if (!$info) throw new InvalidArgumentException('La orden no existe.');

				if ((int) $info['tiene_sub_ordenes'] === 1) {
					$subs = $this->sqlFactura(
						"SELECT s.id, s.folio, s.total, s.status AS status_oc,
								f.status, f.archivo_pdf, f.archivo_xml, f.archivo_complemento, f.uuid_cfdi, f.monto,
								f.motivo_refacturacion, f.num_refacturaciones
						   FROM oc_sub_ordenes s
						   LEFT JOIN oc_facturas f ON f.id_orden = s.id_orden AND f.id_sub_orden = s.id
						  WHERE s.id_orden = ? AND s.status > 0
						  ORDER BY s.consecutivo",
						array((int) $info['id'])
					)->fetchAll(PDO::FETCH_ASSOC);

					$ordenes = array();
					foreach ($subs as $s) {
						$f = $s['status'] !== null ? $this->formatearFactura(array(
							'status' => $s['status'], 'archivo_pdf' => $s['archivo_pdf'], 'archivo_xml' => $s['archivo_xml'],
							'archivo_complemento' => $s['archivo_complemento'], 'uuid_cfdi' => $s['uuid_cfdi'], 'monto' => $s['monto'],
							'motivo_refacturacion' => $s['motivo_refacturacion'], 'num_refacturaciones' => $s['num_refacturaciones'],
						)) : null;
						$ordenes[] = array(
							'id_sub_orden' => (int) $s['id'],
							'folio'        => $s['folio'],
							'total'        => number_format((float) $s['total'], 2),
							'facturable'   => ((int) $s['status_oc'] === self::ST_AUTORIZADA),
							'factura'      => $f,
						);
					}

					return array('type' => 'success', 'data' => array(
						'id'                => (int) $info['id'],
						'folio'             => (string) $info['folio'],
						'proveedor'         => $info['proveedor_razon_social'],
						'total'             => number_format((float) $info['total'], 2),
						'tiene_sub_ordenes' => true,
						'ordenes'           => $ordenes,
						'factura'           => null,
						'requiere_pago_complemento' => self::REQUIERE_PAGO_COMPLEMENTO,
					));
				}
			}

			$o = $this->ordenDelProveedor($idOrden, false, $idSub);

			$f = $this->sqlFactura(
				"SELECT status, archivo_pdf, archivo_xml, archivo_complemento, uuid_cfdi, monto,
						motivo_refacturacion, num_refacturaciones
				   FROM oc_facturas WHERE id_orden = ? AND id_sub_orden <=> ?",
				array((int) $o['id'], $o['id_sub_orden'] > 0 ? (int) $o['id_sub_orden'] : null)
			)->fetch(PDO::FETCH_ASSOC);

			$f = $this->formatearFactura($f);

			// Orden con sub-órdenes cuya factura se cargó completa antes de la separación: se muestra la global (solo lectura)
			if (!$f && $o['id_sub_orden'] > 0) {
				$g = $this->sqlFactura(
					"SELECT status, archivo_pdf, archivo_xml, archivo_complemento, uuid_cfdi, monto,
							motivo_refacturacion, num_refacturaciones
					   FROM oc_facturas WHERE id_orden = ? AND id_sub_orden IS NULL",
					array((int) $o['id'])
				)->fetch(PDO::FETCH_ASSOC);
				if ($g) { $f = $this->formatearFactura($g); $f['global'] = true; }
			}

			return array('type' => 'success', 'data' => array(
				'id'                => (int) $o['id'],
				'id_sub_orden'      => $o['id_sub_orden'] > 0 ? (int) $o['id_sub_orden'] : null,
				'folio'             => $o['folio_doc'],
				'proveedor'         => $o['proveedor_razon_social'],
				'total'             => number_format((float) $o['total'], 2),
				'tiene_sub_ordenes' => ((int) $o['tiene_sub_ordenes'] === 1),
				'factura'           => $f,
				'requiere_pago_complemento' => self::REQUIERE_PAGO_COMPLEMENTO,
			));
		} catch (\Throwable $th) {
			return $this->respuestaErrorFactura($th, 'getFactura', 'No se pudo consultar la factura.');
		}
	}
 
	/**
	 * Cada OC se factura por separado: $idSub = sub-orden (obligatorio si la orden tiene sub-órdenes).
	 * $post['tipo'] = 'factura'      -> archivo_pdf + archivo_xml (sin factura o status 3)
	 * $post['tipo'] = 'complemento'  -> archivo_complemento       (solo status 2)
	 */
	public function guardarFactura($idOrden, $post, $files, $idSub = null) {
		$nuevos = array(); // archivos ya movidos al disco, para borrarlos si algo falla
		$transaccion = false;
 
		try {
			$tipo = $post['tipo'] ?? '';
			if (!in_array($tipo, array('factura', 'complemento'), true)) throw new InvalidArgumentException('Operación no válida.');
 
			$this->_db->beginTransaction();
			$transaccion = true;
 
			$o = $this->ordenDelProveedor($idOrden, true, $idSub);
			$idSubFactura = $o['id_sub_orden'] > 0 ? (int) $o['id_sub_orden'] : null;

			// Orden con sub-órdenes que ya tiene una factura global (anterior a la separación): no se duplica
			if ($idSubFactura !== null) {
				$global = $this->sqlFactura(
					"SELECT id FROM oc_facturas WHERE id_orden = ? AND id_sub_orden IS NULL LIMIT 1",
					array((int) $o['id'])
				)->fetchColumn();
				if ($global) throw new InvalidArgumentException('Esta orden ya cuenta con una factura global. Contacta a Compras para facturar por sub-orden.');
			}

			$f = $this->sqlFactura(
				"SELECT id, status, archivo_pdf, archivo_xml FROM oc_facturas WHERE id_orden = ? AND id_sub_orden <=> ? FOR UPDATE",
				array((int) $o['id'], $idSubFactura)
			)->fetch(PDO::FETCH_ASSOC);
			$status = $f ? (int) $f['status'] : 0;
 
			$carpeta = $this->rutaFisicaFacturas();
			if (!is_dir($carpeta) && !@mkdir($carpeta, 0750, true)) throw new RuntimeException('No se pudo crear la carpeta de facturas.');
 
			if ($tipo === 'factura') {
				if ($status === 1) throw new InvalidArgumentException('La factura ya fue cargada y está pendiente de pago.');
				if ($status === 2) throw new InvalidArgumentException('La factura ya fue pagada.');
 
				$pdf = $this->validarSubida($files['archivo_pdf'] ?? null, array('pdf' => array('application/pdf')), self::MAX_PDF, 'El PDF');
				$xml = $this->validarSubida($files['archivo_xml'] ?? null, array('xml' => array('text/xml', 'application/xml', 'text/plain')), self::MAX_XML, 'El XML');
 
				if ($pdf['ext'] === 'pdf' && strncmp(file_get_contents($pdf['tmp'], false, null, 0, 5), '%PDF-', 5) !== 0) {
					throw new InvalidArgumentException('El PDF no es un archivo válido.');
				}
				$cfdi = $this->leerCfdi($xml['tmp']);
 
				if (self::VALIDAR_RFC_EMISOR && !empty($o['proveedor_rfc'])
					&& strcasecmp(trim($cfdi['rfc_emisor']), trim($o['proveedor_rfc'])) !== 0) {
					throw new InvalidArgumentException('El RFC emisor del XML no coincide con el RFC del proveedor de la orden.');
				}
 
				// El mismo CFDI no puede usarse en otra OC (ni en otra sub-orden)
				$dup = $this->sqlFactura(
					"SELECT id FROM oc_facturas WHERE uuid_cfdi = ? AND id <> ? LIMIT 1",
					array($cfdi['uuid'], $f ? (int) $f['id'] : 0)
				)->fetchColumn();
				if ($dup) throw new InvalidArgumentException('Este CFDI (UUID) ya fue cargado en otra orden.');
 
				$base = 'oc' . preg_replace('/[^0-9A-Za-z-]/', '', $o['folio_doc']) . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));
				$nombrePdf = $base . '_factura.pdf';
				$nombreXml = $base . '_factura.xml';
				$nuevos[] = $this->moverSubida($pdf['tmp'], $carpeta . $nombrePdf);
				$nuevos[] = $this->moverSubida($xml['tmp'], $carpeta . $nombreXml);
 
				if ($f) { // refacturación (status 3): regresa a pendiente
					$this->sqlFactura(
						"UPDATE oc_facturas
						    SET archivo_pdf = ?, archivo_xml = ?, uuid_cfdi = ?, monto = ?,
						        status = 1, num_refacturaciones = num_refacturaciones + 1, fecha_carga = NOW()
						  WHERE id = ? AND status = 3",
						array($nombrePdf, $nombreXml, $cfdi['uuid'], $cfdi['total'], (int) $f['id'])
					);
					$msj = 'Refacturación enviada. Quedó pendiente de pago.';
				} else {
					$this->sqlFactura(
						"INSERT INTO oc_facturas (id_orden, id_sub_orden, archivo_pdf, archivo_xml, uuid_cfdi, monto) VALUES (?, ?, ?, ?, ?, ?)",
						array((int) $o['id'], $idSubFactura, $nombrePdf, $nombreXml, $cfdi['uuid'], $cfdi['total'])
					);
					$msj = 'Factura cargada correctamente.';
				}
			} else { // complemento
				// Estatus en los que se admite el complemento (siempre debe existir la factura y no estar en refacturación)
				$permitidos = self::REQUIERE_PAGO_COMPLEMENTO ? array(2) : array(1, 2);
				if (!in_array($status, $permitidos, true)) {
					if ($status === 0) throw new InvalidArgumentException('Primero debes cargar la factura (PDF y XML).');
					if ($status === 3) throw new InvalidArgumentException('La factura está en refacturación. Primero carga la nueva factura.');
					throw new InvalidArgumentException('El complemento de pago se habilita cuando la factura ya fue pagada.');
				}
 
				$comp = $this->validarSubida($files['archivo_complemento'] ?? null, array(
					'pdf' => array('application/pdf'),
					'xml' => array('text/xml', 'application/xml', 'text/plain'),
				), self::MAX_PDF, 'El complemento');
 
				if ($comp['ext'] === 'pdf' && strncmp(file_get_contents($comp['tmp'], false, null, 0, 5), '%PDF-', 5) !== 0) {
					throw new InvalidArgumentException('El PDF no es un archivo válido.');
				}
				if ($comp['ext'] === 'xml') $this->leerCfdi($comp['tmp'], false); // solo verifica que sea XML bien formado
 
				$nombre = 'oc' . preg_replace('/[^0-9A-Za-z-]/', '', $o['folio_doc']) . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '_complemento.' . $comp['ext'];
				$nuevos[] = $this->moverSubida($comp['tmp'], $carpeta . $nombre);
 
				$this->sqlFactura(
					"UPDATE oc_facturas SET archivo_complemento = ?, fecha_carga_complemento = NOW() WHERE id = ? AND status IN (" . implode(',', $permitidos) . ")",
					array($nombre, (int) $f['id'])
				);
 
				// Verifica que de verdad quedó guardado (no confiar solo en que el UPDATE no lanzó error)
				$guardado = $this->sqlFactura(
					"SELECT archivo_complemento FROM oc_facturas WHERE id = ?",
					array((int) $f['id'])
				)->fetchColumn();
				if ($guardado !== $nombre) {
					error_log('[Compras_Ordenes::guardarFactura] El UPDATE del complemento no modificó oc_facturas (id_factura=' . (int) $f['id'] . ', id_orden=' . (int) $o['id'] . ', status=' . $status . ')');
					throw new RuntimeException('El complemento no se registró en la base de datos.');
				}
				$msj = 'Complemento de pago cargado correctamente.';
			}
 
			$this->_db->commit();
			$transaccion = false;
			return array('type' => 'success', 'msj' => $msj);
 
		} catch (\Throwable $th) {
			if ($transaccion && $this->_db->inTransaction()) $this->_db->rollBack();
			foreach ($nuevos as $ruta) { if ($ruta && is_file($ruta)) @unlink($ruta); }
 
			// 1062 = duplicate key: dos cargas simultáneas de la misma orden
			if ($th instanceof PDOException || ($th instanceof RuntimeException && (int) $th->getCode() === 1062)) {
				if ((int) $th->getCode() === 1062 || strpos($th->getMessage(), '1062') !== false) {
					return array('type' => 'error', 'msj' => 'La factura de esta orden ya fue cargada. Actualiza la página.');
				}
			}
			return $this->respuestaErrorFactura($th, 'guardarFactura', 'No se pudieron guardar los archivos. Intenta nuevamente o contacta a soporte.');
		}
	}
 
	// Valida un $_FILES[x]: subida real, tamaño, extensión permitida y MIME detectado por contenido
	private function validarSubida($file, array $permitidos, $maxBytes, $etiqueta) {
		if (!is_array($file) || !isset($file['error']) || is_array($file['error'])) throw new InvalidArgumentException($etiqueta . ' es obligatorio.');
		if ($file['error'] === UPLOAD_ERR_NO_FILE) throw new InvalidArgumentException($etiqueta . ' es obligatorio.');
		if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) throw new InvalidArgumentException($etiqueta . ' excede el tamaño permitido.');
		if ($file['error'] !== UPLOAD_ERR_OK) throw new InvalidArgumentException('No se pudo recibir ' . strtolower($etiqueta) . '. Intenta de nuevo.');
		if (!is_uploaded_file($file['tmp_name'])) throw new InvalidArgumentException('Archivo no válido.');
		if ($file['size'] <= 0) throw new InvalidArgumentException($etiqueta . ' está vacío.');
		if ($file['size'] > $maxBytes) throw new InvalidArgumentException($etiqueta . ' excede ' . round($maxBytes / 1048576) . ' MB.');
 
		$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		if (!isset($permitidos[$ext])) throw new InvalidArgumentException($etiqueta . ': formato no permitido (' . strtoupper(implode(' / ', array_keys($permitidos))) . ').');
 
		$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
		if (!in_array($mime, $permitidos[$ext], true)) throw new InvalidArgumentException($etiqueta . ': el contenido no corresponde a un archivo ' . strtoupper($ext) . '.');
 
		return array('tmp' => $file['tmp_name'], 'ext' => $ext);
	}
 
	private function moverSubida($tmp, $destino) {
		if (!move_uploaded_file($tmp, $destino)) throw new RuntimeException('No se pudo guardar el archivo en el servidor.');
		@chmod($destino, 0640);
		return $destino;
	}
 
	/**
	 * Lee un CFDI (sin red, sin entidades externas). Con $estricto = true exige UUID y Total
	 * y devuelve array(uuid, total, rfc_emisor).
	 */
	private function leerCfdi($ruta, $estricto = true) {
		$xml = file_get_contents($ruta);
		if ($xml === false || $xml === '') throw new InvalidArgumentException('El XML está vacío.');
		if (stripos($xml, '<!DOCTYPE') !== false || stripos($xml, '<!ENTITY') !== false) throw new InvalidArgumentException('El XML no es un CFDI válido.');
 
		$previo = libxml_use_internal_errors(true);
		$dom = new DOMDocument();
		$ok = $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS);
		libxml_clear_errors();
		libxml_use_internal_errors($previo);
		if (!$ok) throw new InvalidArgumentException('El XML está mal formado.');
		if (!$estricto) return array();
 
		$xp = new DOMXPath($dom);
		$comp = $xp->query('/*[local-name()="Comprobante"]')->item(0);
		if (!$comp) throw new InvalidArgumentException('El XML no es un CFDI (falta el nodo Comprobante).');
 
		$timbre = $xp->query('//*[local-name()="TimbreFiscalDigital"]')->item(0);
		$uuid = $timbre ? strtoupper(trim($timbre->getAttribute('UUID'))) : '';
		if (!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/', $uuid)) {
			throw new InvalidArgumentException('El CFDI no está timbrado (no se encontró el UUID).');
		}
 
		$total = $comp->getAttribute('Total');
		if (!is_numeric($total) || (float) $total < 0) throw new InvalidArgumentException('No se pudo leer el total del CFDI.');
 
		$emisor = $xp->query('//*[local-name()="Emisor"]')->item(0);
 
		return array(
			'uuid'        => $uuid,
			'total'       => round((float) $total, 2),
			'rfc_emisor'  => $emisor ? $emisor->getAttribute('Rfc') : '',
		);
	}
 
	private function respuestaErrorFactura(\Throwable $th, $contexto, $generico) {
		if (!($th instanceof InvalidArgumentException)) error_log('[Compras_Ordenes::' . $contexto . '] ' . $th->getMessage());
		return array('type' => 'error', 'msj' => ($th instanceof InvalidArgumentException) ? $th->getMessage() : $generico);
	}

    public function getListado($context = null, $id_proveedor = null){
        $response = array();

        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS");
        header("Cache-Control:");

        try {
            // Cada pestaña del listado muestra las órdenes de un estatus
            $statusPorPestana = array(
                'generados'   => self::ST_GENERADA,
                'revisadas'   => self::ST_REVISADA,
                'autorizadas' => self::ST_AUTORIZADA,
            );
            if (!is_string($context) || !isset($statusPorPestana[$context])) {
                throw new InvalidArgumentException('Listado no válido.');
            }

            $db = '`' . self::DB_OC . '`';

            // Reservado para restringir la visibilidad por usuario / rol si se necesita
            // El estatus es de cada OC: orden sin sub-órdenes (oc_ordenes.status) o sub-orden (oc_sub_ordenes.status)
            $statusTab = (int) $statusPorPestana[$context];
            $filtroProv = '';

            // Filtro opcional por proveedor (oc_ordenes.id_proveedor). Sin parámetro = todos los proveedores.
            if ($id_proveedor !== null && $id_proveedor !== '') {
                if (!ctype_digit((string) $id_proveedor) || (int) $id_proveedor <= 0) {
                    throw new InvalidArgumentException('Proveedor no válido.');
                }
                $filtroProv = ' AND o.id_proveedor = ' . (int) $id_proveedor; // entero validado: seguro de interpolar
            }

            // BEGIN :: INIT PARAMS
                $PAGINATION = (isset($_POST['pagination']) && is_array($_POST['pagination'])) ? $_POST['pagination'] : array();
                $SORT       = (isset($_POST['sort']) && is_array($_POST['sort'])) ? $_POST['sort'] : array();
                $QUERY      = (isset($_POST['query']) && is_array($_POST['query'])) ? $_POST['query'] : array();
            // END :: INIT PARAMS

            // BEGIN :: BUSQUEDA (campo "Buscar...")
            // Cada fila del listado es una orden SIN sub-órdenes o UNA sub-orden (folio 20001-1, 20001-2...).
            // Busca por folio (de la fila o de la orden principal), proveedor, unidad de negocio y centro de costo
                $BUSQUEDA = (isset($QUERY['generalSearch']) && is_string($QUERY['generalSearch']) && trim($QUERY['generalSearch']) !== '') ? trim($QUERY['generalSearch']) : null;
                $buscaOrden = '';
                $buscaSub = '';
                if ($BUSQUEDA !== null) {
                    $texto = str_replace(array('\\', '%', '_'), array('\\\\', '\\%', '\\_'), $BUSQUEDA);
                    $B = $this->_db->quote('%' . $texto . '%');
                    $buscaOrden = " AND (
                        CAST(o.folio AS CHAR) LIKE $B
                        OR o.proveedor_razon_social LIKE $B
                        OR EXISTS (
                            SELECT 1 FROM oc_partidas p
                            JOIN centros_trabajo ct ON ct.id = p.id_unidad_negocio
                            LEFT JOIN centro_trabajo_areas cta ON cta.id_ct_area = p.id_area_centro_costo
                            WHERE p.id_orden = o.id
                            AND (CONVERT(ct.nombre USING utf8mb4) LIKE $B OR CONVERT(cta.nombre_area USING utf8mb4) LIKE $B)
                        )
                    )";
                    $buscaSub = " AND (
                        s.folio LIKE $B
                        OR CAST(o.folio AS CHAR) LIKE $B
                        OR o.proveedor_razon_social LIKE $B
                        OR EXISTS (
                            SELECT 1 FROM oc_partidas p
                            JOIN centros_trabajo ct ON ct.id = p.id_unidad_negocio
                            LEFT JOIN centro_trabajo_areas cta ON cta.id_ct_area = p.id_area_centro_costo
                            WHERE p.id_sub_orden = s.id
                            AND (CONVERT(ct.nombre USING utf8mb4) LIKE $B OR CONVERT(cta.nombre_area USING utf8mb4) LIKE $B)
                        )
                    )";
                }
            // END :: BUSQUEDA

            // BEGIN :: FILAS BASE (orden sin sub-órdenes  UNION  una fila por sub-orden vigente)
            // Cada sub-orden tiene su propio estatus (revisión / autorización individual).
                $CONV = 'USING utf8mb4';
                $whereOrden = "WHERE o.status = $statusTab AND o.tiene_sub_ordenes = 0 $filtroProv";
                $whereSub   = "WHERE o.status <> " . self::ST_CANCELADA . " AND o.tiene_sub_ordenes = 1 AND s.status = $statusTab $filtroProv";
                $base = "SELECT o.id AS id_orden, NULL AS id_sub_orden,
                                CONVERT(CAST(o.folio AS CHAR) $CONV) AS folio_texto, o.folio AS folio_orden, 0 AS consecutivo,
                                CONVERT(o.proveedor_razon_social $CONV) AS proveedor, o.total AS total,
                                o.status AS status_fila, o.fecha_creacion,
                                o.id_usuario_revisa, o.fecha_revision, o.id_usuario_autoriza, o.fecha_autorizacion,
                                (SELECT f.status FROM oc_facturas f WHERE f.id_orden = o.id AND f.id_sub_orden IS NULL LIMIT 1) AS factura_status,
                                (SELECT f.motivo_refacturacion FROM oc_facturas f WHERE f.id_orden = o.id AND f.id_sub_orden IS NULL LIMIT 1) AS motivo_refacturacion
                         FROM oc_ordenes o
                         $whereOrden $buscaOrden
                         UNION ALL
                         SELECT o.id, s.id,
                                CONVERT(s.folio $CONV), o.folio, s.consecutivo,
                                CONVERT(o.proveedor_razon_social $CONV), s.total,
                                s.status, o.fecha_creacion,
                                s.id_usuario_revisa, s.fecha_revision, s.id_usuario_autoriza, s.fecha_autorizacion,
                                -- factura propia de la sub-orden; si no tiene, la global anterior a la separación (si existe)
                                COALESCE((SELECT f.status FROM oc_facturas f WHERE f.id_orden = o.id AND f.id_sub_orden = s.id LIMIT 1),
                                         (SELECT f.status FROM oc_facturas f WHERE f.id_orden = o.id AND f.id_sub_orden IS NULL LIMIT 1)) AS factura_status,
                                COALESCE((SELECT f.motivo_refacturacion FROM oc_facturas f WHERE f.id_orden = o.id AND f.id_sub_orden = s.id LIMIT 1),
                                         (SELECT f.motivo_refacturacion FROM oc_facturas f WHERE f.id_orden = o.id AND f.id_sub_orden IS NULL LIMIT 1)) AS motivo_refacturacion
                         FROM oc_ordenes o
                         JOIN oc_sub_ordenes s ON s.id_orden = o.id
                         $whereSub $buscaSub";
            // END :: FILAS BASE

            // BEGIN :: META PARAMS
                $globalTotal = (int) $this->ejecutar("SELECT COUNT(*) FROM ($base) r")->fetchColumn();

                $paginaActual = max(1, (int) ($PAGINATION['page'] ?? 1));
                $rowsPorPagina = (int) ($PAGINATION['perpage'] ?? 10);
                if ($rowsPorPagina === -1) {
                    $rowsPorPagina = max($globalTotal, 1);
                } elseif ($rowsPorPagina < 1) {
                    $rowsPorPagina = 10;
                }
                $rowsPorPagina = min($rowsPorPagina, 5000);

                $totalPaginas = max(1, (int) ceil($globalTotal / $rowsPorPagina));
                if ($paginaActual > $totalPaginas) $paginaActual = $totalPaginas;

                $sentido = (isset($SORT['sort']) && strtolower($SORT['sort']) === 'asc') ? 'asc' : 'desc';
                $campo   = (isset($SORT['field']) && is_string($SORT['field'])) ? $SORT['field'] : 'folio';

                $meta = array(
                    'page' => $paginaActual,
                    'perpage' => $rowsPorPagina,
                    'pages' => $totalPaginas,
                    'total' => $globalTotal,
                    'desplazamiento' => ($paginaActual - 1) * $rowsPorPagina,
                    'sort' => $sentido,
                    'field' => $campo,
                );
            // END :: META PARAMS

            // BEGIN :: ORDER BY (lista blanca: nunca se interpola el campo recibido)
            // Desempate: orden principal descendente y sub-órdenes en su consecutivo (-1, -2, -3)
                $columnasOrden = array(
                    'folio'     => 'r.folio_orden',
                    'proveedor' => 'r.proveedor',
                    'total'     => 'r.total',
                    'fecha'     => 'r.fecha_creacion',
                    'fecha_revision'     => 'r.fecha_revision',
                    'fecha_autorizacion' => 'r.fecha_autorizacion',
                );
                $ORDER_BY = ' ORDER BY ' . ($columnasOrden[$campo] ?? 'r.folio_orden') . ' ' . $sentido . ', r.folio_orden DESC, r.consecutivo ASC';
            // END :: ORDER BY

            $LIMIT = 'LIMIT ' . (int) $meta['perpage'] . ' OFFSET ' . (int) $meta['desplazamiento'];

            // LISTADO: 1) filas de la página (cada una = orden o sub-orden)
            $filas = $this->ejecutar("SELECT r.* FROM ($base) r $ORDER_BY $LIMIT")->fetchAll(PDO::FETCH_ASSOC);

            $data = array();

            if (!empty($filas)) {
                $ids = array();
                foreach ($filas as $f) $ids[(int) $f['id_orden']] = (int) $f['id_orden'];
                $ids = array_values($ids);
                $in = implode(',', array_fill(0, count($ids), '?'));

                // 2) datos de la orden principal
                $cabeceras = array();
                $cabs = $this->ejecutar(
                    "SELECT o.id, o.total, o.tiene_sub_ordenes, o.total_sub_ordenes
                     FROM oc_ordenes o
                     WHERE o.id IN ($in)",
                    $ids
                )->fetchAll(PDO::FETCH_ASSOC);
                foreach ($cabs as $c) $cabeceras[(int) $c['id']] = $c;

                // Nombres de quien revisó / autorizó cada fila
                $idsEmp = array();
                foreach ($filas as $f) {
                    if ($f['id_usuario_revisa'])   $idsEmp[(int) $f['id_usuario_revisa']] = true;
                    if ($f['id_usuario_autoriza']) $idsEmp[(int) $f['id_usuario_autoriza']] = true;
                }
                $nombres = $this->nombresEmpleados(array_keys($idsEmp));

                // 3) partidas agrupadas por sub-orden / unidad de negocio / centro de costo
                //    (id_sub_orden NULL => clave 0 = orden sin sub-órdenes)
                $detallePorFila = array();
                $detalle = $this->ejecutar(
                    "SELECT p.id_orden, p.id_sub_orden, p.folio_oc,
                            p.id_unidad_negocio, ct.nombre AS unidad_negocio,
                            p.id_area_centro_costo, cta.nombre_area,
                            COUNT(*) AS partidas, SUM(p.total) AS monto
                     FROM oc_partidas p
                     JOIN centros_trabajo ct ON ct.id = p.id_unidad_negocio
                     LEFT JOIN centro_trabajo_areas cta ON cta.id_ct_area = p.id_area_centro_costo
                     WHERE p.id_orden IN ($in)
                     GROUP BY p.id_orden, p.id_sub_orden, p.folio_oc, p.id_unidad_negocio, ct.nombre, p.id_area_centro_costo, cta.nombre_area
                     ORDER BY p.id_orden, p.id_sub_orden, cta.nombre_area",
                    $ids
                )->fetchAll(PDO::FETCH_ASSOC);
                foreach ($detalle as $d) {
                    $detallePorFila[(int) $d['id_orden']][(int) $d['id_sub_orden']][] = $d;
                }

                foreach ($filas as $f) {
                    $idOrden = (int) $f['id_orden'];
                    $idSub = $f['id_sub_orden'] !== null ? (int) $f['id_sub_orden'] : 0;
                    $esSub = $idSub > 0;
                    $o = $cabeceras[$idOrden] ?? null;
                    if (!$o) continue;

                    $detalleFila = $detallePorFila[$idOrden][$idSub] ?? array();

                    // Unidades de negocio y centros de costo de ESTA fila
                    $unidades = array();
                    $centrosCosto = array();
                    $totalPartidas = 0;
                    foreach ($detalleFila as $d) {
                        $idUnidad = (int) $d['id_unidad_negocio'];
                        if (!isset($unidades[$idUnidad])) {
                            $unidades[$idUnidad] = array(
                                'id' => $idUnidad,
                                'nombre' => $d['unidad_negocio'],
                                'folio' => $f['folio_texto'],
                                'partidas' => 0,
                                'monto' => 0.0,
                            );
                        }
                        $unidades[$idUnidad]['partidas'] += (int) $d['partidas'];
                        $unidades[$idUnidad]['monto'] += (float) $d['monto'];
                        $totalPartidas += (int) $d['partidas'];

                        $centrosCosto[] = array(
                            'id' => (int) $d['id_area_centro_costo'],
                            'nombre' => $d['nombre_area'] !== null && $d['nombre_area'] !== '' ? $d['nombre_area'] : 'Sin centro de costo',
                            'unidad_negocio' => $d['unidad_negocio'],
                            'folio' => $d['folio_oc'],
                            'partidas' => (int) $d['partidas'],
                            'monto' => number_format((float) $d['monto'], 2, '.', ','),
                        );
                    }
                    foreach ($unidades as $k => $u) {
                        $unidades[$k]['monto'] = number_format($u['monto'], 2, '.', ',');
                    }

                    $fechaTimeStamp = (new DateTime($f['fecha_creacion']))->getTimestamp();

                    $data[] = array(
                        // id = orden principal (las acciones de abrir / revisar / autorizar operan sobre ella)
                        'id' => $idOrden,
                        'id_sub_orden' => $esSub ? $idSub : null,
                        'es_sub_orden' => $esSub,
                        'consecutivo' => (int) $f['consecutivo'],
                        'folio' => (string) $f['folio_texto'],
                        'folio_orden' => (string) $f['folio_orden'],
                        // Referencia para el PDF: folio de la sub-orden ('20001-2') o id de la orden
                        'pdf_ref' => $esSub ? (string) $f['folio_texto'] : (string) $idOrden,
                        'proveedor' => $f['proveedor'],
                        'total' => number_format((float) $f['total'], 2, '.', ','),
                        'total_orden' => number_format((float) $o['total'], 2, '.', ','),
                        'tiene_sub_ordenes' => (int) $o['tiene_sub_ordenes'] === 1,
                        'total_sub_ordenes' => (int) $o['total_sub_ordenes'],
                        'total_partidas' => $totalPartidas,
                        'unidades' => array_values($unidades),
                        'centros_costo' => $centrosCosto,
                        'factura_status' => $f['factura_status'] !== null ? (int) $f['factura_status'] : null,
                        'motivo_refacturacion' => (string) $f['motivo_refacturacion'],
                        'status' => (int) $f['status_fila'],
                        'status_texto' => $this->textoStatus($f['status_fila']),
                        'revisada_por' => $nombres[(int) $f['id_usuario_revisa']] ?? '',
                        'fecha_revision' => $f['fecha_revision'] ? Modelos_Fecha::formatearFecha($f['fecha_revision']) : '',
                        'autorizada_por' => $nombres[(int) $f['id_usuario_autoriza']] ?? '',
                        'fecha_autorizacion' => $f['fecha_autorizacion'] ? Modelos_Fecha::formatearFecha($f['fecha_autorizacion']) : '',
                        'fecha' => Modelos_Fecha::formatearFecha($f['fecha_creacion']),
                        'fechaTimeStamp' => $fechaTimeStamp,
                    );
                }
            }

            $response = array('meta' => $meta, 'data' => $data);

        } catch (\Throwable $th) {
            if (!($th instanceof InvalidArgumentException)) {
                error_log('[Compras_Ordenes::getListado] ' . $th->getMessage());
            }
            $response = array(
                'type' => 'error',
                'msj' => ($th instanceof InvalidArgumentException) ? $th->getMessage() : 'No se pudo cargar el listado de órdenes de compra.',
                'data' => array(),
            );
        }
        return $response;
    }

    // =====================================================================
    //  VISUALIZAR / EDITAR / REVISAR / AUTORIZAR
    // =====================================================================

    private function textoStatus($status) {
        $mapa = array(
            self::ST_CANCELADA  => 'Cancelada',
            self::ST_GENERADA   => 'Generada',
            self::ST_REVISADA   => 'Revisada',
            self::ST_AUTORIZADA => 'Autorizada',
        );
        return $mapa[(int) $status] ?? 'Desconocido';
    }

    private function idUsuarioSesion() {
        $id = (int) ($_SESSION['login_id'] ?? 0);
        if ($id <= 0) throw new InvalidArgumentException('Tu sesión expiró. Vuelve a iniciar sesión.');
        return $id;
    }

    private function puedeAutorizar($idUsuario) {
        return empty(self::AUTORIZAN) || in_array((int) $idUsuario, self::AUTORIZAN, true);
    }

    private function respuestaError(\Throwable $th, $contexto, $mensajeGenerico) {
        if (!($th instanceof InvalidArgumentException)) {
            error_log('[Compras_Ordenes::' . $contexto . '] ' . $th->getMessage());
        }
        return array(
            'type' => 'error',
            'msj'  => ($th instanceof InvalidArgumentException) ? $th->getMessage() : $mensajeGenerico,
        );
    }

    /**
     * Generada (1) -> Revisada (2). Se llama al abrir la vista de la orden.
     * Solo aplica a órdenes SIN sub-órdenes: cuando hay sub-órdenes cada una se revisa de forma
     * individual con su botón del listado (revisarOrden con id de sub-orden).
     * Es atómico e idempotente. Devuelve true si cambió.
     */
    public function marcarRevisada($idOrden) {
        try {
            $idUsuario = $this->idUsuarioSesion();
            $sth = $this->ejecutar(
                "UPDATE oc_ordenes
                 SET status = ?, id_usuario_revisa = ?, fecha_revision = NOW()
                 WHERE id = ? AND status = ? AND tiene_sub_ordenes = 0",
                array(self::ST_REVISADA, $idUsuario, (int) $idOrden, self::ST_GENERADA)
            );
            return $sth->rowCount() === 1;
        } catch (\Throwable $th) {
            error_log('[Compras_Ordenes::marcarRevisada] ' . $th->getMessage());
            return false;
        }
    }

    // Nombre completo de empleados {id => nombre}
    private function nombresEmpleados(array $ids) {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (empty($ids)) return array();
        $in = implode(',', array_fill(0, count($ids), '?'));
        $filas = $this->ejecutar("SELECT id, CONCAT(nombre, ' ', apellidos) FROM empleados WHERE id IN ($in)", $ids)->fetchAll(PDO::FETCH_KEY_PAIR);
        return array_map('trim', $filas);
    }

    private function tieneSubAutorizada($idOrden) {
        return (int) $this->ejecutar(
            "SELECT COUNT(*) FROM oc_sub_ordenes WHERE id_orden = ? AND status = ?",
            array((int) $idOrden, self::ST_AUTORIZADA)
        )->fetchColumn() > 0;
    }

    /**
     * La orden principal resume el estatus de sus sub-órdenes vigentes: queda en el MÁS BAJO de ellas
     * (Revisada cuando todas están revisadas o autorizadas, Autorizada cuando todas están autorizadas).
     * Así facturas, edición y demás procesos que leen oc_ordenes.status siguen funcionando.
     * Debe llamarse dentro de la transacción que cambió el estatus de una sub-orden.
     */
    private function sincronizarEstatusOrden($idOrden) {
        $subs = $this->ejecutar(
            "SELECT status, id_usuario_revisa, fecha_revision, id_usuario_autoriza, fecha_autorizacion
             FROM oc_sub_ordenes WHERE id_orden = ? AND status > 0",
            array((int) $idOrden)
        )->fetchAll(PDO::FETCH_ASSOC);
        if (empty($subs)) return;

        $min = self::ST_AUTORIZADA;
        $rev = null;
        $aut = null;
        foreach ($subs as $sub) {
            $min = min($min, (int) $sub['status']);
            if ($sub['fecha_revision'] && (!$rev || $sub['fecha_revision'] > $rev['fecha_revision'])) $rev = $sub;
            if ($sub['fecha_autorizacion'] && (!$aut || $sub['fecha_autorizacion'] > $aut['fecha_autorizacion'])) $aut = $sub;
        }

        // Quien/cuándo de la orden = la última revisión / autorización de sus sub-órdenes
        $conRev = ($min >= self::ST_REVISADA && $rev);
        $conAut = ($min >= self::ST_AUTORIZADA && $aut);
        $this->ejecutar(
            "UPDATE oc_ordenes
             SET status = ?, id_usuario_revisa = ?, fecha_revision = ?, id_usuario_autoriza = ?, fecha_autorizacion = ?
             WHERE id = ? AND status > 0",
            array($min,
                  $conRev ? $rev['id_usuario_revisa'] : null, $conRev ? $rev['fecha_revision'] : null,
                  $conAut ? $aut['id_usuario_autoriza'] : null, $conAut ? $aut['fecha_autorizacion'] : null,
                  (int) $idOrden)
        );
    }

    /**
     * Avanza el estatus de UNA OC:  Generada -> Revisada  o  Revisada -> Autorizada.
     *   $idSub informado : solo esa sub-orden.
     *   $idSub vacío     : orden sin sub-órdenes -> la orden; con sub-órdenes -> todas las que estén en el
     *                      estatus previo (lo usa el botón "Autorizar" de la vista completa de la orden).
     */
    private function avanzarEstatus($idOrden, $idSub, $nuevo) {
        header("Content-Type: application/json");
        $transaccion = false;
        $esRevision = ($nuevo === self::ST_REVISADA);
        $accion = $esRevision ? 'revisarOrden' : 'autorizarOrden';

        try {
            $idOrden = (int) $idOrden;
            $idSub = ($idSub !== null && $idSub !== '') ? (int) $idSub : 0;
            if ($idOrden <= 0 || $idSub < 0) throw new InvalidArgumentException('Orden de compra no válida.');
            $idUsuario = $this->idUsuarioSesion();

            if (!$esRevision && !$this->puedeAutorizar($idUsuario)) {
                throw new InvalidArgumentException('No tienes permiso para autorizar órdenes de compra.');
            }

            $desde = $nuevo - 1;
            $verbo = $esRevision ? 'revisar' : 'autorizar';
            $colUsuario = $esRevision ? 'id_usuario_revisa' : 'id_usuario_autoriza';
            $colFecha   = $esRevision ? 'fecha_revision' : 'fecha_autorizacion';
            $requerido  = $esRevision ? 'Generada' : 'Revisada';

            $this->_db->beginTransaction();
            $transaccion = true;

            $o = $this->ejecutar("SELECT folio, status, tiene_sub_ordenes FROM oc_ordenes WHERE id = ? FOR UPDATE", array($idOrden))->fetch(PDO::FETCH_ASSOC);
            if (!$o) throw new InvalidArgumentException('La orden de compra no existe.');
            if ((int) $o['status'] === self::ST_CANCELADA) throw new InvalidArgumentException('La orden está cancelada y no se puede ' . $verbo . '.');

            if ((int) $o['tiene_sub_ordenes'] !== 1) {
                // ---- Orden sin sub-órdenes ----
                if ($idSub > 0) throw new InvalidArgumentException('La orden no tiene sub-órdenes.');
                $status = (int) $o['status'];
                if ($status === $nuevo) throw new InvalidArgumentException('La orden ya estaba ' . ($esRevision ? 'revisada.' : 'autorizada.'));
                if ($status !== $desde) throw new InvalidArgumentException('La orden debe estar en estatus ' . $requerido . ' para ' . $verbo . 'se.');

                $this->ejecutar(
                    "UPDATE oc_ordenes SET status = ?, $colUsuario = ?, $colFecha = NOW() WHERE id = ? AND status = ?",
                    array($nuevo, $idUsuario, $idOrden, $desde)
                );
                $etiqueta = $o['folio'];
            } else {
                // ---- Orden con sub-órdenes: el estatus es de cada sub-orden ----
                $sql = "SELECT id, folio, status FROM oc_sub_ordenes WHERE id_orden = ? AND status > 0";
                $params = array($idOrden);
                if ($idSub > 0) { $sql .= " AND id = ?"; $params[] = $idSub; }
                $subs = $this->ejecutar($sql . " ORDER BY consecutivo FOR UPDATE", $params)->fetchAll(PDO::FETCH_ASSOC);
                if (empty($subs)) throw new InvalidArgumentException($idSub > 0 ? 'La sub-orden no existe o está cancelada.' : 'La orden no tiene sub-órdenes vigentes.');

                $aplicables = array();
                foreach ($subs as $sub) if ((int) $sub['status'] === $desde) $aplicables[] = $sub;

                if (empty($aplicables)) {
                    if ($idSub > 0) {
                        if ((int) $subs[0]['status'] === $nuevo) throw new InvalidArgumentException('La orden ' . $subs[0]['folio'] . ' ya estaba ' . ($esRevision ? 'revisada.' : 'autorizada.'));
                        throw new InvalidArgumentException('La orden ' . $subs[0]['folio'] . ' debe estar en estatus ' . $requerido . ' para ' . $verbo . 'se.');
                    }
                    throw new InvalidArgumentException('Ninguna sub-orden está en estatus ' . $requerido . '.');
                }

                $idsAplicar = array();
                foreach ($aplicables as $sub) $idsAplicar[] = (int) $sub['id'];
                $in = implode(',', array_fill(0, count($idsAplicar), '?'));
                $this->ejecutar(
                    "UPDATE oc_sub_ordenes SET status = ?, $colUsuario = ?, $colFecha = NOW() WHERE id IN ($in) AND status = ?",
                    array_merge(array($nuevo, $idUsuario), $idsAplicar, array($desde))
                );

                $this->sincronizarEstatusOrden($idOrden);
                $etiqueta = count($aplicables) === 1 ? $aplicables[0]['folio'] : $o['folio'] . ' (' . count($aplicables) . ' sub-órdenes)';
            }

            $this->_db->commit();
            $transaccion = false;

            return array('type' => 'success', 'msj' => 'Orden de compra ' . $etiqueta . ($esRevision ? ' marcada como revisada.' : ' autorizada.'));

        } catch (\Throwable $th) {
            if ($transaccion && $this->_db->inTransaction()) $this->_db->rollBack();
            return $this->respuestaError($th, $accion, $esRevision
                ? 'No se pudo marcar la orden como revisada. Intenta nuevamente o contacta a soporte.'
                : 'No se pudo autorizar la orden. Intenta nuevamente o contacta a soporte.');
        }
    }

    /** Generada (1) -> Revisada (2) de una OC (orden sin sub-órdenes o una sub-orden). */
    public function revisarOrden($idOrden, $idSub = null) {
        return $this->avanzarEstatus($idOrden, $idSub, self::ST_REVISADA);
    }

    /** Revisada (2) -> Autorizada (3) de una OC. Solo usuarios permitidos (AUTORIZAN). */
    public function autorizarOrden($idOrden, $idSub = null) {
        return $this->avanzarEstatus($idOrden, $idSub, self::ST_AUTORIZADA);
    }

    /**
     * Datos de la orden para la vista de visualizar / editar.
     */
    public function getOrden($idOrden) {
        header("Content-Type: application/json");
        try {
            $idOrden = (int) $idOrden;
            if ($idOrden <= 0) throw new InvalidArgumentException('Orden de compra no válida.');
            $idUsuario = $this->idUsuarioSesion();

            $o = $this->ejecutar(
                "SELECT o.id, o.folio, o.proveedor_razon_social, o.proveedor_rfc, o.dias_entrega, o.observaciones,
                        o.tiene_sub_ordenes, o.status, o.fecha_creacion, o.motivo_cancelacion,
                        o.fecha_revision, o.fecha_autorizacion,
                        CONCAT(er.nombre, ' ', er.apellidos) AS revisada_por,
                        CONCAT(ea.nombre, ' ', ea.apellidos) AS autorizada_por
                 FROM oc_ordenes o
                 LEFT JOIN empleados er ON er.id = o.id_usuario_revisa
                 LEFT JOIN empleados ea ON ea.id = o.id_usuario_autoriza
                 WHERE o.id = ?",
                array($idOrden)
            )->fetch(PDO::FETCH_ASSOC);
            if (!$o) throw new InvalidArgumentException('La orden de compra no existe.');

            $partidas = $this->ejecutar(
                "SELECT p.id, p.folio_oc, p.descripcion, p.cantidad, p.um, p.dias_entrega, p.precio_unitario,
                        p.id_unidad_negocio, ct.nombre AS unidad_negocio, p.id_area_centro_costo,
                        p.subtotal, p.descuento, p.tasa_iva, p.iva, p.isr, p.ieps, p.total
                 FROM oc_partidas p
                 JOIN centros_trabajo ct ON ct.id = p.id_unidad_negocio
                 WHERE p.id_orden = ?
                 ORDER BY p.id_sub_orden, p.id",
                array($idOrden)
            )->fetchAll(PDO::FETCH_ASSOC);

            $status   = (int) $o['status'];
            // Con una sub-orden ya autorizada no se edita la orden (sus importes quedan firmes)
            $editable = ($status === self::ST_GENERADA || $status === self::ST_REVISADA) && !$this->tieneSubAutorizada($idOrden);

            return array(
                'type' => 'success',
                'data' => array(
                    'orden' => array(
                        'id'                 => (int) $o['id'],
                        'folio'              => (string) $o['folio'],
                        'proveedor'          => $o['proveedor_razon_social'],
                        'proveedor_rfc'      => $o['proveedor_rfc'],
                        'dias_entrega'       => (int) $o['dias_entrega'],
                        'observaciones'      => (string) $o['observaciones'],
                        'tiene_sub_ordenes'  => (int) $o['tiene_sub_ordenes'] === 1,
                        'status'             => $status,
                        'status_texto'       => $this->textoStatus($status),
                        'fecha'              => Modelos_Fecha::formatearFecha($o['fecha_creacion']),
                        'revisada_por'       => trim((string) $o['revisada_por']),
                        'fecha_revision'     => $o['fecha_revision'] ? Modelos_Fecha::formatearFecha($o['fecha_revision']) : null,
                        'autorizada_por'     => trim((string) $o['autorizada_por']),
                        'fecha_autorizacion' => $o['fecha_autorizacion'] ? Modelos_Fecha::formatearFecha($o['fecha_autorizacion']) : null,
                        'motivo_cancelacion' => $o['motivo_cancelacion'],
                        'editable'           => $editable,
                        'puede_autorizar'    => ($status === self::ST_REVISADA && $this->puedeAutorizar($idUsuario)),
                    ),
                    'partidas' => $partidas,
                ),
            );
        } catch (\Throwable $th) {
            return $this->respuestaError($th, 'getOrden', 'No se pudo cargar la orden de compra.');
        }
    }

    // ---------- helpers de captura (misma validación que Modelos_Compras_Requisiciones) ----------

    private function leerMonto(array $post, $campo, $idPartida, $etiqueta, $decimales = 2) {
        $arr = (isset($post[$campo]) && is_array($post[$campo])) ? $post[$campo] : array();
        if (!isset($arr[$idPartida]) || !is_scalar($arr[$idPartida]) || trim((string) $arr[$idPartida]) === '') {
            return 0.0;
        }
        $v = trim((string) $arr[$idPartida]);
        if (!is_numeric($v) || (float) $v < 0 || (float) $v > 999999999999.99) {
            throw new InvalidArgumentException('Captura ' . $etiqueta . ' de la partida #' . $idPartida . ' con un número válido (mayor o igual a cero).');
        }
        return round((float) $v, $decimales);
    }

    private function leerDias(array $post, $idPartida) {
        $arr = (isset($post['dias_entrega_partida']) && is_array($post['dias_entrega_partida'])) ? $post['dias_entrega_partida'] : array();
        $dias = isset($arr[$idPartida]) ? filter_var($arr[$idPartida], FILTER_VALIDATE_INT) : false;
        if ($dias === false || $dias < 1 || $dias > 255) {
            throw new InvalidArgumentException('Los días de entrega de la partida #' . $idPartida . ' deben estar entre 1 y 255.');
        }
        return (int) $dias;
    }

    // I.V.A. = (subtotal - descuento) x tasa, en centavos enteros (misma fórmula que los JS)
    private function ivaPartida($subtotal, $descuento, $tasa) {
        $base = max(0, (int) round($subtotal * 100) - (int) round($descuento * 100));
        return ((int) round($base * $tasa / 100)) / 100;
    }

    private function leerTasaIva(array $post, $idPartida) {
        $arr = (isset($post['tasa_iva']) && is_array($post['tasa_iva'])) ? $post['tasa_iva'] : array();
        $t = isset($arr[$idPartida]) ? filter_var($arr[$idPartida], FILTER_VALIDATE_INT) : false;
        if ($t === false || !in_array($t, self::TASAS_IVA, true)) {
            throw new InvalidArgumentException('La tasa de I.V.A. de la partida #' . $idPartida . ' debe ser ' . implode('% u ', self::TASAS_IVA) . '%.');
        }
        return (int) $t;
    }

    // importe = subtotal - descuento + IVA + IEPS (+/-) ISR, en centavos enteros
    private function importePartida($subtotal, $descuento, $iva, $isr, $ieps) {
        $c = function ($v) { return (int) round($v * 100); };
        $centavos = $c($subtotal) - $c($descuento) + $c($iva) + $c($ieps) + (self::ISR_RESTA ? -$c($isr) : $c($isr));
        return $centavos / 100;
    }

    /**
     * Guarda los cambios de una orden en estatus Generada / Revisada.
     *
     * Editable: observaciones y, por partida, valor unitario, I.V.A., I.S.R., I.E.P.S.,
     * descuento, días de entrega y centro de costo (dentro de su misma unidad de negocio).
     * NO cambia: proveedor, cantidades ni unidades de negocio (cambiarlas alteraría las
     * sub-órdenes). Subtotales e importes se recalculan aquí; el navegador no manda totales.
     */
    public function guardarOrden($idOrden, $post = array()) {
        header("Content-Type: application/json");
        $transaccion = false;

        try {
            $idOrden = (int) $idOrden;
            if ($idOrden <= 0) throw new InvalidArgumentException('Orden de compra no válida.');
            $this->idUsuarioSesion();

            $observaciones = trim((string) ($post['observaciones'] ?? ''));
            if (mb_strlen($observaciones) > 65000) {
                throw new InvalidArgumentException('Las observaciones son demasiado largas.');
            }

            $this->_db->beginTransaction();
            $transaccion = true;

            // Se bloquea la orden: no se edita mientras otro usuario la autoriza
            $o = $this->ejecutar("SELECT id, status FROM oc_ordenes WHERE id = ? FOR UPDATE", array($idOrden))->fetch(PDO::FETCH_ASSOC);
            if (!$o) throw new InvalidArgumentException('La orden de compra no existe.');
            $status = (int) $o['status'];
            if ($status === self::ST_AUTORIZADA) throw new InvalidArgumentException('La orden ya fue autorizada y no se puede modificar.');
            if ($status === self::ST_CANCELADA)  throw new InvalidArgumentException('La orden está cancelada y no se puede modificar.');
            if ($this->tieneSubAutorizada($idOrden)) throw new InvalidArgumentException('La orden tiene sub-órdenes autorizadas y ya no se puede modificar.');

            $partidas = $this->ejecutar(
                "SELECT id, id_sub_orden, id_unidad_negocio, id_area_centro_costo, cantidad
                 FROM oc_partidas WHERE id_orden = ? FOR UPDATE",
                array($idOrden)
            )->fetchAll(PDO::FETCH_ASSOC);
            if (empty($partidas)) throw new InvalidArgumentException('La orden de compra no tiene partidas.');

            // Centros de costo elegidos: activos y de la misma unidad de negocio de la partida
            // (si no se cambió el actual no se revalida: pudo desactivarse después de generar la orden)
            $areasPost = (isset($post['id_area_centro_costo']) && is_array($post['id_area_centro_costo'])) ? $post['id_area_centro_costo'] : array();
            $areasNuevas = array();
            foreach ($partidas as $p) {
                $idArea = (isset($areasPost[$p['id']]) && is_scalar($areasPost[$p['id']])) ? (int) $areasPost[$p['id']] : 0;
                if ($idArea <= 0) throw new InvalidArgumentException('Selecciona el Centro de Costo de la partida #' . $p['id'] . '.');
                if ($idArea !== (int) $p['id_area_centro_costo']) $areasNuevas[] = $idArea;
            }
            $unidadDeArea = array();
            if (!empty($areasNuevas)) {
                $areasNuevas = array_values(array_unique($areasNuevas));
                $in = implode(',', array_fill(0, count($areasNuevas), '?'));
                $unidadDeArea = $this->ejecutar(
                    "SELECT id_ct_area, id_centro_trabajo FROM centro_trabajo_areas WHERE status_area = 1 AND id_ct_area IN ($in)",
                    $areasNuevas
                )->fetchAll(PDO::FETCH_KEY_PAIR);
            }

            $conceptos = array('subtotal', 'descuento', 'iva', 'isr', 'ieps', 'total');
            $porSub    = array();
            $totalOrden = array_fill_keys($conceptos, 0.0);
            $diasOrden = 1;

            foreach ($partidas as $p) {
                $id = (int) $p['id'];

                $idArea = (int) $areasPost[$id];
                if ($idArea !== (int) $p['id_area_centro_costo']
                    && (!isset($unidadDeArea[$idArea]) || (int) $unidadDeArea[$idArea] !== (int) $p['id_unidad_negocio'])) {
                    throw new InvalidArgumentException('Un Centro de Costo seleccionado no pertenece a la Unidad de Negocio de su partida.');
                }

                $precio = $this->leerMonto($post, 'precio_unitario', $id, 'el valor unitario', 4);
                if ($precio <= 0) throw new InvalidArgumentException('El valor unitario de la partida #' . $id . ' debe ser mayor a cero.');

                $desc = $this->leerMonto($post, 'descuento', $id, 'el descuento');
                $tasa = $this->leerTasaIva($post, $id);
                $isr  = $this->leerMonto($post, 'isr', $id, 'el I.S.R.');
                $ieps = $this->leerMonto($post, 'ieps', $id, 'el I.E.P.S.');
                $dias = $this->leerDias($post, $id);

                $subtotal = round((float) $p['cantidad'] * $precio, 2);
                $iva      = $this->ivaPartida($subtotal, $desc, $tasa);
                $total    = $this->importePartida($subtotal, $desc, $iva, $isr, $ieps);
                if ($total < 0) throw new InvalidArgumentException('El descuento de la partida #' . $id . ' no puede dejar el importe en negativo.');

                $this->ejecutar(
                    "UPDATE oc_partidas
                     SET dias_entrega = ?, precio_unitario = ?, id_area_centro_costo = ?,
                         subtotal = ?, descuento = ?, tasa_iva = ?, iva = ?, isr = ?, ieps = ?, total = ?
                     WHERE id = ? AND id_orden = ?",
                    array($dias, $precio, $idArea, $subtotal, $desc, $tasa, $iva, $isr, $ieps, $total, $id, $idOrden)
                );

                $linea = array('subtotal' => $subtotal, 'descuento' => $desc, 'iva' => $iva, 'isr' => $isr, 'ieps' => $ieps, 'total' => $total);
                $idSub = $p['id_sub_orden'] !== null ? (int) $p['id_sub_orden'] : 0;
                if (!isset($porSub[$idSub])) $porSub[$idSub] = array_fill_keys($conceptos, 0.0);
                foreach ($conceptos as $k) {
                    $porSub[$idSub][$k] += $linea[$k];
                    $totalOrden[$k]     += $linea[$k];
                }
                $diasOrden = max($diasOrden, $dias);
            }

            // Sub-órdenes (si la orden las tiene)
            foreach ($porSub as $idSub => $t) {
                if ($idSub === 0) continue;
                $this->ejecutar(
                    "UPDATE oc_sub_ordenes
                     SET subtotal = ?, descuento = ?, iva = ?, isr = ?, ieps = ?, total = ?
                     WHERE id = ? AND id_orden = ?",
                    array(round($t['subtotal'], 2), round($t['descuento'], 2), round($t['iva'], 2), round($t['isr'], 2),
                          round($t['ieps'], 2), round($t['total'], 2), $idSub, $idOrden)
                );
            }

            // Orden principal
            foreach ($totalOrden as $k => $v) $totalOrden[$k] = round($v, 2);
            $this->ejecutar(
                "UPDATE oc_ordenes
                 SET observaciones = ?, dias_entrega = ?, subtotal = ?, descuento = ?, iva = ?, isr = ?, ieps = ?, total = ?
                 WHERE id = ?",
                array($observaciones !== '' ? $observaciones : null, $diasOrden, $totalOrden['subtotal'], $totalOrden['descuento'],
                      $totalOrden['iva'], $totalOrden['isr'], $totalOrden['ieps'], $totalOrden['total'], $idOrden)
            );

            $this->_db->commit();
            $transaccion = false;

            return array('type' => 'success', 'msj' => 'Cambios guardados correctamente.', 'data' => array('total' => number_format($totalOrden['total'], 2, '.', '')));

        } catch (\Throwable $th) {
            if ($transaccion && $this->_db->inTransaction()) $this->_db->rollBack();
            return $this->respuestaError($th, 'guardarOrden', 'No se pudieron guardar los cambios. Intenta nuevamente o contacta a soporte.');
        }
    }

    /**
     * Total de filas por pestaña del listado (una sola consulta). Una orden con sub-órdenes
     * cuenta una fila por cada sub-orden vigente (con su propio estatus), igual que el datatable.
     * Las claves coinciden con el nombre de cada pestaña en ordenes.js.
     */
    public function getIndicadores() {
        header("Content-Type: application/json");
        try {
            $filas = $this->ejecutar(
                "SELECT t.st, COUNT(*) FROM (
                    SELECT o.status AS st FROM oc_ordenes o
                    WHERE o.tiene_sub_ordenes = 0 AND o.status IN (?, ?, ?)
                    UNION ALL
                    SELECT s.status FROM oc_sub_ordenes s
                    JOIN oc_ordenes o ON o.id = s.id_orden
                    WHERE o.tiene_sub_ordenes = 1 AND o.status <> 0 AND s.status IN (?, ?, ?)
                 ) t GROUP BY t.st",
                array(self::ST_GENERADA, self::ST_REVISADA, self::ST_AUTORIZADA, self::ST_GENERADA, self::ST_REVISADA, self::ST_AUTORIZADA)
            )->fetchAll(PDO::FETCH_KEY_PAIR);

            return array(
                'type' => 'success',
                'data' => array(
                    'generados'   => (int) ($filas[self::ST_GENERADA] ?? 0),
                    'revisadas'   => (int) ($filas[self::ST_REVISADA] ?? 0),
                    'autorizadas' => (int) ($filas[self::ST_AUTORIZADA] ?? 0),
                ),
            );
        } catch (\Throwable $th) {
            return $this->respuestaError($th, 'getIndicadores', 'No se pudieron cargar los indicadores.');
        }
    }

    // =====================================================================
    //  PDF DE LA ORDEN DE COMPRA
    // =====================================================================

    // Datos del emisor (encabezado del documento)
    const EMISOR_NOMBRE    = 'MANTENIMIENTO Y ADMINISTRACION PROFESIONAL';
    const EMISOR_RFC       = 'MAP941111HE2';
    const EMISOR_DOMICILIO = 'Manuel Doblado 2721-1001, Calete';
    const EMISOR_CIUDAD    = 'Tijuana, Baja California, México · C.P. 22044';

    // Ruta del logo (si no existe el archivo se omite sin romper el PDF)
    const LOGO_PDF = 'public/img/logo_grupo_valcas.png';

    /**
     * Obtiene TODOS los datos necesarios para imprimir una orden de compra.
     *
     * @param int|string $id  oc_ordenes.id  ó  folio de sub-orden ('20001-2').
     *                        Con un folio de sub-orden se imprime solo esa sub-orden.
     * @return array {orden, partidas, resumen, requisiciones, genera}
     * @throws InvalidArgumentException si la orden no existe
     */
    public function getDatosOrden($id) {
        $id = trim((string) $id);
        $subFolio = null;

        // Folio de sub-orden -> se resuelve la orden principal
        if (strpos($id, '-') !== false) {
            $row = $this->ejecutar("SELECT id_orden, folio FROM oc_sub_ordenes WHERE folio = ?", array($id))->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new InvalidArgumentException('La sub-orden ' . $id . ' no existe.');
            $idOrden = (int) $row['id_orden'];
            $subFolio = $row['folio'];
        } else {
            $idOrden = (int) $id;
        }
        if ($idOrden <= 0) throw new InvalidArgumentException('Orden de compra no válida.');

        // 1) Orden principal
        $o = $this->ejecutar(
            "SELECT id, folio, proveedor_razon_social, proveedor_rfc, dias_entrega, observaciones,
                    tiene_sub_ordenes, total_sub_ordenes, subtotal, descuento, iva, isr, ieps, retencion_iva, total,
                    status, id_usuario_crea, fecha_creacion, motivo_cancelacion,
                    id_usuario_revisa, fecha_revision, id_usuario_autoriza, fecha_autorizacion
             FROM oc_ordenes WHERE id = ?",
            array($idOrden)
        )->fetch(PDO::FETCH_ASSOC);
        if (!$o) throw new InvalidArgumentException('La orden de compra no existe.');

        // 2) Sub-órdenes
        $subs = $this->ejecutar(
            "SELECT id, folio, unidad_negocio, total_partidas, subtotal, descuento, iva, isr, ieps, total,
                    status, id_usuario_revisa, fecha_revision, id_usuario_autoriza, fecha_autorizacion
             FROM oc_sub_ordenes WHERE id_orden = ? ORDER BY consecutivo",
            array($idOrden)
        )->fetchAll(PDO::FETCH_ASSOC);
        $subsPorId = array();
        foreach ($subs as $s) $subsPorId[(int) $s['id']] = $s;

        $subSel = null;
        if ($subFolio !== null) {
            foreach ($subs as $s) if ($s['folio'] === $subFolio) $subSel = $s;
        }

        // 3) Partidas (solo las de la sub-orden si se pidió una)
        $params = array($idOrden);
        $filtro = '';
        if ($subSel) {
            $filtro = ' AND p.id_sub_orden = ?';
            $params[] = (int) $subSel['id'];
        }
        // oc_partidas guarda descuento y días de entrega por partida:
        //   total = subtotal - descuento + iva + ieps (+/-) isr   (ver ISR_RESTA en Modelos_Compras_Requisiciones)
        $partidas = $this->ejecutar(
            "SELECT p.id, p.id_sub_orden, p.folio_oc, p.id_requisicion, p.descripcion, p.cantidad, p.um,
                    p.precio_unitario, p.id_unidad_negocio, ct.nombre AS unidad_negocio,
                    p.id_area_centro_costo, cta.nombre_area AS centro_costo,
                    p.dias_entrega, p.subtotal, p.descuento, p.iva, p.isr, p.ieps, p.total
             FROM oc_partidas p
             JOIN centros_trabajo ct ON ct.id = p.id_unidad_negocio
             LEFT JOIN centro_trabajo_areas cta ON cta.id_ct_area = p.id_area_centro_costo
             WHERE p.id_orden = ? $filtro
             ORDER BY p.id_sub_orden, p.id",
            $params
        )->fetchAll(PDO::FETCH_ASSOC);
        if (empty($partidas)) throw new InvalidArgumentException('La orden de compra no tiene partidas.');

        // 4) Resumen por folio / unidad de negocio / centro de costo + totales por sub-orden
        $resumen = array();
        $totalSub = array();
        $idsReq = array();
        $unidades = array();
        foreach ($partidas as $p) {
            $cc = ($p['centro_costo'] !== null && $p['centro_costo'] !== '') ? $p['centro_costo'] : 'Sin centro de costo';
            $k = $p['folio_oc'] . '|' . $p['id_unidad_negocio'] . '|' . $p['id_area_centro_costo'];
            if (!isset($resumen[$k])) {
                $resumen[$k] = array('folio' => $p['folio_oc'], 'unidad_negocio' => $p['unidad_negocio'], 'centro_costo' => $cc, 'partidas' => 0, 'monto' => 0.0);
            }
            $resumen[$k]['partidas']++;
            $resumen[$k]['monto'] += (float) $p['total'];

            $ids = (int) $p['id_sub_orden'];
            $totalSub[$ids] = ($totalSub[$ids] ?? 0.0) + (float) $p['total'];

            $idsReq[(int) $p['id_requisicion']] = true;
            $unidades[$p['unidad_negocio']] = true;
        }

        // 5) Requisiciones de origen (REQ, departamento, solicitante, fecha)
        $requisiciones = array();
        if (!empty($idsReq)) {
            $ids = array_keys($idsReq);
            $in = implode(',', array_fill(0, count($ids), '?'));
            $requisiciones = $this->ejecutar(
                "SELECT r.id, d.nombre AS departamento, CONCAT(e.nombre, ' ', e.apellidos) AS solicitante,
                        MIN(rp.fecha_creacion) AS fecha
                 FROM requisiciones r
                 LEFT JOIN departamentos d ON d.id = r.id_departamento
                 LEFT JOIN empleados e ON e.id = r.id_usuario
                 LEFT JOIN requisiciones_partes rp ON rp.id_requisicion = r.id
                 WHERE r.id IN ($in)
                 GROUP BY r.id, d.nombre, e.nombre, e.apellidos
                 ORDER BY r.id",
                $ids
            )->fetchAll(PDO::FETCH_ASSOC);
        }

        // 6) Quien genera la orden
        $genera = $this->ejecutar("SELECT CONCAT(nombre, ' ', apellidos) FROM empleados WHERE id = ?", array((int) $o['id_usuario_crea']))->fetchColumn();

        // 6b) Quien revisa y autoriza: los de la sub-orden si se imprime solo una; si no, los de la orden
        $fuenteFirmas = $subSel ?: $o;
        $nombresFirma = $this->nombresEmpleados(array((int) $fuenteFirmas['id_usuario_revisa'], (int) $fuenteFirmas['id_usuario_autoriza']));
        $revisa = array(
            'nombre' => $nombresFirma[(int) $fuenteFirmas['id_usuario_revisa']] ?? '',
            'fecha'  => $fuenteFirmas['fecha_revision'],
        );
        $autoriza = array(
            'nombre' => $nombresFirma[(int) $fuenteFirmas['id_usuario_autoriza']] ?? '',
            'fecha'  => $fuenteFirmas['fecha_autorizacion'],
        );

        // Importes: los de la sub-orden si se imprime solo una
        if ($subSel) {
            $imp = array(
                'subtotal' => $subSel['subtotal'], 'descuento' => $subSel['descuento'], 'iva' => $subSel['iva'], 'isr' => $subSel['isr'],
                'ieps' => $subSel['ieps'], 'retencion_iva' => 0, 'total' => $subSel['total'],
            );
            $folioDoc = $subSel['folio'];
        } else {
            $imp = array(
                'subtotal' => $o['subtotal'], 'descuento' => $o['descuento'], 'iva' => $o['iva'], 'isr' => $o['isr'],
                'ieps' => $o['ieps'], 'retencion_iva' => $o['retencion_iva'], 'total' => $o['total'],
            );
            $folioDoc = (string) $o['folio'];
        }

        return array(
            'orden' => array_merge(array(
                'id' => (int) $o['id'],
                'folio' => (string) $o['folio'],
                'folio_documento' => $folioDoc,
                'es_sub_orden' => (bool) $subSel,
                'proveedor' => $o['proveedor_razon_social'],
                'proveedor_rfc' => $o['proveedor_rfc'],
                'dias_entrega' => (int) $o['dias_entrega'],
                'observaciones' => $o['observaciones'],
                'cancelada' => ((int) $o['status'] === self::ST_CANCELADA),
                'motivo_cancelacion' => $o['motivo_cancelacion'],
                'fecha_creacion' => $o['fecha_creacion'],
                'agrupar_sub_ordenes' => (!$subSel && (int) $o['tiene_sub_ordenes'] === 1),
                'unidades_negocio' => array_keys($unidades),
            ), array_map('floatval', $imp)),
            'sub_ordenes' => $subsPorId,
            'total_por_sub' => $totalSub,
            'partidas' => $partidas,
            'resumen' => array_values($resumen),
            'requisiciones' => $requisiciones,
            'genera' => array('nombre' => $genera ?: '', 'fecha' => $o['fecha_creacion']),
            'revisa' => $revisa,
            'autoriza' => $autoriza,
        );
    }

    /**
     * Devuelve la lista de "documentos" a imprimir.
     * - Orden con más de una unidad de negocio / con sub-órdenes -> un documento por sub-orden
     *   (cada uno con su folio, partidas e importes propios).
     * - Cualquier otro caso (orden simple, o una sola sub-orden solicitada) -> un solo documento.
     */
    private function documentosPdf(array $datos) {
        $o = $datos['orden'];

        $dividir = !$o['es_sub_orden']
            && !empty($datos['sub_ordenes'])
            && ($o['agrupar_sub_ordenes'] || count($o['unidades_negocio']) > 1);

        if (!$dividir) return array($datos);

        $docs = array();
        foreach ($datos['sub_ordenes'] as $sub) {   // ya viene ordenado por consecutivo
            try {
                $docs[] = $this->getDatosOrden($sub['folio']);
            } catch (InvalidArgumentException $e) {
                // sub-orden sin partidas: no genera hoja
            }
        }
        return !empty($docs) ? $docs : array($datos);
    }

    /**
     * Genera el PDF (vertical, tamaño carta) de una orden de compra.
     * Uso: pdf(12) -> orden completa | pdf('20001-2') -> solo la sub-orden 20001-2
     * Si la orden tiene sub-órdenes / varias unidades de negocio, cada una sale en su propia hoja
     * con su folio (encabezado, pie de página y numeración de página independientes).
     */
    public function pdf($id){

        require_once(APP . 'plugins/mpdf/mpdf.php');

        try {
            $datos = $this->getDatosOrden($id);
            $o = $datos['orden'];
            $documentos = $this->documentosPdf($datos);

            $pdf = new mPDF('utf-8', 'letter');
            $pdf->SetFont('montserrat');
            $pdf->SetTitle('Orden de Compra - ' . $o['folio_documento']);

            if ($o['cancelada']) {
                $pdf->SetWatermarkText('CANCELADA');
                $pdf->showWatermarkText = true;
            }

            $pdf->WriteHTML($this->pdfEstilos(), 1);

            foreach ($documentos as $doc) {
                $od = $doc['orden'];

                // El encabezado se define ANTES de AddPage (aplica a las páginas nuevas)
                $pdf->SetHTMLHeader($this->pdfEncabezado($doc));

                // Cada documento inicia en hoja nueva y reinicia la numeración de página
                $pdf->AddPageByArray([
                    'margin-left'   => 12,
                    'margin-right'  => 12,
                    'margin-top'    => 52,   // alto reservado al encabezado (logo + folio + título)
                    'margin-header' => 12,
                    'margin-bottom' => 18,
                    'margin-footer' => 6,
                    'resetpagenum'  => 1,
                ]);

                // El pie se define DESPUÉS de AddPage: mPDF lo imprime al cerrar la página,
                // así la hoja anterior conserva su propio folio.
                $pdf->SetHTMLFooter($this->pdfPie($od['folio_documento']));

                $pdf->WriteHTML($this->pdfHtml($doc), 2);
            }

            $pdf->Output('OrdenDeCompra_' . $o['folio_documento'] . '.pdf', 'I');
        } catch (\Throwable $th) {
            if (!($th instanceof InvalidArgumentException)) {
                error_log('[Compras_Ordenes::pdf] ' . $th->getMessage());
            }
            if (!headers_sent()) {
                header('Content-Type: text/plain; charset=utf-8');
                http_response_code($th instanceof InvalidArgumentException ? 404 : 500);
            }
            echo ($th instanceof InvalidArgumentException) ? $th->getMessage() : 'No se pudo generar el PDF de la orden de compra.';
        }
    }

    // ---------------------------------------------------------------------
    //  Helpers de presentación
    // ---------------------------------------------------------------------

    private function h($s) {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }

    private function dinero($n) {
        return '$' . number_format((float) $n, 2, '.', ',');
    }

    /** 21/Sep/2026  (con $hora: 21/09/2026 - 03:09 PM) */
    private function fechaPdf($fecha, $hora = false) {
        if (empty($fecha) || strpos((string) $fecha, '0000-00-00') === 0) return '';
        $t = strtotime($fecha);
        if (!$t) return '';
        if ($hora) return date('d/m/Y - h:i A', $t);
        $meses = array('', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic');
        return date('d', $t) . '/' . $meses[(int) date('n', $t)] . '/' . date('Y', $t);
    }

    /** 21-SEP-26 (formato de las observaciones) */
    private function fechaObs($fecha) {
        if (empty($fecha) || strpos((string) $fecha, '0000-00-00') === 0) return '';
        $t = strtotime($fecha);
        if (!$t) return '';
        $meses = array('', 'ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC');
        return date('d', $t) . '-' . $meses[(int) date('n', $t)] . '-' . date('y', $t);
    }

    /** 18786.00 -> DIECIOCHO MIL SETECIENTOS OCHENTA Y SEIS PESOS 00/100 M.N. */
    private function importeConLetra($monto) {
        $monto = round((float) $monto, 2);
        $entero = (int) floor($monto);
        $cent = (int) round(($monto - $entero) * 100);
        if ($cent === 100) { $entero++; $cent = 0; }

        $txt = ($entero === 0) ? 'CERO' : $this->numeroALetras($entero);
        if ($entero >= 1000000 && $entero % 1000000 === 0) $txt .= ' DE';
        $moneda = ($entero === 1) ? 'PESO' : 'PESOS';

        return $txt . ' ' . $moneda . ' ' . sprintf('%02d', $cent) . '/100 M.N.';
    }

    private function numeroALetras($n) {
        $millones = intdiv($n, 1000000);
        $resto    = $n % 1000000;
        $miles    = intdiv($resto, 1000);
        $cientos  = $resto % 1000;

        $partes = array();
        if ($millones > 0) $partes[] = ($millones === 1) ? 'UN MILLÓN' : $this->menorMil($millones) . ' MILLONES';
        if ($miles > 0)    $partes[] = ($miles === 1) ? 'MIL' : $this->menorMil($miles) . ' MIL';
        if ($cientos > 0)  $partes[] = $this->menorMil($cientos);
        return implode(' ', $partes);
    }

    private function menorMil($n) {
        $u = array('', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE', 'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE',
                   'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE', 'VEINTE', 'VEINTIÚN', 'VEINTIDÓS', 'VEINTITRÉS',
                   'VEINTICUATRO', 'VEINTICINCO', 'VEINTISÉIS', 'VEINTISIETE', 'VEINTIOCHO', 'VEINTINUEVE');
        $d = array(3 => 'TREINTA', 4 => 'CUARENTA', 5 => 'CINCUENTA', 6 => 'SESENTA', 7 => 'SETENTA', 8 => 'OCHENTA', 9 => 'NOVENTA');
        $c = array(1 => 'CIENTO', 2 => 'DOSCIENTOS', 3 => 'TRESCIENTOS', 4 => 'CUATROCIENTOS', 5 => 'QUINIENTOS',
                   6 => 'SEISCIENTOS', 7 => 'SETECIENTOS', 8 => 'OCHOCIENTOS', 9 => 'NOVECIENTOS');

        if ($n === 100) return 'CIEN';
        $t = '';
        if ($n >= 100) {
            $t = $c[intdiv($n, 100)];
            $n %= 100;
            if ($n > 0) $t .= ' ';
        }
        if ($n < 30) {
            $t .= $u[$n];
        } else {
            $t .= $d[intdiv($n, 10)];
            if ($n % 10 > 0) $t .= ' Y ' . $u[$n % 10];
        }
        return $t;
    }

    // ---------------------------------------------------------------------
    //  Estilos y HTML (mismo look de la Requisición Interna)
    //  Azul oscuro #00436c (etiquetas) · azul #0573ba (encabezado de tabla) · gris #dddcdd (separadores)
    // ---------------------------------------------------------------------

    private function pdfEstilos() {
        return '
            body { font-family: montserrat; font-size: 8.5pt; color: #000000; }
            table { border-collapse: collapse; }
            .lbl  { background-color: #00436c; color: #ffffff; font-weight: bold; text-align: center; padding: 4px 3px; border: 1px solid #ffffff; font-size: 8.5pt; }
            .val  { text-align: center; padding: 5px 3px; font-size: 8.5pt; }
            .th   { background-color: #0573ba; color: #ffffff; font-weight: bold; text-align: center; padding: 5px 3px; border: 1px solid #ffffff; font-size: 7.5pt; }
            .td   { padding: 5px 3px; font-size: 8pt; border-bottom: 1px solid #dddcdd; vertical-align: top; }
            .band { background-color: #dddcdd; font-weight: bold; padding: 4px 4px; font-size: 8pt; }
            .r    { text-align: right; }
            .c    { text-align: center; }
            .tit  { font-size: 15pt; font-weight: bold; color: #00436c; }
            .emi  { font-size: 13pt; font-weight: bold; }
            .small{ font-size: 7.5pt; color: #444444; }
            .tot-l{ text-align: right; padding: 3px 6px; font-size: 8.5pt; }
            .tot-v{ text-align: right; padding: 3px 4px; font-size: 8.5pt; width: 40%; }
            .firma-l { border-top: 1px solid #444444; text-align: center; padding-top: 3px; font-size: 8pt; }
        ';
    }

    /** Pie de página con folio y paginación. */
    private function pdfPie($folio) {
        $folio = $this->h($folio);
        return <<<HTML
<table width="100%" style="font-size:7pt;color:#444;border-top:1px solid #dddcdd;">
    <tr>
        <td width="70%">Orden de Compra {$folio}</td>
        <td width="30%" align="right">Página {PAGENO} de {nb}</td>
    </tr>
</table>
HTML;
    }

    /**
     * Encabezado que se repite en cada página (mPDF SetHTMLHeader).
     * Izquierda: logo · Centro: folio, fecha y usuario · Derecha: QR · Debajo: título.
     */
    private function pdfEncabezado(array $d) {
        $o = $d['orden'];

        $folio = $this->h($o['folio_documento']);
        $fecha = $this->h($this->fechaPdf($o['fecha_creacion']));
        $user  = $this->h($d['genera']['nombre']);

        $qrCode = Modelos_QR::generateQRCodeImage(STASIS.'/compras/ordenes/visualizar/'.$o['id'], 90);

        // QR con el folio (mPDF: <barcode type="QR">). Puede llevar una URL si se prefiere.

        $logo = "<img style=\"height:60px;\" src=\"".STASIS."/img/gvalcas.png\" />";

        return <<<HTML
<table style="font-size: 10px; vertical-align: center;" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td style="width: 180px; color: #444; text-align: left;">{$logo}</td>
        <td style="width: 380px; color: #444; text-align: right;">
            <br /><br /><br />
            <span style="font-size: 12px;">Folio: <strong>{$folio}</strong><br />Fecha: {$fecha}</span><br />
            <span style="font-size: 12px;">Generado Por: {$user}</span>
        </td>
        <td style="width: 90px;">{$qrCode}</td>
    </tr>
</table>
<br />
<table style="text-align: center;" cellpadding="2" cellspacing="0" width="100%">
    <tr>
        <td style="width: 100%">
            <h1 style="text-align: center; font-family: 'SanFranciscoBold';">ORDEN DE COMPRA</h1>
        </td>
    </tr>
</table>
HTML;
    }

    // ---------------------------------------------------------------------
    //  Piezas del cuerpo (cada una devuelve filas <tr> ya listas)
    // ---------------------------------------------------------------------

    private function filasPartidas(array $d) {
        $o = $d['orden'];
        $rows = '';
        $subActual = false;

        foreach ($d['partidas'] as $p) {
            $idSub = (int) $p['id_sub_orden'];

            // Banda por sub-orden cuando la orden tiene más de una unidad de negocio
            if ($o['agrupar_sub_ordenes'] && $subActual !== $idSub) {
                $subActual = $idSub;
                $folio  = $this->h($p['folio_oc']);
                $unidad = $this->h($p['unidad_negocio']);
                $total  = $this->dinero(isset($d['sub_ordenes'][$idSub]) ? $d['sub_ordenes'][$idSub]['total'] : ($d['total_por_sub'][$idSub] ?? 0));
                $rows .= <<<HTML
<tr>
    <td class="band" colspan="7">Sub-orden {$folio} &middot; {$unidad}</td>
    <td class="band r" colspan="3">Total: {$total}</td>
</tr>
HTML;
            }

            $cant   = number_format((float) $p['cantidad'], 2, '.', ',');
            $um     = $this->h($p['um']);
            $desc   = nl2br($this->h($p['descripcion']));
            $req    = (int) $p['id_requisicion'];
            $cc     = $this->h($p['centro_costo'] !== null && $p['centro_costo'] !== '' ? $p['centro_costo'] : 'Sin centro de costo');
            $unit   = $this->dinero($p['precio_unitario']);
            $import = $this->dinero($p['total']);   // importe = cant x unit. - desc. + IVA + IEPS (+/-) ISR
            $desc_m = $this->dinero($p['descuento'] ?? 0);
            $iva_m  = $this->dinero($p['iva'] ?? 0);
            $isr_m  = $this->dinero($p['isr'] ?? 0);

            $rows .= <<<HTML
<tr>
    <td class="td c">{$cant}</td>
    <td class="td c">{$um}</td>
    <td class="td">{$desc}</td>
    <td class="td c">{$req}</td>
    <td class="td">{$cc}</td>
    <td class="td r">{$unit}</td>
    <td class="td r">{$desc_m}</td>
    <td class="td r">{$iva_m}</td>
    <td class="td r">{$isr_m}</td>
    <td class="td r">{$import}</td>
</tr>
HTML;
        }
        return $rows;
    }

    private function filasResumen(array $resumen) {
        $rows = '';
        foreach ($resumen as $r) {
            $folio = $this->h($r['folio']);
            $un    = $this->h($r['unidad_negocio']);
            $cc    = $this->h($r['centro_costo']);
            $part  = (int) $r['partidas'];
            $monto = $this->dinero($r['monto']);
            $rows .= "<tr><td class=\"td c\">{$folio}</td><td class=\"td\">{$un}</td><td class=\"td\">{$cc}</td><td class=\"td c\">{$part}</td><td class=\"td r\">{$monto}</td></tr>";
        }
        return $rows;
    }

    private function filasTotales(array $o) {
        $conceptos = array(
            'Subtotal'      => $o['subtotal'],
            'Descuentos'    => $o['descuento'],
            'I.V.A.'        => $o['iva'],
            'I.E.P.S.'      => $o['ieps'],   // solo se muestra si es > 0
            'I.S.R.'        => $o['isr'],
            'Retención IVA' => $o['retencion_iva'],
        );

        $rows = '';
        foreach ($conceptos as $nombre => $monto) {
            if ($nombre === 'I.E.P.S.' && $monto <= 0) continue;
            $valor = $this->dinero($monto);
            $rows .= "<tr><td class=\"tot-l\">{$nombre}:</td><td class=\"tot-v\">{$valor}</td></tr>";
        }
        return $rows;
    }

    private function textoObservaciones(array $d) {
        $o = $d['orden'];
        $txt = '';
        if (!empty($o['observaciones'])) $txt = '' . nl2br($this->h($o['observaciones']));
        if ($o['cancelada'] && !empty($o['motivo_cancelacion'])) $txt .= '<br><b>CANCELADA:</b> ' . $this->h($o['motivo_cancelacion']);

        return $txt;
    }

    // ---------------------------------------------------------------------
    //  Cuerpo del documento
    // ---------------------------------------------------------------------

    private function pdfHtml(array $d) {
        $o = $d['orden'];

        // Valores ya escapados / formateados para la plantilla
        $fecha      = $this->h($this->fechaPdf($o['fecha_creacion']));
        $folio      = $this->h($o['folio_documento']);
        $proveedor  = $this->h($o['proveedor']);
        $rfc        = $this->h($o['proveedor_rfc']);
        $unidades   = $this->h(implode(', ', $o['unidades_negocio']));
        $dias       = (int) $o['dias_entrega'];
        $etiquetaN  = $o['es_sub_orden'] ? 'Orden principal:' : 'Productos/Servicios:';
        $valorN     = $o['es_sub_orden'] ? $this->h($o['folio']) : count($d['partidas']);

        $partidas   = $this->filasPartidas($d);
        $totales    = $this->filasTotales($o);
        $total      = $this->dinero($o['total']);
        $observ     = $this->textoObservaciones($d);
        $conLetra   = $this->h($this->importeConLetra($o['total']));
        $resumen    = $this->filasResumen($d['resumen']);

        $firmas = $this->bloqueFirmas($d);

        return <<<HTML
<table width="100%">
    <tr>
        <td class="lbl" width="18%">Fecha:</td>
        <td class="lbl" width="40%">Proveedor:</td>
        <td class="lbl" width="17%">RFC:</td>
    </tr>
    <tr>
        <td class="val">{$fecha}</td>
        <td class="val">{$proveedor}</td>
        <td class="val">{$rfc}</td>
    </tr>
</table>
<table width="100%">
    <tr>
        <td class="lbl" width="58%">Unidad de Negocio:</td>
        <td class="lbl" width="24%">{$etiquetaN}</td>
    </tr>
    <tr>
        <td class="val">{$unidades}</td>
        <td class="val">{$valorN}</td>
    </tr>
</table>
<br>

<table width="100%" repeat_header="1">
    <thead>
        <tr>
            <td class="th" width="7%">Cantidad</td>
            <td class="th" width="6%">UM</td>
            <td class="th" width="21%">Descripción</td>
            <td class="th" width="6%">Req.</td>
            <td class="th" width="12%">Centro de Costo</td>
            <td class="th" width="11%">Valor unit.</td>
            <td class="th" width="9%">Desc.</td>
            <td class="th" width="9%">IVA</td>
            <td class="th" width="9%">ISR</td>
            <td class="th" width="10%">Importe</td>
        </tr>
    </thead>
    <tbody>{$partidas}</tbody>
</table>
<br>

<table width="100%">
    <tr>
        <td width="56%" valign="top">
            <table width="100%">
                <tr><td class="" style="border-bottom: 1px solid #000;"><h3>&nbsp;Observaciones:</h3></td></tr>
                <tr><td style="padding:5px 4px;font-size:8pt;">{$observ}</td></tr>
            </table>
        </td>
        <td width="4%"></td>
        <td width="40%" valign="top">
            <table width="100%">
                {$totales}
                <tr>
                    <td class="tot-l" style="background-color:#00436c;color:#ffffff;font-weight:bold;">Total:</td>
                    <td class="tot-v" style="background-color:#00436c;color:#ffffff;font-weight:bold;">{$total}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br>

<table width="100%">
    <tr><td class="lbl" style="text-align:left;">Importe con letra</td></tr>
    <tr><td style="padding:5px 4px;font-size:8.5pt;">{$conLetra}</td></tr>
</table>
<br>
{$firmas}
HTML;
    }

    /**
     * Bloque final del PDF: quién generó, revisó y autorizó la OC (con fecha y hora).
     * Mientras falte revisión / autorización se imprime "Pendiente".
     */
    private function bloqueFirmas(array $d) {
        $celda = function ($titulo, array $p) {
            $tiene = ($p['nombre'] !== '' && !empty($p['fecha']));
            $nombre = $tiene ? $this->h($p['nombre']) : '<span style="color:#888888;">Pendiente</span>';
            $fecha  = $tiene ? $this->h($this->fechaPdf($p['fecha'], true)) : '&nbsp;';
            return '<td width="32%" valign="top">' .
                '<table width="100%">' .
                    '<tr><td class="lbl">' . $titulo . '</td></tr>' .
                    '<tr><td class="val" style="padding-top:14px;border-bottom:1px solid #000;font-size:9pt;"><strong>' . $nombre . '</strong></td></tr>' .
                    '<tr><td class="val small">' . $fecha . '</td></tr>' .
                '</table></td>';
        };

        return '<table width="100%" style="page-break-inside:avoid;"><tr>' .
            $celda('Generó', $d['genera']) .
            '<td width="2%"></td>' .
            $celda('Revisó', $d['revisa']) .
            '<td width="2%"></td>' .
            $celda('Autorizó', $d['autoriza']) .
        '</tr></table>';
    }
}