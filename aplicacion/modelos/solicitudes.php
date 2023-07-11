<?php
final class Modelos_Solicitudes extends Modelo {
	protected $_db = null;
	public $mensajes = array();

	public function iniciarDb($db) {
    	if (!$this->_db) {
			$this->_db = $db;
        }
    }

    public function nueva() {
		try {
			$datosArray = array();

			$sth = $this->_db->query("SELECT id FROM solicitudes ORDER BY id DESC LIMIT 1");
			$datosArray['folio'] = str_pad($sth->fetchColumn()+1, 5, '0', STR_PAD_LEFT);

			return $datosArray;
		} catch (Exception $e) {
			echo Modelos_Sistema::status(0, $e->getMessage());
		}
	}

	public function listadoServicios($idSolicitud = null) {
		try {
			$sth = $this->_db->query("SELECT id, tipo, nombre
				FROM servicios
				WHERE status = 1
				ORDER BY nombre ASC");
			if(!$sth->execute()) throw New Exception();

			$html = '';
			$tipo = '';

			while ($datos = $sth->fetch()) {
				$tipo = $datos['tipo'];
				if (isset($idSolicitud)) {
					if ($idSolicitud == $datos['id']) {
						$html .= '<option value="' . $datos['id'] . '" selected>' . $datos['nombre'] . '</option>';
					} else {
						$html .= '<option value="' . $datos['id'] . '" data-t="' . $datos['tipo'] . '" style="display: none;">' . $datos['nombre'] . '</option>';
					}
				} else {
					$html .= '<option value="' . $datos['id'] . '" data-t="' . $datos['tipo'] . '" style="display: none;">' . $datos['nombre'] . '</option>';
				}
			}

			$html .= '<option value="-1" data-t="A" style="display: none;">OTRO...</option>';
			$html .= '<option value="-1" data-t="S" style="display: none;">OTRO...</option>';

	  		return $html;
		} catch (Exception $e) {
			$this->mensajes[] = Modelos_Sistema::status(0, $e->getMessage());
		}
	}

	public function generar() {
		try {
			require APP . 'inc/class.upload.php';

			function reArrayFiles(&$file_post) {
				$file_ary = array();
				$file_count = count($file_post['name']);
				$file_keys = array_keys($file_post);
				for ($i=0; $i<$file_count; $i++) {
					foreach ($file_keys as $key) {
						$file_ary[$i][$key] = $file_post[$key][$i];
					}
				}
				return $file_ary;
			}

			$idPropietario = $_SESSION['login_id'];
			$tipo = $_POST['tipo'];
			$id_servicio = $_POST['id_servicio'];
			$otro = $_POST['otro'];
			$descripcion = $_POST['descripcion'];
			
			$email = $_POST['email'];
			$telefono = $_POST['telefono'];

			$arregloDatos = array($idPropietario, $tipo, $id_servicio, $otro, $descripcion);
			$sth = $this->_db->prepare("INSERT INTO solicitudes (id_propietario, fecha_creacion, tipo, id_servicio, otro, descripcion) VALUES (?, NOW(), ?, ?, ?, ?)");
			if(!$sth->execute($arregloDatos)) throw New Exception();
			$id = $this->_db->lastInsertId();

			if (!$_FILES['archivo']['size'][0] == 0) {
				$file_ary = reArrayFiles($_FILES['archivo']);

				foreach ($file_ary as $file) {
					$handle = new upload($file);
					if ($handle->uploaded) {
						$archivo = str_replace(' ', '_', $handle->file_src_name_body) . '-' . time();
						$handle->file_new_name_body   = $archivo;
						$archivoDb = $archivo . '.' . $handle->file_src_name_ext;
						$handle->process(ROOT_DIR . 'data/privada/archivos/');
						if ($handle->processed) {
							$handle->clean();
						}
					}

					$arregloDatos = array($id, $archivoDb);
					$sth = $this->_db->prepare("INSERT INTO solicitudes_archivos (id_solicitud, archivo) VALUES (?, ?)");
					if(!$sth->execute($arregloDatos)) throw New Exception();
				}
			}

			// Checar si se actualizaron datos de email y telefono
			if ($email != $_SESSION['login_email'] || $telefono != $_SESSION['login_telefono1']) {
				$sth = $this->_db->prepare("UPDATE propietarios SET email = ?, telefono1 = ? WHERE id = ?");
				$arregloDatos = array($email, $telefono, $_SESSION['login_id']);
				if(!$sth->execute($arregloDatos)) throw New Exception();
			}

			// Enviar Correo
			$correo = Modelos_Contenedor::crearModelo('Correo');
			$nombrePdf = $this->pdf($id);
			$correo->solicitudGenerada($id, $nombrePdf);

			header('Location:' . STASIS. '/movimientos/solicitudes/enviada');
		} catch (Exception $e) {
			// die;
		}
	}

	public function pdf($id) {
		// PDF
		require_once(APP . 'plugins/tcpdf/tcpdf.php');
		$pdf = new RTPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetTitle('Solicitud');
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		$pdf->SetFont('helvetica', '', 10);
		$pdf->SetPrintHeader(false);
		$pdf->SetMargins(10, 10, 10, 0);
		$pdf->AddPage();

		$sth = $this->_db->prepare("
			SELECT so.id, p.nombre AS propietario, p.lote, p.manzana, p.seccion, so.tipo, se.nombre AS servicio, so.status, so.fecha_creacion, so.fecha_autorizada, so.fecha_compromiso, CONCAT(e.nombre, ' ', e.apellidos) AS responsable, d.nombre AS departamento, e.email, e.telefono, so.descripcion, e.foto, so.fecha_atendida, so.conclusion, CONCAT(a.nombre, ' ', a.apellidos) AS administrador, so.motivo_cancelacion, so.otro, so.conclusion_archivo
			FROM solicitudes so
			LEFT JOIN servicios se
			ON se.id = so.id_servicio
			LEFT JOIN propietarios p
			ON p.id = so.id_propietario
			LEFT JOIN empleados e
			ON e.id = so.id_responsable
			LEFT JOIN departamentos d
			ON d.id = e.id_departamento
			LEFT JOIN empleados a
			ON a.id = so.id_autorizado
			WHERE so.id = ?
			ORDER BY so.id DESC
		");
		$sth->bindParam(1, $id);
		if(!$sth->execute()) throw New Exception();
		$datos = $sth->fetch();

		switch ($datos['seccion']) {
			case 'HACIENDA DEL REY (RGR)': $prefijo = 'SR'; break;
			case 'HACIENDA DEL REY': $prefijo = 'SR'; break;
			case 'LOMAS (RGR)': $prefijo = 'SL'; break;
			case 'LOMAS': $prefijo = 'SL'; break;
			case 'HACIENDA VALLE DE LOS ENCINOS (RGR)': $prefijo = 'SV'; break;
			case 'HACIENDA VALLE DE LOS ENCINOS': $prefijo = 'SV'; break;
			case 'CAÑADA DEL ENCINO': $prefijo = 'SC'; break;
			case 'VISTA DEL REY (RGR)': $prefijo = 'VR'; break;
			case 'VISTA DEL REY': $prefijo = 'VR'; break;
		}
		$lote = $prefijo . '-' . $datos['manzana'] . '-' . $datos['lote'];

		if ($datos['tipo'] == 'A') {
			$tipo = 'ATENCIÓN';
		} elseif ($datos['tipo'] == 'S') {
			$tipo = 'SERVICIO';
		}

		if (!$datos['servicio']) {
			$servicio = mb_strtoupper($datos['otro']);
		} else {
			$servicio = $datos['servicio'];
		}

		$id = $id;
		$no_solicitud = $datos['tipo'] . '-' . str_pad($datos['id'], 5, '0', STR_PAD_LEFT);
		$propietario = $datos['propietario'];
		$lote = $datos['lote'];
		$servicio = $servicio;
		$motivo_cancelacion = $datos['motivo_cancelacion'];
		$fecha_creacion = Modelos_Fecha::formatearFechaHora($datos['fecha_creacion']);

		if ($datos['fecha_autorizada']) {
			$fecha_autorizada = Modelos_Fecha::formatearFechaHora($datos['fecha_autorizada']);
		} else {
			$fecha_autorizada = '';
		}
		if ($datos['fecha_compromiso']) {
			$fecha_compromiso = $datos['fecha_compromiso'];
		} else {
			$fecha_compromiso = '';
		}
		if ($datos['fecha_atendida']) {
			$fecha_atendida = Modelos_Fecha::formatearFecha($datos['fecha_atendida']);

			$fechaAtendidaDateTime = new DateTime($datos['fecha_atendida']);
			$fechaAtendidaDateTime = $fechaAtendidaDateTime->getTimestamp();
			$fechaAtendidaFormatted = utf8_encode(ucfirst(strftime("%A %d de %B, %Y", $fechaAtendidaDateTime)));
		} else {
			$fecha_atendida = '';
		}

		$descripcion = $datos['descripcion'];
		$status = $datos['status'];
		
		$responsable = $datos['responsable'];
		$departamento = $datos['departamento'];
		$email = $datos['email'];
		$telefono = $datos['telefono'];

		if (!$datos['foto']) {
			$foto = 'img/prop.png';
		} else {
			$foto = 'data/f/' . $datos['foto'];
		}
		$conclusion = $datos['conclusion'];
		$administrador = $datos['administrador'];
		$conclusion_archivo = $datos['conclusion_archivo'];

		// Comentarios
		$sth = $this->_db->prepare("
			SELECT COUNT(s.id)
			FROM solicitudes_comentarios s
			LEFT JOIN empleados e
			ON e.id = s.id_usuario
			WHERE s.id_solicitud = ?
			ORDER BY s.fecha DESC
		");
		$sth->bindParam(1, $id);
		if(!$sth->execute()) throw New Exception();
		$cComentarios = $sth->fetchColumn();

		if ($cComentarios >= 1) {
			$htmlComentarios = '
				<br />
				<table style="border: 2px solid #DDDCDD;">
				</table>
				<br />
				<div style="text-align: center; font-size: 9px;">
					<span style="font-weight: bold; text-align: center; font-size: 10px;">BITÁCORA DE SEGUIMIENTO</span><br />
				</div>
				<table style="text-align: left; font-size: 8px;" cellpadding="0" border="0">
			';

			$sth = $this->_db->prepare("
				SELECT s.comentario, s.fecha, CONCAT(e.nombre, ' ', e.apellidos) AS usuario, s.fecha, e.foto, p.nombre AS puesto, s.archivo
				FROM solicitudes_comentarios s
				LEFT JOIN empleados e
				ON e.id = s.id_usuario
				LEFT JOIN puestos p
				ON p.id = e.id_puesto
				WHERE s.id_solicitud = ?
				ORDER BY s.fecha DESC
			");
			$sth->bindParam(1, $id);
			if(!$sth->execute()) throw New Exception();
			while ($datos = $sth->fetch()) {
				$fechaComentario = Modelos_Fecha::formatearFechaHora($datos['fecha']);
				if (!$datos['usuario']) {
					$usuario = $propietario . ' (PROPIETARIO)';
					$fotoComentario = '<img src="' . STASIS . '/img/prop.png" height="30" />';

					if ($datos['archivo']) {
						$archivo = '<br /><br />Archivo adjunto: <img src="' . STASIS . '/img/link.png" height="7" />&nbsp;<a href="https://saevalcas.mx/atencion/data/privada/archivos/' . $datos['archivo'] . '">' . $datos['archivo'] . '</a>';
					} else {
						$archivo = '';
					}
				} else {
					$fotoComentario = '<img src="' . STASIS . '/' . $foto . '" height="30" />';
					$usuario = $datos['usuario'] . ' (' . $datos['puesto'] . ')';

					if ($datos['archivo']) {
						$archivo = '<br /><br />Archivo adjunto: <img src="' . STASIS . '/img/link.png" height="7" />&nbsp;<a href="https://saevalcas.mx/data/privada/archivos/' . $datos['archivo'] . '">' . $datos['archivo'] . '</a>';
					} else {
						$archivo = '';
					}
				}

				$htmlComentarios .= '
					<tr>
						<td style="width: 9%; text-align: center;" rowspan="2">' . $fotoComentario . '</td>
						<td style="background-color: #EAEAEA; color: #000; width: 91%"><span style="line-height: 2; font-family: \'\';">' . $usuario . '</span> | ' . $fechaComentario . '</td>
					</tr>
					<tr>
						<td>
							' . $datos['comentario'] . $archivo . '
						</td>
					</tr>
					<tr>
						<td></td>
					</tr>
				';
			}

			$htmlComentarios .= '</table><br /><br />';
		}

		if (empty($motivo_cancelacion)) {
			if (!empty($responsable)) {
				// Si ya se atendio
				if (!empty($fecha_atendida)) {

					if ($conclusion_archivo) {
						$archivoConclusion = '<br /><br />Archivo adjunto: <img src="' . STASIS . '/img/link.png" height="7" />&nbsp;<a href="https://saevalcas.mx/data/privada/archivos/' . $conclusion_archivo . '">' . $conclusion_archivo . '</a>';
					} else {
						$archivoConclusion = '';
					}

					$htmlCompromiso = '
						<br />
						<table style="border: 2px solid #DDDCDD;">
						</table>
						<br />

						<div style="text-align: center; font-size: 9px;">
							<span style="font-weight: bold; text-align: center; font-size: 10px;">CONCLUSIÓN</span>
						</div>

						<div style="background-color: #DBDECE; width: 300px; text-align: center;"><br /><span style="font-family: \'\';">' . $conclusion . '</span>' . $archivoConclusion . '<br /><br /><img src="' . STASIS . '/img/guirnalda.png" height="20" /><br />Atentamente:<b><br />' . $administrador . '<br />' . $fechaAtendidaFormatted . '</b><br /></div>
					';
				// Si hay fecha compromiso
				} else {
					if (!empty($fecha_compromiso)) {
						$fechaCompromisoDateTime = new DateTime($fecha_compromiso);
						$fechaCompromisoDateTime = $fechaCompromisoDateTime->getTimestamp();
						$fechaCompromisoFormatteada = ucfirst(utf8_encode(strftime("%A %d de %B, %Y", $fechaCompromisoDateTime)));

						$htmlCompromiso = '
							<div style="background-color: #7FAA41; color: #FFF; width: 300px; text-align: center;"><br /><span style="font-family: \'\';">Fecha Estimada de Entrega:</span><br />' . $fechaCompromisoFormatteada . '<br /></div>
						';
					} else {
						$htmlCompromiso = '<div style="background-color: #C4DEED; width: 300px; text-align: center;"><br /><span style="font-family: \'\';">Está por determinarse la fecha estimada de entrega por el reponsable acorde a lo solicitado.<br />Asignaremos la fecha en un periodo máximo de 24 horas.</span><br /></div>';
					}
				}

				$htmlResponsable = '
					<br />
					<table style="border: 2px solid #DDDCDD;">
					</table>
					<br />
					
					<div style="text-align: center; font-size: 9px;">
						<span style="font-weight: bold; text-align: center; font-size: 10px;">NOMBRE DEL RESPONSABLE</span>
					</div>

					<table>
						<tr>
							<td style="width: 15%">
								<img src="' . STASIS . '/' . $foto . '" height="55" />
							</td>
							<td style="width: 85%">
								<table style="text-align: left; font-size: 8px;" cellpadding="2" cellspacing="1">
									<tr>
										<td style="background-color: #00436C; color: #FFF; width: 50%">
											<span style="text-align: center; font-family: \'\';">Nombre:</strong>
										</td>
										<td style="background-color: #00436C; color: #FFF; width: 50%">
											<span style="text-align: center; font-family: \'\';">Departamento:</strong>
										</td>
									</tr>
									<tr>
										<td style="text-align: center;">' . $responsable . '</td>
										<td style="text-align: center;">' . $departamento . '</td>
									</tr>
									<tr>
										<td style="background-color: #00436C; color: #FFF; width: 50%">
											<span style="text-align: center; font-family: \'\';">Teléfono:</strong>
										</td>
										<td style="background-color: #00436C; color: #FFF; width: 50%">
											<span style="text-align: center; font-family: \'\';">Correo:</strong>
										</td>
									</tr>
									<tr>
										<td style="text-align: center;">' . $telefono . '</td>
										<td style="text-align: center;">' . $email . '</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>

					<br />
					' . $htmlCompromiso . '
					' . $htmlComentarios . '
				';
			} else {
				$htmlResponsable = '
					<div style="background-color: #C4DEED; width: 300px; text-align: center;"><br /><span style="font-family: \'\';">La solicitud será revisada en un periodo máximo de 24 horas a partir del momento de su creación.</span><br /></div>
				';
			}
		} else {
			$htmlResponsable = '
				<div style="background-color: #FFBCC6; width: 300px; text-align: center;"><br /><span style="font-family: \'\';">Solicitud cancelada por propietario con el siguiente motivo de cancelación:<br/><br/>' . $motivo_cancelacion . '</span><br /></div>
			';
		}

		switch($status) {
			case 0: $statusHtml = '<img src="' . STASIS . '/img/s-success.png" height="7" /> Pendiente'; break;
			case 1: $statusHtml = '<img src="' . STASIS . '/img/s-primary.png" height="7" /> Autorizada'; break;
			case 2: $statusHtml = '<img src="' . STASIS . '/img/s-primary.png" height="7" /> Procesando'; break;
			case 3: $statusHtml = '<img src="' . STASIS . '/img/s-primary.png" height="7" /> Procesando'; break;
			case 4: $statusHtml = '<img src="' . STASIS . '/img/s-info.png" height="7" /> Atendida'; break;
			case -1: $statusHtml = '<img src="' . STASIS . '/img/s-danger.png" height="7" /> Cancelada'; break;
			case 9: $statusHtml = '<img src="' . STASIS . '/img/s-danger.png" height="7" /> Rechazada'; break;
		}

		$stasis = STASIS;
		TCPDF_FONTS::addTTFfont(APP . '/plugins/tcpdf/fonts/Roboto-Bold.ttf', 'TrueTypeUnicode', '', 96);
		TCPDF_FONTS::addTTFfont(APP . '/plugins/tcpdf/fonts/Roboto-Regular.ttf', 'TrueTypeUnicode', '', 96);

		$html = <<<EOF
			<table border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td style="width: 250px; color: #444;">
						<img src="$stasis/img/rtecate.png" height="64" />
					</td>
					<td style="width: 213px; text-align: right; color: #444;">
						<span style="font-size: 14px; font-family: 'Roboto Bold';">SOLICITUD DE PROPIETARIO</span><br /><br />
						<span style="font-size: 9px;">No. Solicitud: $no_solicitud<br />Fecha: $fecha_creacion</span><br />
						<span style="font-size: 9px;">$statusHtml</span>
					</td>
					<td style="width: 75px; text-align: right;">
						<img src="http://chart.apis.google.com/chart?cht=qr&chs=100x100&chl=https://saevalcas.mx/movimientos/solicitudes/visualizar/$id&chld=H|0" height="65">
					</td>
				</tr>
			</table>
			<br /><br />

			<table style="border: 2px solid #DDDCDD;">
			</table>
			<br /><br />

			<table style="text-align: left; font-size: 8px;" cellpadding="2" cellspacing="1">
				<tr>
					<td style="background-color: #00436C; color: #FFF; width: 35%">
						<span style="text-align: center; font-family: '';">Propietario:</strong>
					</td>
					<td style="background-color: #00436C; color: #FFF; width: 10%">
						<span style="text-align: center; font-family: '';">Lote:</strong>
					</td>
					<td style="background-color: #00436C; color: #FFF; width: 15%">
						<span style="text-align: center; font-family: '';">Solicitud:</strong>
					</td>
					<td style="background-color: #00436C; color: #FFF; width: 40%">
						<span style="text-align: center; font-family: '';">Servicio:</strong>
					</td>
				</tr>
				<tr>
					<td style="text-align: center;">$propietario</td>
					<td style="text-align: center;">$lote</td>
					<td style="text-align: center;">$tipo</td>
					<td style="text-align: center;">$servicio</td>
				</tr>
				<tr>
					<td style="background-color: #00436C; color: #FFF; width: 537px">
						<span style="text-align: center; font-family: \'\';">Descripción Detallada y Observaciones del Servicio:</strong>
					</td>
				</tr>
				<tr>
					<td style="text-align: center;">$descripcion</td>
				</tr>
			</table>
			<br />

			$htmlResponsable
EOF;
		$fechaPdf = date('d-m-Y');

		$pdf->writeHTML($html, true, false, true, false, '');
		$pdf->lastPage();

		$nombrePdf = "Solicitud_{$no_solicitud}_{$fechaPdf}.pdf";
		$archivo = $pdf->Output(ROOT_DIR . "/data/tmp/$nombrePdf", 'F');
		return $nombrePdf;
	}

    public function listado($tipo = null) {
		try {
			$datosVista = array();

			// Enviadas
			if ($tipo == 'enviadas') {
				$sth = $this->_db->prepare("
					SELECT so.id, so.tipo, se.nombre AS servicio, so.status, so.fecha_creacion, so.otro
					FROM solicitudes so
					LEFT JOIN servicios se
					ON se.id = so.id_servicio
					WHERE so.id_propietario = ? AND so.status = 0
					ORDER BY so.id DESC
				");
				$sth->bindParam(1, $_SESSION['login_id']);
				if(!$sth->execute()) throw New Exception();

				while ($datos = $sth->fetch()) {
					if (!$datos['servicio']) {
						$servicio = mb_strtoupper($datos['otro']);
					} else {
						$servicio = $datos['servicio'];
					}

					$arreglo = array(
						'id' => $datos['id'],
						'no_solicitud' => $datos['tipo'] . '-' . str_pad($datos['id'], 5, '0', STR_PAD_LEFT),
						'servicio' => $servicio,
						'responsable' => 'SIN ASIGNAR',
						'label' => 'label-success',
						'fecha_creacion' => Modelos_Fecha::formatearFecha($datos['fecha_creacion']),
						'hora_creacion' => Modelos_Fecha::formatearHora($datos['fecha_creacion']) . ' hrs'
					);
					$datosVista[] = $arreglo;
				}
			}

			// Autorizadas
			if ($tipo == 'autorizadas') {
				$sth = $this->_db->prepare("
					SELECT so.id, p.nombre AS propietario, p.lote, so.tipo, se.nombre AS servicio, so.status, so.fecha_creacion, so.otro, so.fecha_autorizada, CONCAT(e.nombre, ' ', e.apellidos) AS responsable
					FROM solicitudes so
					LEFT JOIN servicios se
					ON se.id = so.id_servicio
					JOIN propietarios p
					ON p.id = so.id_propietario
					JOIN empleados e
					ON e.id = so.id_responsable
					WHERE so.id_propietario = ? AND so.status = 1
					ORDER BY so.id DESC
				");
				$sth->bindParam(1, $_SESSION['login_id']);
				if(!$sth->execute()) throw New Exception();

				while ($datos = $sth->fetch()) {
					if (!$datos['servicio']) {
						$servicio = mb_strtoupper($datos['otro']);
					} else {
						$servicio = $datos['servicio'];
					}

					$arreglo = array(
						'id' => $datos['id'],
						'no_solicitud' => $datos['tipo'] . '-' . str_pad($datos['id'], 5, '0', STR_PAD_LEFT),
						'servicio' => $servicio,
						'responsable' => $datos['responsable'],
						'label' => 'label-primary',
						'fecha_creacion' => Modelos_Fecha::formatearFecha($datos['fecha_creacion']),
						'hora_creacion' => Modelos_Fecha::formatearHora($datos['fecha_creacion']) . ' hrs'
					);
					$datosVista[] = $arreglo;
				}
			}

			// Proceso
			if ($tipo == 'proceso') {
				$sth = $this->_db->prepare("
					SELECT so.id, p.nombre AS propietario, p.lote, so.tipo, se.nombre AS servicio, so.status, so.fecha_creacion, so.otro, so.fecha_autorizada, CONCAT(e.nombre, ' ', e.apellidos) AS responsable
					FROM solicitudes so
					LEFT JOIN servicios se
					ON se.id = so.id_servicio
					JOIN propietarios p
					ON p.id = so.id_propietario
					JOIN empleados e
					ON e.id = so.id_responsable
					WHERE so.id_propietario = ? AND (so.status = 2 OR so.status = 3)
					ORDER BY so.id DESC
				");
				$sth->bindParam(1, $_SESSION['login_id']);
				if(!$sth->execute()) throw New Exception();

				while ($datos = $sth->fetch()) {
					if (!$datos['servicio']) {
						$servicio = mb_strtoupper($datos['otro']);
					} else {
						$servicio = $datos['servicio'];
					}

					$arreglo = array(
						'id' => $datos['id'],
						'no_solicitud' => $datos['tipo'] . '-' . str_pad($datos['id'], 5, '0', STR_PAD_LEFT),
						'servicio' => $servicio,
						'responsable' => $datos['responsable'],
						'label' => 'label-primary',
						'fecha_creacion' => Modelos_Fecha::formatearFecha($datos['fecha_creacion']),
						'hora_creacion' => Modelos_Fecha::formatearHora($datos['fecha_creacion']) . ' hrs'
					);
					$datosVista[] = $arreglo;
				}
			}

			// Atendidas
			if ($tipo == 'atendidas') {
				$sth = $this->_db->prepare("
					SELECT so.id, p.nombre AS propietario, p.lote, so.tipo, se.nombre AS servicio, so.status, so.fecha_creacion, so.otro, so.fecha_autorizada, CONCAT(e.nombre, ' ', e.apellidos) AS responsable
					FROM solicitudes so
					LEFT JOIN servicios se
					ON se.id = so.id_servicio
					JOIN propietarios p
					ON p.id = so.id_propietario
					JOIN empleados e
					ON e.id = so.id_responsable
					WHERE so.id_propietario = ? AND so.status = 4
					ORDER BY so.id DESC
				");
				$sth->bindParam(1, $_SESSION['login_id']);
				if(!$sth->execute()) throw New Exception();

				while ($datos = $sth->fetch()) {
					if (!$datos['servicio']) {
						$servicio = mb_strtoupper($datos['otro']);
					} else {
						$servicio = $datos['servicio'];
					}

					$arreglo = array(
						'id' => $datos['id'],
						'no_solicitud' => $datos['tipo'] . '-' . str_pad($datos['id'], 5, '0', STR_PAD_LEFT),
						'servicio' => $servicio,
						'responsable' => $datos['responsable'],
						'label' => 'label-info',
						'fecha_creacion' => Modelos_Fecha::formatearFecha($datos['fecha_creacion']),
						'hora_creacion' => Modelos_Fecha::formatearHora($datos['fecha_creacion']) . ' hrs'
					);
					$datosVista[] = $arreglo;
				}
			}

	  		return $datosVista;
		} catch (Exception $e) {
			$this->mensajes[] = Modelos_Sistema::status(0, $e->getMessage());
		}
	}
	
	public function modificar($id) {
		try {
			$datosArray = array();

			$sth = $this->_db->prepare("
				SELECT so.id, p.nombre AS propietario, p.lote, so.tipo, se.nombre AS servicio, so.status, so.fecha_creacion, so.fecha_autorizada, CONCAT(e.nombre, ' ', e.apellidos) AS responsable, so.descripcion, so.otro
				FROM solicitudes so
				LEFT JOIN servicios se
				ON se.id = so.id_servicio
				JOIN propietarios p
				ON p.id = so.id_propietario
				LEFT JOIN empleados e
				ON e.id = so.id_responsable
				WHERE so.id = ?
				ORDER BY so.id DESC
			");
			$sth->bindParam(1, $id);
			if(!$sth->execute()) throw New Exception();
			$datos = $sth->fetch();

			if ($datos['tipo'] == 'A') {
				$tipo = 'ATENCION';
			} elseif ($datos['tipo'] == 'S') {
				$tipo = 'SERVICIO';
		 	}

		 	if (!$datos['servicio']) {
				$servicio = mb_strtoupper($datos['otro']);
			} else {
				$servicio = $datos['servicio'];
			}

			$datosArray['id'] = $datos['id'];
			$datosArray['no_solicitud'] = $datos['tipo'] . '-' . str_pad($datos['id'], 5, '0', STR_PAD_LEFT);
			$datosArray['servicio'] = $servicio;
			$datosArray['tipo'] = $tipo;
			$datosArray['responsable'] = $datos['responsable'];
			$datosArray['descripcion'] = $datos['descripcion'];
			$datosArray['label'] = 'label-primary';
			$datosArray['fecha_creacion'] = Modelos_Fecha::formatearFecha($datos['fecha_creacion']);
			$datosArray['hora_creacion'] = Modelos_Fecha::formatearHora($datos['fecha_creacion']) . ' hrs';

			return $datosArray;
		} catch (Exception $e) {
			echo Modelos_Sistema::status(0, $e->getMessage());
		}
	}

	public function datosPropietario() {
		try {
			$datosArray = array();

			$sth = $this->_db->prepare("
				SELECT so.id, p.nombre AS propietario, p.lote, so.tipo, se.nombre AS servicio, so.status, so.fecha_creacion, so.fecha_autorizada, CONCAT(e.nombre, ' ', e.apellidos) AS responsable, so.descripcion
				FROM solicitudes so
				JOIN servicios se
				ON se.id = so.id_servicio
				JOIN propietarios p
				ON p.id = so.id_propietario
				LEFT JOIN empleados e
				ON e.id = so.id_responsable
				WHERE so.id = ?
				ORDER BY so.id DESC
			");
			$sth->bindParam(1, $id);
			if(!$sth->execute()) throw New Exception();
			$datos = $sth->fetch();

			if ($datos['tipo'] == 'A') {
				$tipo = 'ATENCION';
			} elseif ($datos['tipo'] == 'S') {
				$tipo = 'SERVICIO';
		 	}

			$datosArray['id'] = $datos['id'];
			$datosArray['no_solicitud'] = $datos['tipo'] . '-' . str_pad($datos['id'], 5, '0', STR_PAD_LEFT);
			$datosArray['servicio'] = $datos['servicio'];
			$datosArray['tipo'] = $tipo;
			$datosArray['responsable'] = $datos['responsable'];
			$datosArray['descripcion'] = $datos['descripcion'];
			$datosArray['label'] = 'label-primary';
			$datosArray['fecha_creacion'] = Modelos_Fecha::formatearFecha($datos['fecha_creacion']);
			$datosArray['hora_creacion'] = Modelos_Fecha::formatearHora($datos['fecha_creacion']) . ' hrs';

			return $datosArray;
		} catch (Exception $e) {
			echo Modelos_Sistema::status(0, $e->getMessage());
		}
	}

	public function generarComentario() {
		try {
			$id = $_POST['id'];
			$comentario = $_POST['comentario'];

			$sth = $this->_db->prepare("INSERT INTO solicitudes_comentarios (id_solicitud, id_usuario, comentario, fecha) VALUES (?, 0, ?, NOW())");
			$sth->bindParam(1, $id);
			$sth->bindParam(2, $comentario);
			if(!$sth->execute()) throw New Exception();
			$idComentario = $this->_db->lastInsertId();

			if (!$_FILES['archivo']['size'] == 0) {
				require APP . 'inc/class.upload.php';

				$handle = new upload($_FILES['archivo']);
				if ($handle->uploaded) {
					$archivo = str_replace(' ', '_', $handle->file_src_name_body) . '-' . time();
					$handle->file_new_name_body   = $archivo;
					$archivoDb = $archivo . '.' . $handle->file_src_name_ext;
					$handle->process(ROOT_DIR . 'data/privada/archivos/');
					if ($handle->processed) {
						$handle->clean();
					}
				}

				$arregloDatos = array($archivoDb, $idComentario);
				$sth = $this->_db->prepare("UPDATE solicitudes_comentarios SET archivo = ? WHERE id = ?");
				if(!$sth->execute($arregloDatos)) throw New Exception();
			}

	  		header('Location: ' . STASIS . '/movimientos/solicitudes/cenviado');
		} catch (Exception $e) {
			$this->mensajes[] = Modelos_Sistema::status(0, $e->getMessage());
		}
	}

	public function generarCancelacion() {
		try {
			$id = $_POST['id'];
			$motivo_cancelacion = $_POST['motivo_cancelacion'];

			$sth = $this->_db->prepare("UPDATE solicitudes SET motivo_cancelacion = ?, fecha_cancelada = NOW(), status = -1 WHERE id = ?");
			$sth->bindParam(1, $motivo_cancelacion);
			$sth->bindParam(2, $id);
			if(!$sth->execute()) throw New Exception();

	  		header('Location: ' . STASIS . '/movimientos/solicitudes/s/enviadas');
		} catch (Exception $e) {
			$this->mensajes[] = Modelos_Sistema::status(0, $e->getMessage());
		}
	}

	public function actualizarDatos() {
		try {
			$email = $_POST['email'];
			$telefono1 = $_POST['telefono1'];
			$telefono2 = $_POST['telefono2'];

			$sth = $this->_db->prepare("UPDATE propietarios SET email = ?, telefono1 = ?, telefono2 = ?, actualizado = 1 WHERE id = ?");
			$sth->bindParam(1, $email);
			$sth->bindParam(2, $telefono1);
			$sth->bindParam(3, $telefono2);
			$sth->bindParam(4, $_SESSION['login_id']);
			if(!$sth->execute()) throw New Exception();

	  		header('Location: ' . STASIS . '/movimientos/solicitudes/verificado');
		} catch (Exception $e) {
			$this->mensajes[] = Modelos_Sistema::status(0, $e->getMessage());
		}
	}

	public function visualizar($id) {
		$this->pdf($id,0,1);
	}

	public function visualizarParte($id) {
		$this->pdfParte($id,0,1);
	}

	public function visualizarRecibida($id) {
		$this->pdfRecibida($id,0,1);
	}

	public function eliminar($id) {
		try {
			$sth = $this->_db->prepare("DELETE FROM requisiciones WHERE id = ?");
			$sth->bindParam(1, $id);
			if(!$sth->execute()) throw New Exception();
	  		header('Location: ' . STASIS . '/movimientos/compras/historial/1');
		} catch (Exception $e) {
			$this->mensajes[] = Modelos_Sistema::status(0, $e->getMessage());
		}
	}

	public function autorizar($id) {
		try {
			$sth = $this->_db->prepare("UPDATE requisiciones SET status = 2 WHERE id = ?");
			$sth->bindParam(1, $id);
			if(!$sth->execute()) throw New Exception();

			$sth = $this->_db->prepare("UPDATE requisiciones_partes SET status = 1, id_autoriza = ?, fecha_autorizacion = NOW() WHERE id_requisicion = ?");
			$sth->bindParam(1, $_SESSION['login_id']);
			$sth->bindParam(2, $id);
			if(!$sth->execute()) throw New Exception();
	  		header('Location: ' . STASIS . '/movimientos/compras/historial/1');
		} catch (Exception $e) {
			$this->mensajes[] = Modelos_Sistema::status(0, $e->getMessage());
		}
	}

	public function rechazar() {
		try {
			$id = $_POST['id'];
			$motivo_rechazo = mb_strtoupper($_POST['motivo_rechazo'], 'UTF-8');
			$tipo_rechazo = $_POST['tipo_rechazo'];

			$sth = $this->_db->prepare("UPDATE requisiciones SET status = 3, fecha_rechazo = NOW(), motivo_rechazo = ?, tipo_rechazo = ?, id_usuario_rechaza = ? WHERE id = ?");
			$sth->bindParam(1, $motivo_rechazo);
			$sth->bindParam(2, $tipo_rechazo);
			$sth->bindParam(3, $_SESSION['login_id']);
			$sth->bindParam(4, $id);
			if(!$sth->execute()) throw New Exception();

	  		header('Location: ' . STASIS . '/movimientos/compras/historial/4');
		} catch (Exception $e) {
			$this->mensajes[] = Modelos_Sistema::status(0, $e->getMessage());
		}
	}

	public function procesar() {
		try {
			$id = $_POST['id'];
			$oc = strtoupper($_POST['oc']);
			$dias_entrega = $_POST['dias_entrega'];

			$sth = $this->_db->prepare("UPDATE requisiciones_partes SET status = 2, oc = ?, dias_entrega = ?, id_procesa = ?, fecha_procesa = NOW() WHERE id = ?");
			$sth->bindParam(1, $oc);
			$sth->bindParam(2, $dias_entrega);
			$sth->bindParam(3, $_SESSION['login_id']);
			$sth->bindParam(4, $id);
			if(!$sth->execute()) throw New Exception();
	  		header('Location: ' . STASIS . '/movimientos/compras/historial/2');
		} catch (Exception $e) {
			$this->mensajes[] = Modelos_Sistema::status(0, $e->getMessage());
		}
	}

	public function info() {
		try {
			$id = $_POST['id'];

			// Listado de Archivos
			$archivosArray = [];
			$sth = $this->_db->prepare("
				SELECT archivo
				FROM solicitudes_archivos
				WHERE id_solicitud = ?
			");
			$sth->bindParam(1, $id);
			if(!$sth->execute()) throw New Exception();
			while ($datos = $sth->fetch()) {
				$archivosArray[] = $datos['archivo'];
			}

			if (!empty($archivosArray)) {
				foreach($archivosArray as $archivo) {
					$archivosListado .= '<li><a target="_blank" href="' . STASIS . '/data/privada/archivos/' . $archivo . '">' . $archivo . '</a></li>';
				}

				$htmlArchivos = '
					<div class="col-md-12">
						<h4 style="color: #83AB29;">ARCHIVOS CARGADOS:</h4>
						<ul>' . $archivosListado . '</ul>
					</div>

					<div class="col-md-12">
						<div class="mb-5" style="width: 100%; height: 2px; background-color: #83AB29;"></div>
					</div>
				';
			} else {
				$htmlArchivos = '';
			}

			// Datos de solicitud
			$sth = $this->_db->prepare("
				SELECT so.id, p.nombre AS propietario, p.lote, so.tipo, se.nombre AS servicio, so.status, so.fecha_creacion, so.fecha_autorizada, CONCAT(e.nombre, ' ', e.apellidos) AS responsable, d.nombre AS departamento, e.telefono, e.email, e.foto, so.fecha_compromiso, so.fecha_atendida, so.conclusion, CONCAT(a.nombre, ' ', a.apellidos) AS administrador
				FROM solicitudes so
				LEFT JOIN servicios se
				ON se.id = so.id_servicio
				JOIN propietarios p
				ON p.id = so.id_propietario
				LEFT JOIN empleados e
				ON e.id = so.id_responsable
				LEFT JOIN departamentos d
				ON d.id = e.id_departamento
				LEFT JOIN empleados a
				ON a.id = so.id_autorizado
				WHERE so.id = ?
				ORDER BY so.id DESC
			");
			$sth->bindParam(1, $id);
			if(!$sth->execute()) throw New Exception();
			$datos = $sth->fetch();

			$idSolicitud = $datos['id'];
			$propietario = $datos['propietario'];

			if (!$datos['foto']) {
				$foto = 'https://saevalcas.mx/atencion/img/prop.png';
			} else {
				$foto = 'https://saevalcas.mx/atencion/data/f/' . $datos['foto'];
			}
			$conclusion = $datos['conclusion'];
			$administrador = $datos['administrador'];
			$fecha_atendida = $datos['fecha_atendida'];
			$fechaCreacion = Modelos_Fecha::formatearFechaHora($datos['fecha_creacion']);
			$qr = '<img src="http://chart.apis.google.com/chart?cht=qr&chs=300x300&chl=https://saevalcas.mx/movimientos/solicitudes/visualizar/' . $id . '&chld=H|0" width="50%" style="margin-bottom: 13px;"><br />';

			// Pendiente
			if (!$datos['responsable']) {
				$html = '
					<div class="col-md-12 text-center">
						<h4 style="color: #83AB29;">CÓDIGO QR:</h4>
						' . $qr . '
						<a target="_blank" href="https://saevalcas.mx/movimientos/solicitudes/visualizar/' . $id . '" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-download"></i> Descargar PDF</a>
					</div>
					<div class="col-md-12">
						<div class="my-5" style="width: 100%; height: 2px; background-color: #83AB29;"></div>
					</div>

					<div class="col-md-12">
						<h4 style="color: #83AB29;">RESPONSABLE:</h4>
						<span class="text-muted">Pendiente de asignar por parte de administración.</span>
					</div>
					<div class="col-md-12">
						<div class="my-5" style="width: 100%; height: 2px; background-color: #83AB29;"></div>
					</div>

					' . $htmlArchivos . '

					<div class="col-md-12">
						<h4 style="color: #83AB29;">BITÁCORA:</h4>
						<ul>
							<li>Solicitud enviada.<br /><span class="text-muted">' . $fechaCreacion . '<br />' . $propietario . '</span></li>
						</ul>
					</div>

					<div class="col-md-12">
						<div class="mb-5" style="width: 100%; height: 2px; background-color: #83AB29;"></div>
					</div>
					<div class="col-md-12">
						<a class="btn btn-sm btn-danger" href="' . STASIS . '/movimientos/solicitudes/cancelar/' . $id . '">
							<i class="fa fa-times"></i> Cancelar Solicitud
						</a>
					</div>
				';
			// Autorizada
			} else {
				$responsable = $datos['responsable'];
				$departamento = $datos['departamento'];
				$telefono = $datos['telefono'];
				$email = $datos['email'];
				$fechaAutorizada = Modelos_Fecha::formatearFechaHora($datos['fecha_autorizada']);

				if ($datos['fecha_compromiso']) {
					$fechaCompromiso = Modelos_Fecha::formatearFecha($datos['fecha_compromiso']);
					$htmlCompromiso = '<ul> <li>Solicitud en proceso.<br /><span class="text-muted">Fecha compromiso: ' . $fechaCompromiso . '<br />' . $responsable . '</span></li> </ul>';
				} else {
					$htmlCompromiso = '';
				}

				// Comentarios
				$sth = $this->_db->prepare("
					SELECT s.comentario, s.fecha, CONCAT(e.nombre, ' ', e.apellidos) AS usuario, s.fecha, s.archivo
					FROM solicitudes_comentarios s
					LEFT JOIN empleados e
					ON e.id = s.id_usuario
					WHERE s.id_solicitud = ?
					ORDER BY s.fecha DESC
				");
				$sth->bindParam(1, $id);
				if(!$sth->execute()) throw New Exception();
				while ($datos = $sth->fetch()) {
					$fechaComentario = Modelos_Fecha::formatearFechaHora($datos['fecha']);
					if (!$datos['usuario']) {
						$usuario = $_SESSION['login_nombre'];

						if ($datos['archivo']) {
							$archivo = '<br /><img src="' . STASIS . '/img/link.png" height="12" /> <a href="https://saevalcas.mx/atencion/data/privada/archivos/' . $datos['archivo'] . '">' . $datos['archivo'] . '</a>';
						} else {
							$archivo = '';
						}
					} else {
						$usuario = $datos['usuario'];

						if ($datos['archivo']) {
							$archivo = '<br /><img src="' . STASIS . '/img/link.png" height="12" /> <a href="https://saevalcas.mx/data/privada/archivos/' . $datos['archivo'] . '">' . $datos['archivo'] . '</a>';
						} else {
							$archivo = '';
						}
					}

					$comentarios .= '<ul> <li>Comentario:<br /><span style="color: #83AB29;">' . $datos['comentario'] . '</span>' . $archivo . '<br /><span class="text-muted">' . $fechaComentario . '<br />' . $usuario . '</span></li> </ul>';
				}

				// Si ya esta atendida
				if ($fecha_atendida) {
					$fecha_atendida = Modelos_Fecha::formatearFechaHora($fecha_atendida);

					$fechaAtendidaDateTime = new DateTime($datos['fecha_atendida']);
					$fechaAtendidaDateTime = $fechaAtendidaDateTime->getTimestamp();
					$fechaAtendidaFormatted = utf8_encode(ucfirst(strftime("%A %d de %B, %Y", $fechaAtendidaDateTime)));

					$htmlFechaAtendida = '
						<ul>
							<li>Solicitud atendida.<br /><span class="text-muted">' . $fecha_atendida . '<br />' . $administrador . '</span></li>
						</ul>
					';

					$htmlConclusion = '
						<div class="col-md-12">
							<div class="mb-5" style="width: 100%; height: 2px; background-color: #83AB29;"></div>
						</div>
						<div class="col-md-12">
							<h4 style="color: #83AB29;">CONCLUSIÓN:</h4>
							' . $conclusion . '<br /><br />
							<span class="text-muted">' . $administrador . '<br />
							' . $fechaAtendidaFormatted . '</span>
						</div>
					';

					$sthX = $this->_db->prepare("SELECT COUNT(id) AS conteo FROM evaluaciones WHERE id_solicitud = ?");
					$sthX->bindParam(1, $idSolicitud);
					if(!$sthX->execute()) throw New Exception();
					$evaluacionRealizada = $sthX->fetchColumn();

					if ($evaluacionRealizada == 1) {
						$htmlEvaluar = '<br /><br /><a href="" class="btn btn-light-info mr-2 disabled" disabled><i class="fa fa-check"></i> Evaluación Realizada</a>';
					} else {
						$htmlEvaluar = '<br /><br /><a href="' . STASIS . '/movimientos/solicitudes/evaluar/' . $id . '" class="btn btn-info mr-2"><i class="fa fa-star"></i> Evaluar Atención</a>';
					}
				} else {
					$htmlConclusion = '';
					$htmlEvaluar = '';
				}

				$html = '
					<div class="col-md-12 text-center">
						<h4 style="color: #83AB29;">CÓDIGO QR:</h4>
						' . $qr . '
						<a target="_blank" href="https://saevalcas.mx/movimientos/solicitudes/visualizar/' . $id . '" class="btn btn-primary mr-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-download"></i> Descargar PDF</a>
						' . $htmlEvaluar . '
					</div>
					<div class="col-md-12">
						<div class="my-5" style="width: 100%; height: 2px; background-color: #83AB29;"></div>
					</div>

					<div class="col-md-12">
						<h4 style="color: #83AB29;">RESPONSABLE:</h4>
					</div>
					<div class="col-md-4">
						<img src="' . $foto . '" style="width: 100%;" />
					</div>
					<div class="col-md-8" style="font-weight: bold;">
						' . $responsable . '<br />
						DPTO. ' . $departamento . '<br />
						T. ' . $telefono . '<br />
						' . $email . '
					</div>

					<div class="col-md-12">
						<div class="my-5" style="width: 100%; height: 2px; background-color: #83AB29;"></div>
					</div>

					' . $htmlArchivos . '

					<div class="col-md-12">
						<h4 style="color: #83AB29;">BITÁCORA:</h4>
						' . $htmlFechaAtendida . '
						' . $htmlCompromiso . '
						<ul>
							<li>Solicitud autorizada.<br /><span class="text-muted">' . $fechaAutorizada . '<br />' . $administrador . '</span></li>
						</ul>
						<ul>
							<li>Solicitud enviada.<br /><span class="text-muted">' . $fechaCreacion . '<br />' . $propietario . '</span></li>
						</ul>
					</div>

					' . $htmlConclusion . ' 
				';
			}

			echo $html;
		} catch (Exception $e) {
			$this->mensajes[] = Modelos_Sistema::status(0, $e->getMessage());
		}
	}

	public function infoComentarios() {
		try {
			$id = $_POST['id'];

			// Datos de solicitud
			$sth = $this->_db->prepare("
				SELECT so.id, p.nombre AS propietario, p.lote, so.tipo, se.nombre AS servicio, so.status, so.fecha_creacion, so.fecha_autorizada, CONCAT(e.nombre, ' ', e.apellidos) AS responsable, d.nombre AS departamento, e.telefono, e.email, e.foto, so.fecha_compromiso, so.fecha_atendida, so.conclusion, CONCAT(a.nombre, ' ', a.apellidos) AS administrador
				FROM solicitudes so
				LEFT JOIN servicios se
				ON se.id = so.id_servicio
				JOIN propietarios p
				ON p.id = so.id_propietario
				LEFT JOIN empleados e
				ON e.id = so.id_responsable
				LEFT JOIN departamentos d
				ON d.id = e.id_departamento
				LEFT JOIN empleados a
				ON a.id = so.id_autorizado
				WHERE so.id = ?
				ORDER BY so.id DESC
			");
			$sth->bindParam(1, $id);
			if(!$sth->execute()) throw New Exception();
			$datos = $sth->fetch();

			$propietario = $datos['propietario'];

			if (!$datos['foto']) {
				$foto = 'https://saevalcas.mx/atencion/img/prop.png';
			} else {
				$foto = 'https://saevalcas.mx/atencion/data/f/' . $datos['foto'];
			}
			$conclusion = $datos['conclusion'];
			$administrador = $datos['administrador'];
			$fecha_atendida = $datos['fecha_atendida'];
			$fechaCreacion = Modelos_Fecha::formatearFechaHora($datos['fecha_creacion']);
			$qr = '<img src="http://chart.apis.google.com/chart?cht=qr&chs=300x300&chl=https://saevalcas.mx/movimientos/solicitudes/visualizar/' . $id . '&chld=H|0" width="50%" style="margin-bottom: 13px;"><br />';

			if (!$datos['responsable']) {
				
			} else {
				$sth = $this->_db->prepare("
					SELECT s.comentario, s.fecha, CONCAT(e.nombre, ' ', e.apellidos) AS usuario, s.fecha, s.archivo
					FROM solicitudes_comentarios s
					LEFT JOIN empleados e
					ON e.id = s.id_usuario
					WHERE s.id_solicitud = ?
					ORDER BY s.fecha DESC
				");
				$sth->bindParam(1, $id);
				if(!$sth->execute()) throw New Exception();
				while ($datos = $sth->fetch()) {
					$fechaComentario = Modelos_Fecha::formatearFechaHora($datos['fecha']);
					if (!$datos['usuario']) {
						$usuario = $_SESSION['login_nombre'];

						if ($datos['archivo']) {
							$archivo = '<br /><img src="' . STASIS . '/img/link.png" height="12" /> <a href="https://saevalcas.mx/atencion/data/privada/archivos/' . $datos['archivo'] . '">' . $datos['archivo'] . '</a>';
						} else {
							$archivo = '';
						}
					} else {
						$usuario = $datos['usuario'];

						if ($datos['archivo']) {
							$archivo = '<br /><img src="' . STASIS . '/img/link.png" height="12" /> <a href="https://saevalcas.mx/data/privada/archivos/' . $datos['archivo'] . '">' . $datos['archivo'] . '</a>';
						} else {
							$archivo = '';
						}
					}

					$comentarios .= '<ul> <li>Comentario:<br /><span style="color: #83AB29;">' . $datos['comentario'] . '</span>' . $archivo . '<br /><span class="text-muted">' . $fechaComentario . '<br />' . $usuario . '</span></li> </ul>';
				}

				$html =	'
					<div class="col-md-12">
						<h4 style="color: #83AB29;">COMENTARIOS:</h4>
						<a href="' . STASIS . '/movimientos/solicitudes/comentario/' . $id . '" class="btn btn-sm btn-primary mb-2" style="background: #83AB29; border: 1px #83AB29 solid;"><i class="fa fa-edit"></i> Agregar Comentario</a>
						' . $comentarios . '
					</div>
				';

				echo $html;
			}
		} catch (Exception $e) {
			$this->mensajes[] = Modelos_Sistema::status(0, $e->getMessage());
		}
	}

	public function excel() {
		// Inicializadores
		ini_set('memory_limit', '1024M');
		set_time_limit(0);
		require_once(APP . 'inc/phpexcel/phpexcel.php');

		// Variables iniciales
		$fechaActual = new DateTime();

		// Inicializador Excel
		$i = 1;
		$objPHPExcel = new PHPExcel();
		$objPHPExcel->getProperties()->setCreator("Grupo Valcas")->setTitle("Reporte de Requisicioones")->setSubject("Reporte de Requisiciones");
		$objPHPExcel->setActiveSheetIndex(0);

		// Facturas
    	$objPHPExcel->getActiveSheet()->setTitle("Requisiciones");
		$objPHPExcel->getActiveSheet()->setCellValue('A1', 'Grupo Valcas');
		$objPHPExcel->getActiveSheet()->setCellValue('B1', 'Reporte de Requisiciones');
		$objPHPExcel->getActiveSheet()->getStyle("A1:O1")->getFont()->setSize(18);
		$objPHPExcel->getActiveSheet()->getStyle("A1:O1")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$objPHPExcel->getActiveSheet()->getStyle("A1:O1")->getFill()->getStartColor()->setARGB('256BB3');
		$objPHPExcel->getActiveSheet()->getStyle("A1:O1")->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_WHITE);
		$objPHPExcel->getActiveSheet()->getStyle("A1:O1")->getFont()->setBold(true);
    	
    	$i++;
    	$letra = 'A';

    	$objPHPExcel->getActiveSheet()->setCellValue("$letra$i", 'Folio Requisición'); $letra++;
		$objPHPExcel->getActiveSheet()->setCellValue("$letra$i", 'Solicita'); $letra++;
		$objPHPExcel->getActiveSheet()->setCellValue("$letra$i", 'Departamento'); $letra++;
		$objPHPExcel->getActiveSheet()->setCellValue("$letra$i", 'Producto'); $letra++;
		$objPHPExcel->getActiveSheet()->setCellValue("$letra$i", 'Tipo'); $letra++;
		$objPHPExcel->getActiveSheet()->setCellValue("$letra$i", 'Cantidad'); $letra++;
		$objPHPExcel->getActiveSheet()->setCellValue("$letra$i", 'Unidad de Medida'); $letra++;
		$objPHPExcel->getActiveSheet()->setCellValue("$letra$i", 'Explicación y/o Justificación'); $letra++;
		$objPHPExcel->getActiveSheet()->setCellValue("$letra$i", 'Proveedor Sugerido / Observaciones'); $letra++;
		$objPHPExcel->getActiveSheet()->setCellValue("$letra$i", 'Autoriza'); $letra++;
		$objPHPExcel->getActiveSheet()->setCellValue("$letra$i", 'Fecha de Creación'); $letra++;
		$objPHPExcel->getActiveSheet()->setCellValue("$letra$i", 'Fecha de Autorización');

		$objPHPExcel->getActiveSheet()->getStyle("A$i:$letra$i")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$objPHPExcel->getActiveSheet()->getStyle("A$i:$letra$i")->getFill()->getStartColor()->setARGB('748F2C');
		$objPHPExcel->getActiveSheet()->getStyle("A$i:$letra$i")->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_WHITE);
		
		// Listado de facturas
		$i++;
    	$sth = $this->_db->query("
    		SELECT rp.id, rp.id_requisicion, CONCAT(e.nombre, ' ', e.apellidos) AS solicita, d.nombre AS departamento, rp.producto, rp.tipo, rp.cantidad, rp.um, rp.fecha_creacion, CONCAT(es.nombre, ' ', es.apellidos) AS autoriza, rp.fecha_autorizacion, rp.justificacion, rp.observaciones
			FROM requisiciones_partes rp
			JOIN departamentos d
			ON d.id = rp.id_departamento
			JOIN empleados e
			ON e.id = rp.id_solicita
			JOIN empleados es
			ON es.id = rp.id_autoriza
			WHERE rp.status = 1
			ORDER BY rp.id DESC
		");
    	if(!$sth->execute()) throw New Exception();
    	while ($datos = $sth->fetch()) {
			// $diasEntrega = $datos['dias_entrega'];
			// $fechaVencimiento = new DateTime($datos['fecha_creacion']);
			// $fechaVencimiento->modify("+$diasEntrega days");
			// $diasVencidos = $fechaActual->diff($fechaVencimiento);
			// $diasVencidos = (int)$diasVencidos->format("%r%a")+1;
			// if ($diasVencidos <= 0) {
			// 	$diasVencidos = 'ATRASADA';
			// }

			$letra = 'A';
			$objPHPExcel->getActiveSheet()->setCellValue("$letra$i", $datos['id_requisicion']); $letra++;
			$objPHPExcel->getActiveSheet()->setCellValue("$letra$i", $datos['solicita']); $letra++;
			$objPHPExcel->getActiveSheet()->setCellValue("$letra$i", $datos['departamento']); $letra++;
			$objPHPExcel->getActiveSheet()->setCellValue("$letra$i", $datos['producto']); $letra++;
			$objPHPExcel->getActiveSheet()->setCellValue("$letra$i", $datos['tipo']); $letra++;
			$objPHPExcel->getActiveSheet()->setCellValue("$letra$i", $datos['cantidad']); $letra++;
			$objPHPExcel->getActiveSheet()->setCellValue("$letra$i", $datos['um']); $letra++;
			$objPHPExcel->getActiveSheet()->setCellValue("$letra$i", $datos['justificacion']); $letra++;
			$objPHPExcel->getActiveSheet()->setCellValue("$letra$i", $datos['observaciones']); $letra++;
			$objPHPExcel->getActiveSheet()->setCellValue("$letra$i", $datos['autoriza']); $letra++;
			$objPHPExcel->getActiveSheet()->setCellValue("$letra$i", Modelos_Fecha::formatearFechaHora($datos['fecha_creacion'])); $letra++;
			$objPHPExcel->getActiveSheet()->setCellValue("$letra$i", Modelos_Fecha::formatearFechaHora($datos['fecha_autorizacion']));

			// $objPHPExcel->getActiveSheet()->getStyle("I$i")->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* \(#,##0.00\);_("$"* "-"??_);_(@_)');
			$i++;
    	}

    	for ($col='A';$col!='K';$col++) $objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->setAutoFilter('A2:K2');

    	// Final de Excel
		$objPHPExcel->setActiveSheetIndex(0);
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="gvalcas_reporte_requisiciones.xlsx"');
		header('Cache-Control: max-age=0');
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');
		exit;
	}

	public function verificarDatosActualizados() {
		try {
			$sth = $this->_db->prepare("SELECT actualizado FROM propietarios WHERE id = ?");
			$sth->bindParam(1, $_SESSION['login_id']);
			if(!$sth->execute()) throw New Exception();
			return $sth->fetchColumn();
		} catch (Exception $e) {
			$this->mensajes[] = Modelos_Sistema::status(0, $e->getMessage());
		}
	}

	public function generarEvaluacion() {
		try {
			$id = $_POST['id'];
			$calificacion = $_POST['calificacion'];
			$p1 = $_POST['p1'];
			$p2 = $_POST['p2'];
			$p3 = $_POST['p3'];
			$p4 = $_POST['p4'];
			$p5 = $_POST['p5'];
			$comentarios = $_POST['comentarios'];

			$sth = $this->_db->prepare("INSERT INTO evaluaciones (id_solicitud, calificacion, p1, p2, p3, p4, p5, comentarios, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
			$sth->bindParam(1, $id);
			$sth->bindParam(2, $calificacion);
			$sth->bindParam(3, $p1);
			$sth->bindParam(4, $p2);
			$sth->bindParam(5, $p3);
			$sth->bindParam(6, $p4);
			$sth->bindParam(7, $p5);
			$sth->bindParam(8, $comentarios);
			if(!$sth->execute()) throw New Exception();

	  		header('Location: ' . STASIS . '/movimientos/solicitudes/evenviado');
		} catch (Exception $e) {
			echo $e->getMessage();
			die;
		}
	}

}