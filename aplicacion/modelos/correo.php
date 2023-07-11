<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once(APP . 'plugins/PHPMailer/src/Exception.php');
require_once(APP . 'plugins/PHPMailer/src/PHPMailer.php');
require_once(APP . 'plugins/PHPMailer/src/SMTP.php');


final class Modelos_Correo extends Modelo {
	protected $_db = null;
	private $_bodySuperior;
	private $_bodyInferior;
	public $mensajes = array();

	public function iniciarDb($db) {
    	if (!$this->_db) {
			$this->_db = $db;
        }
    }
	// titulo
	// generales
	// seguimiento
	// condicionantes

	public function designMail($context, $type, $text=null){
		if($context=='html'){
			switch ($type) {
				case 'header-rt':
					return '<tr>
						<td align="center" style="padding-top: 35px; padding-bottom: 27px;">
							<img style="width: 25%;" src="https://saevalcas.mx/img/rtecate.png" alt="">
						</td>
					</tr>';
				break;
				case 'header-valcas':
					return '<tr>
						<td align="center">
							<img style="width: 70%;" src="https://saevalcas.mx/img/mail/header.png" alt="">
						</td>
					</tr>';
				break;
				case 'titulo':
					return '<p style="text-align: center; color: #000; font: 20px Century Gothic, sans-serif;">
						<span style="font-weight: 600;">'.$text.'</span>
					</p>';
				break;
				case 'subtitulo':
					return '<p style="text-align: center; color: #000;
						font: 14px Century Gothic, sans-serif;
						border-bottom: 1px solid #DCDCDC;
						padding-bottom: 20px;
						">'.$text.'</p>';
				break;
				case 'generales':
					$table.='<table>';

					foreach ($text as $value) {
						$clave_valor = explode('//', $value);
						$table.='<tr>
							<td>
								<p style="
								color: #000;
								margin-top: 15px;
								font: 13px Century Gothic, sans-serif;
								">'.$clave_valor[0].': <span style="font-weight: 600;">'.$clave_valor[1].'</span></p>
							</td>
						</tr>';
					}
					$table.='</table>';

					return $table; 
				break;
			}
		}else if($context=='style'){
			switch ($type) {
				case 'body':
					return 'style="
						background-color: #fff;
						padding: 24px;
						border-radius: 8px;
						margin-left: 7%;
						margin-right: 7%;
						margin-bottom: 32px;
						padding-left: 8%;
						padding-right: 8%;
						box-shadow: 0px 0px 5px -2px rgb(0, 0, 0, 0.2);"';
				break;
			}
		}
	}

	public function sendMailRT($cuerpo, $titulo, $destinatario, $archivoAdjunto = null){
		$mail = new PHPMailer(true);
		try {
			$mail->IsSMTP();
			$mail->isHTML(true); 
			$mail->CharSet 		= 'UTF-8';
			$mail->Mailer 		= 'smtp';
			$mail->SMTPDebug  	= 0;
			$mail->SMTPAuth   	= true;
			$mail->SMTPSecure 	= 'ssl';
			$mail->Port     	= 465;
			$mail->Host			= 'saevalcas.mx';
			$mail->Username 	= 'notificaciones@saevalcas.mx';
			$mail->Password 	= 'Provisional123.';
			$mail->SetFrom('notificaciones@saevalcas.mx', 'Rancho Tecate');
	
			$mail->addAddress($destinatario);
			$mail->Subject = $titulo;
			$mail->Body    = $this->_bodySuperior . $cuerpo . $this->_bodyInferior;
			if ($archivoAdjunto) $mail->AddAttachment(ROOT_DIR . "data/tmp/$archivoAdjunto");
			$mail->send();
			return 'SUCCESSFULLY SENT';
		} catch (phpmailerException $e) {
			return $e->errorMessage(); //Pretty error messages from PHPMailer
		} catch (Exception $e) {
			return $e->getMessage(); //Boring error messages from anything else!
		}
	}

	public function sendMail($cuerpo, $titulo, $destinatario, $archivoAdjunto = null){
		$mail = new PHPMailer(true);
		try {
			$mail->IsSMTP();
			$mail->isHTML(true); 
			$mail->CharSet 		= 'UTF-8';
			$mail->Mailer 		= 'smtp';
			$mail->SMTPDebug  	= 0;
			$mail->SMTPAuth   	= true;
			$mail->SMTPSecure 	= 'ssl';
			$mail->Port     	= 465;
			$mail->Host			= 'saevalcas.mx';
			$mail->Username 	= 'notificaciones@saevalcas.mx';
			$mail->Password 	= 'Provisional123.';
			$mail->SetFrom('notificaciones@saevalcas.mx', 'Grupo Valcas');
	
			$mail->addAddress($destinatario);
			$mail->Subject = $titulo;
			$mail->Body    = $this->_bodySuperior . $cuerpo . $this->_bodyInferior;
			if ($archivoAdjunto) $mail->AddAttachment(ROOT_DIR . "data/tmp/$archivoAdjunto");
			$mail->send();
			return 'SUCCESSFULLY SENT';
		} catch (phpmailerException $e) {
			return $e->errorMessage(); //Pretty error messages from PHPMailer
		} catch (Exception $e) {
			return $e->getMessage(); //Boring error messages from anything else!
		}
	}

    private function enviarCorreo($cuerpo, $titulo, $destinatario1, $destinatario2 = null, $archivoAdjunto = null, $propietario = null) {
		$mail = new PHPMailer;
		$mail->IsSMTP();
		$mail->isHTML(true); 
		$mail->CharSet 		= 'UTF-8';
		$mail->Mailer 		= 'smtp';
		$mail->SMTPDebug  	= 0;
		$mail->SMTPAuth   	= true;
		$mail->SMTPSecure 	= 'ssl';
		$mail->Port     	= 465;
		$mail->Host			= 'saevalcas.mx';
		$mail->Username 	= 'notificaciones@saevalcas.mx';
		$mail->Password 	= 'Provisional123.';
		$mail->SetFrom('notificaciones@saevalcas.mx', 'Grupo Valcas');

		if ($propietario == 1) {
			$sth = $this->_db->prepare("
				SELECT email
				FROM empleados
				WHERE tipo = 4 AND status = 1
			");
			if(!$sth->execute()) throw New Exception();
			while ($datos = $sth->fetch()) {
				$mail->addAddress($datos['email']);
			}
		}

		$mail->addAddress($destinatario1);

		if (!empty($destinatario2)) {
			$mail->addAddress($destinatario2);
		}

		$mail->Subject = $titulo;
		$mail->Body    = $this->_bodySuperior . $cuerpo . $this->_bodyInferior;
		if ($archivoAdjunto) $mail->AddAttachment(ROOT_DIR . "data/f/$archivoAdjunto");
		$mail->send();

		// if (!empty($redireccion)) header("Location:" . $redireccion);
	}

	private function enviarCorreoNotificacion($cuerpo, $titulo, $destinatario1, $destinatario2 = null, $archivoAdjunto = null, $propietario = null) {
		
		// $mail = new PHPMailer;
		// $mail->CharSet 		= 'UTF-8';
		// $mail->isHTML(true); 
		// $mail->Host			= 'mail.saevalcas.mx';
		// $mail->Port     	= 587;
		// $mail->Username 	= 'notificaciones@saevalcas.mx';
		// $mail->Password 	= 'Provisional123.';
		// $mail->SMTPSecure 	= 'tls';
		// $mail->From 		= 'notificaciones@saevalcas.mx';
		// $mail->FromName 	= 'Grupo Valcas';
		$mail = new PHPMailer;
		$mail->IsSMTP();
		$mail->isHTML(true); 
		$mail->CharSet 		= 'UTF-8';
		$mail->Mailer 		= 'smtp';
		$mail->SMTPDebug  	= 0;
		$mail->SMTPAuth   	= true;
		$mail->SMTPSecure 	= 'ssl';
		$mail->Port     	= 465;
		$mail->Host			= 'saevalcas.mx';
		$mail->Username 	= 'notificaciones@saevalcas.mx';
		$mail->Password 	= 'Provisional123.';
		$mail->SetFrom('notificaciones@saevalcas.mx', 'Grupo Valcas');

		if ($propietario == 1) {
			$sth = $this->_db->prepare("
				SELECT email
				FROM empleados
				WHERE tipo = 4 AND status = 1
			");
			if(!$sth->execute()) throw New Exception();
			while ($datos = $sth->fetch()) {
				$mail->addAddress($datos['email']);
			}
		}

		$mail->addAddress($destinatario1);

		if (!empty($destinatario2)) {
			$mail->addAddress($destinatario2);
		}

		$mail->Subject = $titulo;
		$mail->Body    = $this->_bodySuperior . $cuerpo . $this->_bodyInferior;
		if ($archivoAdjunto) $mail->AddAttachment(ROOT_DIR . "data/tmp/$archivoAdjunto");
		$mail->send();

		// if (!empty($redireccion)) header("Location:" . $redireccion);
	}

	private function enviarCorreoFactura($cuerpo, $titulo, $destinatario, $xmlArchivo, $pdfArchivo) {
		$mail = new PHPMailer;
		$mail->IsSMTP();
		$mail->isHTML(true); 
		$mail->CharSet 		= 'UTF-8';
		$mail->Mailer 		= 'smtp';
		$mail->SMTPDebug  	= 0;
		$mail->SMTPAuth   	= true;
		$mail->SMTPSecure 	= 'ssl';
		$mail->Port     	= 465;
		$mail->Host			= 'saevalcas.mx';
		$mail->Username 	= 'notificaciones@saevalcas.mx';
		$mail->Password 	= 'Provisional123.';
		$mail->SetFrom('notificaciones@saevalcas.mx', 'Grupo Valcas');
		$mail->addAddress($destinatario);
		$mail->Subject = $titulo;
		$mail->Body    = $cuerpo;
		$mail->AddAttachment($xmlArchivo);
		$mail->AddAttachment($pdfArchivo);
		$mail->send();
	}

	private function enviarCorreoComprobante($cuerpo, $titulo, $destinatario, $pdfArchivo) {
		$mail = new PHPMailer;
		$mail->IsSMTP();
		$mail->IsHTML(true);
		$mail->CharSet 		= 'UTF-8';
		$mail->Mailer 		= 'smtp';
		$mail->SMTPDebug  	= 0;
		$mail->SMTPAuth   	= TRUE;
		$mail->SMTPSecure 	= 'tls';
		$mail->Port       	= 587;
		$mail->Host       	= 'smtp.gmail.com';
		$mail->Username   	= 'notificaciones@cobroplan.mx';
		$mail->Password   	= '+N0tiC0br0';
		$mail->SetFrom('notificaciones@cobroplan.mx', 'Cobroplan');
		$mail->addAddress($destinatario);
		$mail->Subject = $titulo;
		$mail->Body    = $cuerpo;
		$mail->AddAttachment($pdfArchivo);
		$mail->send();
	}

	public function enviarCorreoPrueba() {
		$mail = new PHPMailer;
		$mail->IsSMTP();
		$mail->isHTML(true); 
		$mail->CharSet 		= 'UTF-8';
		$mail->Mailer 		= 'smtp';
		$mail->SMTPDebug  	= 2;
		$mail->SMTPAuth   	= true;
		$mail->SMTPSecure 	= 'ssl';
		$mail->Port     	= 465;
		$mail->Host			= 'saevalcas.mx';
		$mail->Username 	= 'notificaciones@saevalcas.mx';
		$mail->Password 	= 'Provisional123.';
		$mail->SetFrom('notificaciones@saevalcas.mx', 'Grupo Valcas');
		$mail->addAddress('albert@dualstudio.com.mx');
		$mail->addAddress('dualstudio.albert@gmail.com');

		$mail->Subject = 'Test Subject';
		$mail->Body    = 'Test Body';
		
		if(!$mail->send()) {
			echo $mail->ErrorInfo;
		} else {
			echo 1;
		}
	}

	public function __construct() {
		$this->_bodySuperior = '<html><head> <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /> <style type="text/css"> #outlook a{padding:0;} body{width:100% !important;} .ReadMsgBody{width:100%;} .ExternalClass{width:100%;} body{-webkit-text-size-adjust:none;} .ExternalClass * {line-height: 100%} body{margin:0; padding:0;} img{border:0; line-height:100% !important; outline:none; text-decoration:none;} table td{border-collapse:collapse;} #backgroundTable{height:100% !important; margin:0; padding:0; width:100% !important;} a{text-decoration:none;} </style> </head> <body>';
		$this->_bodyInferior = '<br /></body></html>';
    }

    // Requi atrasada
	public function requisicionAtrasada($id) {
		$sth = $this->_db->prepare("
			SELECT rp.id_requisicion, CONCAT(e.nombre, ' ', e.apellidos) AS solicita, d.nombre AS departamento, rp.producto, rp.tipo, rp.cantidad, rp.um, DATE(rp.fecha_creacion) AS fecha_creacion, DATE(rp.fecha_procesa) AS fecha_procesa, CONCAT(es.nombre, ' ', es.apellidos) AS autoriza, rp.fecha_autorizacion, rp.dias_entrega, rp.oc
			FROM requisiciones_partes rp
			JOIN departamentos d
			ON d.id = rp.id_departamento
			JOIN empleados e
			ON e.id = rp.id_solicita
			JOIN empleados es
			ON es.id = rp.id_autoriza
			JOIN requisiciones r
			ON r.id = rp.id_requisicion
			WHERE rp.id = ?
			LIMIT 1
		");
		$sth->bindParam(1, $id);
		if(!$sth->execute()) throw New Exception();
		$datos = $sth->fetch();

		$fechaActual = new DateTime(date('Y-m-d 00:00:00'));
		$diasEntrega = $datos['dias_entrega'];
		$fechaVencimiento = new DateTime($datos['fecha_procesa']);
		$fechaVencimiento->modify("+$diasEntrega days");
		$diasVencidos = $fechaActual->diff($fechaVencimiento);
		$diasVencidos = $diasVencidos->format("%r%a");

		$id_requisicion = $datos['id_requisicion'];
		$solicita = $datos['solicita'];
		$autoriza = $datos['autoriza'];
		$departamento = $datos['departamento'];
		$producto = $datos['producto'];
		$tipo = $datos['tipo'];
		$cantidad = $datos['cantidad'];
		$oc = $datos['oc'];
		$dias_entrega = $datos['dias_entrega'];
		$dias_vencidos = $status;
		$icono = $icono;
		$color = $color;
		$fecha = Modelos_Fecha::formatearFecha($datos['fecha_creacion']);
		$fecha_procesa = Modelos_Fecha::formatearFecha($datos['fecha_procesa']);
		$fecha_vencimiento = Modelos_Fecha::formatearFecha($fechaVencimiento->format('Y-m-d'));
		$fecha_autorizacion = Modelos_Fecha::formatearFecha($datos['fecha_autorizacion']);

		$cuerpo = <<<EOT
			<center>
				<table width="600" border="0" cellspacing="0" cellpadding="0" style="font-family: Calibri, Arial, sans-serif;">
					<tr>
						<td bgcolor="#FFF" style="padding: 0 0; text-align: center; color: #FFF;" colspan="2"><img src="https://saevalcas.mx/img/gvalcas.png" /><br /><br /></td>
					</tr>
					<tr>
						<td bgcolor="#0C73B9" style="padding: 10px 0; text-align: center; color: #FFF;" colspan="2">Requisición Atrasada</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Folio de Requisición:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$id}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Solicitado Por:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$solicita}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Autorizado Por:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$autoriza}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Departamento:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$departamento}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Producto:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$producto}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Cantidad:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$cantidad}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Fecha Autorizada:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$fecha_autorizacion}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Fecha Procesada:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$fecha_procesa}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Dias de Entrega:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$dias_entrega}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Fecha de Vencimiento:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$fecha_vencimiento}</td>
					</tr>
				</table>
			</center>
EOT;
	
		$correo = 'albert@dualstudio.com.mx';
		$titulo = 'Requisición Atrasada';

		$this->enviarCorreo($cuerpo, $titulo, $correo);
	}

	// Solicitud
	public function solicitudGenerada($id) {
		$sth = $this->_db->prepare("
			SELECT so.id, p.nombre AS propietario, p.lote, p.manzana, p.seccion, so.tipo, se.nombre AS servicio, so.status, so.fecha_creacion, so.fecha_autorizada, so.fecha_compromiso, CONCAT(e.nombre, ' ', e.apellidos) AS responsable, d.nombre AS departamento, e.email, e.telefono, so.descripcion, e.foto, so.fecha_atendida, so.conclusion, CONCAT(a.nombre, ' ', a.apellidos) AS administrador, so.motivo_cancelacion, so.otro
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
		$manzana = $datos['manzana'];
		$seccion = $datos['seccion'];
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
			$fechaAtendidaFormatted = ucfirst(strftime("%A %d de %B, %Y", $fechaAtendidaDateTime));
		} else {
			$fecha_atendida = '';
		}

		$descripcion = $datos['descripcion'];
		$status = $datos['status'];
		$responsable = $datos['responsable'];
		$departamento = $datos['departamento'];
		$email = $datos['email'];
		$telefono = $datos['telefono'];
		$conclusion = $datos['conclusion'];
		$administrador = $datos['administrador'];

		$cuerpo = <<<EOT
			<center>
				<table width="600" border="0" cellspacing="0" cellpadding="0" style="font-family: Calibri, Arial, sans-serif;">
					<tr>
						<td bgcolor="#FFF" style="padding: 0 0; text-align: center; color: #FFF;" colspan="2"><img src="https://saevalcas.mx/img/gvalcas.png" /><br /><br /></td>
					</tr>
					<tr>
						<td bgcolor="#0C73B9" style="padding: 10px 0; text-align: center; color: #FFF;" colspan="2">Solicitud Generada por Propietario</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>No. Solicitud:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$no_solicitud}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Propietario:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$propietario}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Sección:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$seccion}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Manzana:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$manzana}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Lote:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$lote}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Servicio:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$servicio}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Fecha de Creación:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$fecha_creacion}</td>
					</tr>
				</table>
			</center>
EOT;
	
		$correo = 'albert@dualstudio.com.mx';
		$titulo = 'Solicitud Generada por Propietario';

		$this->enviarCorreo($cuerpo, $titulo, $correo, '', '', 1);
	}

	// Clave adjuntada
	public function claveCatastral($id) {
		$sth = $this->_db->prepare("
			SELECT clave_catastral
			FROM propietarios 
			WHERE id = ?
			LIMIT 1
		");
		$sth->bindParam(1, $id);
		if(!$sth->execute()) throw New Exception();
		$datos = $sth->fetch();
		$clave_catastral = $datos['clave_catastral'];

		$cuerpo = <<<EOT
			<center>
				<table width="600" border="0" cellspacing="0" cellpadding="0" style="font-family: Calibri, Arial, sans-serif;">
					<tr>
						<td bgcolor="#FFF" style="padding: 0 0; text-align: center; color: #FFF;" colspan="2"><img src="https://saevalcas.mx/img/gvalcas.png" /><br /><br /></td>
					</tr>
					<tr>
						<td bgcolor="#0C73B9" style="padding: 10px 0; text-align: center; color: #FFF;" colspan="2">Estimado propietario, enviamos adjunta su clave catastral.</td>
					</tr>
				</table>
			</center>
EOT;
	
		$correo = 'albert@dualstudio.com.mx';
		$titulo = 'Envío de Clave Catastral';

		$this->enviarCorreo($cuerpo, $titulo, $correo, '', $clave_catastral);
	}

	// Clave adjuntada
	public function claveCatastralAdeudo($id) {
		$sth = $this->_db->prepare("
			SELECT adeudo
			FROM propietarios 
			WHERE id = ?
			LIMIT 1
		");
		$sth->bindParam(1, $id);
		if(!$sth->execute()) throw New Exception();
		$datos = $sth->fetch();
		$adeudo = $datos['adeudo'];

		$cuerpo = <<<EOT
			<center>
				<table width="600" border="0" cellspacing="0" cellpadding="0" style="font-family: Calibri, Arial, sans-serif;">
					<tr>
						<td bgcolor="#FFF" style="padding: 0 0; text-align: center; color: #FFF;" colspan="2"><img src="https://saevalcas.mx/img/gvalcas.png" /><br /><br /></td>
					</tr>
					<tr>
						<td bgcolor="#0C73B9" style="padding: 10px 0; text-align: center; color: #FFF;" colspan="2">Estimado propietario, te informamos que ya tenemos su clave catastral, pasar a pagar el monto de $ $adeudo, con el fin de poder hacer el envío de la clave catastral mencionada.<br /<br />Atte. Departamento de Post-Venta.</td>
					</tr>
				</table>
			</center>
EOT;
	
		$correo = 'albert@dualstudio.com.mx';
		$titulo = 'Adeudo Clave Catastral';

		$this->enviarCorreo($cuerpo, $titulo, $correo, '', $clave_catastral);
	}

	// Revision de comentario
	public function revisionInformacion() {
		$id = $_POST['id'];
		
		$sth = $this->_db->prepare("UPDATE proveedores SET status = 1, id_aprueba = 0, id_autoriza = 0, fecha_aprobacion = NULL, fecha_autorizacion = NULL WHERE id = ?");
		$sth->bindParam(1, $id);
		if(!$sth->execute()) throw New Exception();

		$sth = $this->_db->prepare("SELECT * FROM proveedores WHERE id = ?");
		$sth->bindParam(1, $id);
		if(!$sth->execute()) throw New Exception();
		$datos = $sth->fetch();
		
		$correo = $datos['email'];
		$comentario = $_POST['comentario'];

		$cuerpo = <<<EOT
			<center>
				<table width="600" border="0" cellspacing="0" cellpadding="0" style="font-family: Calibri, Arial, sans-serif;">
					<tr>
						<td bgcolor="#FFF" style="padding: 0 0; text-align: center; color: #FFF;" colspan="2"><img src="https://saevalcas.mx/img/gvalcas.png" /><br /><br /></td>
					</tr>
					<tr>
						<td bgcolor="#0C73B9" style="padding: 10px 0; text-align: center; color: #FFF;" colspan="2">$comentario</td>
					</tr>
				</table>
			</center>
EOT;
	
		$titulo = 'Revisión de Información de Proveedor';
		$this->enviarCorreo($cuerpo, $titulo, $correo, $_SESSION['login_correo']);
	}

	// Revision de comentario
	public function comentarioSolicitud() {
		$id = $_POST['id'];
		
		$sth = $this->_db->prepare("UPDATE proveedores SET status = 1, id_aprueba = 0, id_autoriza = 0, fecha_aprobacion = NULL, fecha_autorizacion = NULL WHERE id = ?");
		$sth->bindParam(1, $id);
		if(!$sth->execute()) throw New Exception();

		$sth = $this->_db->prepare("SELECT * FROM proveedores WHERE id = ?");
		$sth->bindParam(1, $id);
		if(!$sth->execute()) throw New Exception();
		$datos = $sth->fetch();
		
		$correo = $datos['email'];
		$comentario = $_POST['comentario'];

		$cuerpo = <<<EOT
			<center>
				<table width="600" border="0" cellspacing="0" cellpadding="0" style="font-family: Calibri, Arial, sans-serif;">
					<tr>
						<td bgcolor="#FFF" style="padding: 0 0; text-align: center; color: #FFF;" colspan="2"><img src="https://saevalcas.mx/img/gvalcas.png" /><br /><br /></td>
					</tr>
					<tr>
						<td bgcolor="#0C73B9" style="padding: 10px 0; text-align: center; color: #FFF;" colspan="2">$comentario</td>
					</tr>
				</table>
			</center>
EOT;
	
		$titulo = 'Revisión de Información de Proveedor';
		$this->enviarCorreo($cuerpo, $titulo, $correo, $_SESSION['login_correo']);
	}

	// Cotizacion a propietario
	public function cotizacionPropietario($id, $nombrePdf, $email) {
		$cuerpo = <<<EOT
			<center>
				<table width="600" border="0" cellspacing="0" cellpadding="0" style="font-family: Calibri, Arial, sans-serif;">
					<tr>
						<td bgcolor="#FFF" style="padding: 0 0; text-align: center; color: #FFF;" colspan="2"><img src="https://saevalcas.mx/img/gvalcas.png" /><br /><br /></td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px; text-align: justify;" colspan="2">
							Estimado propietario, se adjunta la cotización solicitada.<br /><br /> Una vez revisada, en el PDF favor de hacer click en Autorizar para continuar al pago, o en el botón de Rechazar en caso de que no esté de acuerdo con la cotización.
						</td>
					</tr>
				</table>
			</center>
EOT;
	
		$titulo = 'Envío de Cotización Solicitada';

		$this->enviarCorreoNotificacion($cuerpo, $titulo, $email, '', $nombrePdf);
	}

	// Revision de comentario
	public function interaccion($idSolicitud, $nombrePdf) {
		$sth = $this->_db->prepare("
			SELECT i.id,
			CONCAT(er.nombre, ' ', er.apellidos) AS remitente, erp.nombre AS remitente_puesto, er.email AS remitente_email,
			CONCAT(ed.nombre, ' ', ed.apellidos) AS destinatario, erd.nombre AS destinatario_puesto, ed.email AS destinatario_email,
			i.titulo, i.mensaje, i.prioridad, i.fecha_creacion, i.archivo, i.fecha_requerida, i.status, i.fecha_compromiso, i.fecha_finalizada, i.conclusion_remitente, i.conclusion_remitente_archivo, i.fecha_cierre, i.conclusion_destinatario, i.conclusion_destinatario_archivo, i.origen
			FROM interacciones i
			JOIN empleados er
			ON er.id = i.id_remitente
			LEFT JOIN puestos erp
			ON erp.id = er.id_puesto

			JOIN empleados ed
			ON ed.id = i.id_destinatario
			LEFT JOIN puestos erd
			ON erd.id = ed.id_puesto

			WHERE i.id = ?
		");
		$sth->bindParam(1, $idSolicitud);
		if(!$sth->execute()) throw New Exception();
		$datos = $sth->fetch();

		$no_solicitud = str_pad($datos['id'], 3, '0', STR_PAD_LEFT);
		$remitente = $datos['remitente'];
		$remitente_puesto = $datos['remitente_puesto'];
		$remitente_email = $datos['remitente_email'];
		$destinatario = $datos['destinatario'];
		$destinatario_puesto = $datos['destinatario_puesto'];
		$destinatario_email = $datos['destinatario_email'];

		$titulo = $datos['titulo'];
		$mensaje = $datos['mensaje'];
		$fecha_creacion = Modelos_Fecha::formatearFechaHora($datos['fecha_creacion']);
		$fecha_requerida = Modelos_Fecha::formatearFechaHora($datos['fecha_requerida']);

		switch($datos['origen']) {
			case 1: $origen = 'Queja de propietario'; break;
			case 2: $origen = 'Queja de proveedor'; break;
			case 3: $origen = 'Queja de colaborador'; break;
			case 4: $origen = 'Solicitud interna'; break;
			case 5: $origen = 'Acción correctiva'; break;
			case 6: $origen = 'Actualización de documentos'; break;
			case 7: $origen = 'Oportunidad de mejora'; break;
			case 8: $origen = 'Procedimientos'; break;
			case 9: $origen = 'Reuniones de resultados'; break;
			case 10: $origen = 'Otro'; break;
		}

		switch ($datos['prioridad']) {
			case 1: $prioridad = 'BAJA'; break;
			case 2: $prioridad = 'MEDIA'; break;
			case 3: $prioridad = 'ALTA'; break;
		}

		$cuerpo = <<<EOT
			<center>
				<table width="600" border="0" cellspacing="0" cellpadding="0" style="font-family: Calibri, Arial, sans-serif;">
					<tr>
						<td bgcolor="#FFF" style="padding: 0 0; text-align: center; color: #FFF;" colspan="2"><img src="https://saevalcas.mx/img/gvalcas.png" /><br /><br /></td>
					</tr>
					<tr>
						<td bgcolor="#0C73B9" style="padding: 10px 0; text-align: center; color: #FFF;" colspan="2">Datos de Interacción Interna Generada</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>No. Solicitud:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$no_solicitud}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Remitente:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$remitente}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Destinatario:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$destinatario}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Tema:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$titulo}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Mensaje:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$mensaje}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Origen:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$origen}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Prioridad:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$prioridad}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Fecha de Creación:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$fecha_creacion}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Fecha Requerida:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$fecha_requerida}</td>
					</tr>
				</table>
			</center>
EOT;
	
		$titulo = 'Notificación de Interacción Asignada';
		$this->enviarCorreoNotificacion($cuerpo, $titulo, $destinatario_email, '', $nombrePdf);
	}

	// Revision de comentario
	public function interaccionParticipante($idSolicitud, $nombrePdf, $correo) {
		$sth = $this->_db->prepare("
			SELECT i.id,
			CONCAT(er.nombre, ' ', er.apellidos) AS remitente, erp.nombre AS remitente_puesto, er.email AS remitente_email,
			CONCAT(ed.nombre, ' ', ed.apellidos) AS destinatario, erd.nombre AS destinatario_puesto, ed.email AS destinatario_email,
			i.titulo, i.mensaje, i.prioridad, i.fecha_creacion, i.archivo, i.fecha_requerida, i.status, i.fecha_compromiso, i.fecha_finalizada, i.conclusion_remitente, i.conclusion_remitente_archivo, i.fecha_cierre, i.conclusion_destinatario, i.conclusion_destinatario_archivo, i.origen
			FROM interacciones i
			JOIN empleados er
			ON er.id = i.id_remitente
			LEFT JOIN puestos erp
			ON erp.id = er.id_puesto

			JOIN empleados ed
			ON ed.id = i.id_destinatario
			LEFT JOIN puestos erd
			ON erd.id = ed.id_puesto

			WHERE i.id = ?
		");
		$sth->bindParam(1, $idSolicitud);
		if(!$sth->execute()) throw New Exception();
		$datos = $sth->fetch();

		$no_solicitud = str_pad($datos['id'], 3, '0', STR_PAD_LEFT);
		$remitente = $datos['remitente'];
		$remitente_puesto = $datos['remitente_puesto'];
		$remitente_email = $datos['remitente_email'];
		$destinatario = $datos['destinatario'];
		$destinatario_puesto = $datos['destinatario_puesto'];
		$destinatario_email = $datos['destinatario_email'];

		$titulo = $datos['titulo'];
		$mensaje = $datos['mensaje'];
		$fecha_creacion = Modelos_Fecha::formatearFechaHora($datos['fecha_creacion']);
		$fecha_requerida = Modelos_Fecha::formatearFechaHora($datos['fecha_requerida']);

		switch($datos['origen']) {
			case 1: $origen = 'Queja de propietario'; break;
			case 2: $origen = 'Queja de proveedor'; break;
			case 3: $origen = 'Queja de colaborador'; break;
			case 4: $origen = 'Solicitud interna'; break;
			case 5: $origen = 'Acción correctiva'; break;
			case 6: $origen = 'Actualización de documentos'; break;
			case 7: $origen = 'Oportunidad de mejora'; break;
			case 8: $origen = 'Procedimientos'; break;
			case 9: $origen = 'Reuniones de resultados'; break;
			case 10: $origen = 'Otro'; break;
		}

		switch ($datos['prioridad']) {
			case 1: $prioridad = 'BAJA'; break;
			case 2: $prioridad = 'MEDIA'; break;
			case 3: $prioridad = 'ALTA'; break;
		}

		$cuerpo = <<<EOT
			<center>
				<table width="600" border="0" cellspacing="0" cellpadding="0" style="font-family: Calibri, Arial, sans-serif;">
					<tr>
						<td bgcolor="#FFF" style="padding: 0 0; text-align: center; color: #FFF;" colspan="2"><img src="https://saevalcas.mx/img/gvalcas.png" /><br /><br /></td>
					</tr>
					<tr>
						<td bgcolor="#0C73B9" style="padding: 10px 0; text-align: center; color: #FFF;" colspan="2">Datos de Interacción Interna Generada</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>No. Solicitud:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$no_solicitud}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Remitente:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$remitente}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Destinatario:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$destinatario}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Tema:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$titulo}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Mensaje:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$mensaje}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Origen:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$origen}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Prioridad:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$prioridad}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Fecha de Creación:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$fecha_creacion}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Fecha Requerida:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$fecha_requerida}</td>
					</tr>
				</table>
			</center>
EOT;
	
		$titulo = 'Notificación de Interacción Asignada como Participante';
		$this->enviarCorreoNotificacion($cuerpo, $titulo, $correo, '', $nombrePdf);
	}

	public function solicitudComentario($id, $comentario, $nombrePdf) {
		$sth = $this->_db->prepare("
			SELECT so.id, p.nombre AS propietario, p.lote, p.manzana, p.seccion, so.tipo, se.nombre AS servicio, so.status, so.fecha_creacion, so.fecha_autorizada, so.fecha_compromiso, CONCAT(e.nombre, ' ', e.apellidos) AS responsable, d.nombre AS departamento, p.email, e.telefono, so.descripcion, e.foto, so.fecha_atendida, so.conclusion, CONCAT(a.nombre, ' ', a.apellidos) AS administrador, so.motivo_cancelacion, so.otro
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

		$no_solicitud = $datos['tipo'] . '-' . str_pad($datos['id'], 5, '0', STR_PAD_LEFT);
		$correo = $datos['email'];

		$cuerpo = <<<EOT
			<center>
				<table width="600" border="0" cellspacing="0" cellpadding="0" style="font-family: Calibri, Arial, sans-serif;">
					<tr>
						<td bgcolor="#FFF" style="padding: 0 0; text-align: center; color: #FFF;" colspan="2"><img src="https://saevalcas.mx/img/gvalcas.png" /><br /><br /></td>
					</tr>
					<tr>
						<td bgcolor="#0C73B9" style="padding: 10px 0; text-align: center; color: #FFF;" colspan="2">Comentario Agregado en Solicitud</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>No. Solicitud:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$no_solicitud}</td>
					</tr>
					<tr>
						<td bgcolor="#0C73B9" style="padding: 10px 0; text-align: center; color: #FFF;" colspan="2">$comentario</td>
					</tr>
				</table><br /><br />
				<a href="https://saevalcas.mx/atencion">Para responder este comentario, favor de hacer click aquí</a>
			</center>
EOT;
	
		$titulo = 'Comentario Agregado en Solicitud';
		$this->enviarCorreoNotificacion($cuerpo, $titulo, $correo, '', $nombrePdf);
	}

	public function interaccionComentario($id, $comentario, $correo) {
		$sth = $this->_db->prepare("SELECT i.id, i.uniqueid, i.titulo AS tema, i.mensaje AS descripcion, i.fecha_compromiso,
			CONCAT(er.nombre, ' ', er.apellidos) AS remitente, erp.nombre AS remitente_puesto, er.email AS remitente_email,
			CONCAT(ed.nombre, ' ', ed.apellidos) AS destinatario, erd.nombre AS destinatario_puesto, ed.email AS destinatario_email
			FROM interacciones i
			JOIN empleados er
			ON er.id = i.id_remitente
			LEFT JOIN puestos erp
			ON erp.id = er.id_puesto
			JOIN empleados ed
			ON ed.id = i.id_destinatario
			LEFT JOIN puestos erd
			ON erd.id = ed.id_puesto
			WHERE i.id = ?");
		$sth->bindParam(1, $id);
		if(!$sth->execute()) throw New Exception();
		$datos = $sth->fetch();

		$no_solicitud = str_pad($datos['id'], 3, '0', STR_PAD_LEFT);

		$fecha_actual = date('d/m/Y');

		if($datos['fecha_compromiso']){
			$fecha_compromiso = DateTime::createFromFormat('Y-m-d', $datos['fecha_compromiso']);
			if(is_bool($fecha_compromiso)){
				$fecha_compromiso = 'N/A';
			}else{
				$fecha_compromiso = $fecha_compromiso->format('d/m/Y');
			}
		}else{
			$fecha_compromiso = 'N/A';
		}

		$descripcion = str_replace(". ", ".<br>", $datos['descripcion']);

		$stasis = STASIS;
		$cuerpo = <<<EOT
		<center>
		<table width="700" border="0" cellspacing="0" cellpadding="0" style="font-family: Calibri, Arial, sans-serif; background-color: #EEF0F8;">
			{$this->designMail('html','header-valcas')}
			<tr>
				<td>
					<div {$this->designMail('style','body')}>
						{$this->designMail('html','titulo','INTERACCIÓN DE PROCESOS')}
						{$this->designMail('html','subtitulo','Se agregó un comentario el <span style="font-weight: 600;">'.$fecha_actual.'</span> con los siguientes datos:')}
						{$this->designMail('html','generales',
							array(
								'NO. SOLICITUD//#'.$no_solicitud,
								'TEMA//'.$datos['tema'],
								'DESCRIPCIÓN//'.$descripcion,
								'SOLICITANTE//'.$datos['remitente'],
								'FECHA COMPROMISO//'.$fecha_compromiso,
							)
						)}
						<p style="
						color: #000;
						font: 13px Century Gothic, sans-serif;
						padding-top: 35px;
						line-height: 5px;
						border-top: 1px solid #DCDCDC; font-weight: 600; text-align: center;
						">COMENTARIO DE SEGUIMIENTO</p>
						<p style="
						color: #000;
						font: 13px Century Gothic, sans-serif;
						padding-bottom: 19px;
						line-height: 23px; text-align: justify;
						"><span style="font-weight: 500; text-align: justify;">$comentario</span></p>
						
						<p style="
						color: #000;
						font: 13px Century Gothic, sans-serif;
						padding-top: 23px;
						line-height: 23px;
						border-top: 1px solid #DCDCDC;
						">Para mirar más detalles sobre el seguimiento de la interacción <span style="font-weight: 600;">#{$no_solicitud}</span>, da clic al siguiente botón:</p>
						<p style="text-align: center; margin-top: 32px;">
							<a href="{$stasis}/movimientos/procesos/visualizar/{$datos['uniqueid']}"
							style="
							">
								<div style="background-color: #B8DBFF;
								padding-top: 11px;
								padding-bottom: 11px;
								border-radius: 7px;
								border: 1px solid #0473BA;
								text-align: center;
								">
									<span style="color: #0473BA;
									font: 14px Century Gothic, sans-serif;
									font-weight: 600;
									text-align: center;">IR A LA INTERACCIÓN</span>
								</div>
							</a>
						</p>
					</div>
				</td>
			</tr>
		</table>
	</center>
EOT;
	
		$titulo = 'Comentario Agregado en Interacción - '.$id;
		$this->enviarCorreo($cuerpo, $titulo, $correo);
	}

	// Revision de comentario
	public function cotizacionPago($id) {
		$sth = $this->_db->prepare("
			SELECT c.alfanumerico, c.id, p.nombre, p.seccion, p.manzana, p.lote, c.fecha_creacion, c.vigencia, c.total, c.subtotal, c.impuesto, c.moneda, CONCAT(e.nombre, ' ', e.apellidos) AS agente, e.celular AS agente_celular, e.email AS agente_email, c.vigencia, p.telefono1, p.telefono2, p.email, c.por_impuesto, c.observaciones, c.status, co.nombre AS concepto, cp.um, cp.cantidad, cp.precio, c.openpay
			FROM cotizaciones c
			JOIN propietarios p
			ON p.id = c.id_cliente
			JOIN cotizaciones_partes cp
			ON cp.id_cotizacion = c.id
			JOIN conceptos co
			ON co.id = cp.id_concepto
			JOIN empleados e
			ON e.id = c.id_agente
			WHERE c.id = ?
		");
		$sth->bindParam(1, $id);
		if(!$sth->execute()) throw New Exception();
		$datos = $sth->fetch();

		$alfanumerico = $datos['alfanumerico'];

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
		$lote = $prefijo . '-' . str_pad($datos['manzana'], 2, '0', STR_PAD_LEFT) . '-' . str_pad($datos['lote'], 2, '0', STR_PAD_LEFT);

		if ($datos['moneda'] == 1) {
			$moneda = 'MXN';
		} elseif ($datos['moneda'] == 2) {
			$moneda = 'USD';
		}

		$conceptoConcepto = $datos['concepto'];
		$um = mb_strtoupper($datos['um']);
		$cantidad = $datos['cantidad'];
		$precio = '$ ' . number_format($datos['precio'], 2, '.', ',');
		$totalConcepto = '$ ' . number_format($datos['precio']*$datos['cantidad'], 2, '.', ',');

		$folio = $datos['id'];
		$alfanumerico = $datos['alfanumerico'];
		$fecha_creacion = Modelos_Fecha::formatearFecha($datos['fecha_creacion']);
		$fecha_vigencia = Modelos_Fecha::formatearFecha($datos['vigencia']);
		$agente = $grado . mb_strtoupper($datos['agente'], 'UTF-8');
		$agenteCorreo = $datos['agente_email'];
		$agenteCelular = $datos['agente_celular'];
		$porImpuesto = $datos['por_impuesto']*100;
		$propietario = $datos['nombre'];
		$telefono1 = $datos['telefono1'];
		$telefono2 = $datos['telefono2'];
		$email = strtolower($datos['email']);
		$rfc = $datos['rfc'];
		$vigencia = Modelos_Fecha::formatearFecha($datos['vigencia']);
		$totalLetras = strtoupper(Modelos_Caracteres::num2letras($datos['total'], $moneda));
		$idCliente = $datos['id_cliente'];
		$cliente = $datos['cliente'];
		$subtotal = number_format($datos['subtotal'], 2, '.', ',');
		$impuesto = number_format($datos['impuesto'], 2, '.', ',');
		$total = number_format($datos['total'], 2, '.', ',');
		$observaciones = $datos['observaciones'];
		$concepto = 'PAGO DE COTIZACIÓN FOLIO #' . $datos['id'];

		if (!empty($datos['openpay'])) {
			$ch = curl_init('https://api.openpay.mx/v1/m7aci0xq2pyewsqdhy9r/charges/' . $datos['openpay']);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			    "Accept: application/json",
			    "Content-Type: application/json",
			    "Authorization: Basic c2tfM2IzZGVkNGNjZjU4NGVhYjliNGRkOTUzNmI0ZGI0ZjM6"
			));
			$response = curl_exec($ch);
			$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			if(curl_errno($ch)) throw new Exception(curl_error($ch));

			$jsonReponse = json_decode($response);

			$fechaOperacion = new DateTime($jsonReponse->operation_date);
			
			$openpayId = $datos['openpay'];
			$openpayBrand = $jsonReponse->card->brand;
			$openpayCard_number = $jsonReponse->card->card_number;
			$openpayHolder_name = $jsonReponse->card->holder_name;
			$openpayBank_name = $jsonReponse->card->bank_name;
			$openpayOperation_date = $fechaOperacion->format('d/m/Y H:i:s');
		}

		$cuerpo = <<<EOT
			<center>
				<table width="600" border="0" cellspacing="0" cellpadding="0" style="font-family: Calibri, Arial, sans-serif;">
					<tr>
						<td bgcolor="#FFF" style="padding: 0 0; text-align: center; color: #FFF;" colspan="2"><img src="https://saevalcas.mx/img/rtecate.png" /><br /><br /></td>
					</tr>
					<tr>
						<td bgcolor="#0C73B9" style="padding: 10px 0; text-align: center; color: #FFF;" colspan="2">Pago Exitoso</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>No. Cotización:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$no_solicitud}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Remitente:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$remitente}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Destinatario:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$destinatario}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Tema:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$titulo}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Mensaje:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$mensaje}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Origen:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$origen}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Prioridad:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$prioridad}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Fecha de Creación:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$fecha_creacion}</td>
					</tr>
					<tr>
						<td bgcolor="#EAEAEA" style="padding: 10px;"><b>Fecha Requerida:</b></td>
						<td bgcolor="#EAEAEA" style="padding: 10px;">{$fecha_requerida}</td>
					</tr>
				</table>
			</center>
EOT;
	
		$titulo = 'Pago Exitoso';
		$this->enviarCorreo($cuerpo, $titulo, $destinatario_email, '', $nombrePdf);
	}

	// Factura
	public function factura($id) {
		$sth = $this->_db->prepare("
			SELECT * FROM facturas WHERE id = ?
		");
		$sth->bindParam(1, $id);
		if(!$sth->execute()) throw New Exception();
		$datos = $sth->fetch();
		$destinatario = $datos['email'];

		// Leer UUID
		$file = file_get_contents(ROOT_DIR . "/data/xml/" . $id . "_timbrado.xml");
		$file = json_decode($file);
		$uuid = $file->data->uuid;

		$xmlArchivo = ROOT_DIR . "/data/facturas/$uuid.xml";
		$pdfArchivo = ROOT_DIR . "/data/facturas/$uuid.pdf";

		$cuerpo = <<<EOT
			<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional //EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> <html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office"> <head> <!--[if gte mso 9]> <xml> <o:OfficeDocumentSettings> <o:AllowPNG/> <o:PixelsPerInch>96</o:PixelsPerInch> </o:OfficeDocumentSettings> </xml> <![endif]--> <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <meta name="x-apple-disable-message-reformatting"> <!--[if !mso]><!--><meta http-equiv="X-UA-Compatible" content="IE=edge"><!--<![endif]--> <title></title> <style type="text/css"> @media only screen and (min-width: 620px) {.u-row {width: 600px !important; } .u-row .u-col {vertical-align: top; } .u-row .u-col-100 {width: 600px !important; } } @media (max-width: 620px) {.u-row-container {max-width: 100% !important; padding-left: 0px !important; padding-right: 0px !important; } .u-row .u-col {min-width: 320px !important; max-width: 100% !important; display: block !important; } .u-row {width: calc(100% - 40px) !important; } .u-col {width: 100% !important; } .u-col > div {margin: 0 auto; } } body {margin: 0; padding: 0; } table, tr, td {vertical-align: top; border-collapse: collapse; } p {margin: 0; } .ie-container table, .mso-container table {table-layout: fixed; } * {line-height: inherit; } a[x-apple-data-detectors='true'] {color: inherit !important; text-decoration: none !important; } table, td { color: #000000; } a { color: #0000ee; text-decoration: underline; } @media (max-width: 480px) { #u_content_heading_2 .v-font-size { font-size: 20px !important; } #u_content_text_1 .v-container-padding-padding { padding: 10px 20px !important; } #u_content_text_1 .v-text-align { text-align: center !important; } } </style> <!--[if !mso]><!--><link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet" type="text/css"><!--<![endif]-->
			</head>

			<body class="clean-body u_body" style="margin: 0;padding: 0;-webkit-text-size-adjust: 100%;background-color: #ffffff;color: #000000">
			  <!--[if IE]><div class="ie-container"><![endif]-->
			  <!--[if mso]><div class="mso-container"><![endif]-->
			  <table style="border-collapse: collapse;table-layout: fixed;border-spacing: 0;mso-table-lspace: 0pt;mso-table-rspace: 0pt;vertical-align: top;min-width: 320px;Margin: 0 auto;background-color: #ffffff;width:100%" cellpadding="0" cellspacing="0">
			  <tbody>
			  <tr style="vertical-align: top">
			    <td style="word-break: break-word;border-collapse: collapse !important;vertical-align: top">
			    <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" style="background-color: #ffffff;"><![endif]-->
			    

			<div class="u-row-container" style="padding: 0px;background-color: transparent">
			  <div class="u-row" style="Margin: 0 auto;min-width: 320px;max-width: 600px;overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;">
			    <div style="border-collapse: collapse;display: table;width: 100%;height: 100%;background-color: transparent;">
			      <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding: 0px;background-color: transparent;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:600px;"><tr style="background-color: transparent;"><![endif]-->
			      
			<!--[if (mso)|(IE)]><td align="center" width="600" style="background-color: #ffffff;width: 600px;padding: 30px 0px 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;" valign="top"><![endif]-->
			<div class="u-col u-col-100" style="max-width: 320px;min-width: 600px;display: table-cell;vertical-align: top;">
			  <div style="background-color: #ffffff;height: 100%;width: 100% !important;">
			  <!--[if (!mso)&(!IE)]><!--><div style="padding: 30px 0px 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;"><!--<![endif]-->
			  
			<table style="font-family:'Montserrat',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
			  <tbody>
			    <tr>
			      <td class="v-container-padding-padding" style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:'Montserrat',sans-serif;" align="left">
			        
			<table width="100%" cellpadding="0" cellspacing="0" border="0">
			  <tr>
			    <td class="v-text-align" style="padding-right: 0px;padding-left: 0px;" align="center">
			      
			      <img align="center" border="0" src="https://saevalcas.mx/img/cobroplan.png" alt="image" title="image" style="outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;clear: both;display: inline-block !important;border: none;height: auto;float: none;width: 100%;max-width: 485px;" width="485"/>
			      
			    </td>
			  </tr>
			</table>

			      </td>
			    </tr>
			  </tbody>
			</table>

			  <!--[if (!mso)&(!IE)]><!--></div><!--<![endif]-->
			  </div>
			</div>
			<!--[if (mso)|(IE)]></td><![endif]-->
			      <!--[if (mso)|(IE)]></tr></table></td></tr></table><![endif]-->
			    </div>
			  </div>
			</div>



			<div class="u-row-container" style="padding: 0px;background-color: transparent">
			  <div class="u-row" style="Margin: 0 auto;min-width: 320px;max-width: 600px;overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;">
			    <div style="border-collapse: collapse;display: table;width: 100%;height: 100%;background-color: transparent;">
			      <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding: 0px;background-color: transparent;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:600px;"><tr style="background-color: transparent;"><![endif]-->
			      
			<!--[if (mso)|(IE)]><td align="center" width="600" style="background-color: #ffffff;width: 600px;padding: 0px 0px 30px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;" valign="top"><![endif]-->
			<div class="u-col u-col-100" style="max-width: 320px;min-width: 600px;display: table-cell;vertical-align: top;">
			  <div style="background-color: #ffffff;height: 100%;width: 100% !important;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;">
			  <!--[if (!mso)&(!IE)]><!--><div style="padding: 0px 0px 30px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;"><!--<![endif]-->
			  
			<table id="u_content_heading_2" style="font-family:'Montserrat',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
			  <tbody>
			    <tr>
			      <td class="v-container-padding-padding" style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:'Montserrat',sans-serif;" align="left">
			        
			  <h1 class="v-text-align v-font-size" style="margin: 0px; line-height: 140%; text-align: center; word-wrap: break-word; font-weight: normal; font-family: 'Montserrat',sans-serif; font-size: 25px;">
			    <strong>Envío de Factura XML y PDF</strong>
			  </h1>

			      </td>
			    </tr>
			  </tbody>
			</table>

			  <!--[if (!mso)&(!IE)]><!--></div><!--<![endif]-->
			  </div>
			</div>
			<!--[if (mso)|(IE)]></td><![endif]-->
			      <!--[if (mso)|(IE)]></tr></table></td></tr></table><![endif]-->
			    </div>
			  </div>
			</div>



			<div class="u-row-container" style="padding: 0px;background-color: #f1f1f1">
			  <div class="u-row" style="Margin: 0 auto;min-width: 320px;max-width: 600px;overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;">
			    <div style="border-collapse: collapse;display: table;width: 100%;height: 100%;background-color: transparent;">
			      <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding: 0px;background-color: #f1f1f1;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:600px;"><tr style="background-color: transparent;"><![endif]-->
			      
			<!--[if (mso)|(IE)]><td align="center" width="600" style="background-color: #f1f1f1;width: 600px;padding: 30px 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;" valign="top"><![endif]-->
			<div class="u-col u-col-100" style="max-width: 320px;min-width: 600px;display: table-cell;vertical-align: top;">
			  <div style="background-color: #f1f1f1;height: 100%;width: 100% !important;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;">
			  <!--[if (!mso)&(!IE)]><!--><div style="padding: 30px 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;"><!--<![endif]-->
			  
			<table id="u_content_text_1" style="font-family:'Montserrat',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
			  <tbody>
			    <tr>
			      <td class="v-container-padding-padding" style="overflow-wrap:break-word;word-break:break-word;padding:0px 30px;font-family:'Montserrat',sans-serif;" align="left">
			        
			  <div class="v-text-align" style="line-height: 160%; text-align: justify; word-wrap: break-word;">
			    <p style="font-size: 14px; line-height: 160%;"><span style="font-size: 16px; line-height: 25.6px;"><strong>Hola!</strong></span></p>
			<p style="font-size: 14px; line-height: 160%;"> </p>
			<p style="font-size: 14px; line-height: 160%;">Enviamos adjunta el archivo PDF y XML de la factura en relación con la cotización. Cualquier duda o aclaración favor de contactarnos.</p>
			<p style="font-size: 14px; line-height: 160%;"> </p>
			<p style="font-size: 14px; line-height: 160%;"><strong>Equipo Cobroplan</strong></p>
			  </div>

			      </td>
			    </tr>
			  </tbody>
			</table>

			  <!--[if (!mso)&(!IE)]><!--></div><!--<![endif]-->
			  </div>
			</div>
			<!--[if (mso)|(IE)]></td><![endif]-->
			      <!--[if (mso)|(IE)]></tr></table></td></tr></table><![endif]-->
			    </div>
			  </div>
			</div>



			
















			<div class="u-row-container" style="padding: 0px;background-color: transparent">
  <div class="u-row" style="Margin: 0 auto;min-width: 320px;max-width: 600px;overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;">
    <div style="border-collapse: collapse;display: table;width: 100%;height: 100%;background-color: transparent;">
      <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding: 0px;background-color: transparent;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:600px;"><tr style="background-color: transparent;"><![endif]-->
      
<!--[if (mso)|(IE)]><td align="center" width="600" style="width: 600px;padding: 30px 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;" valign="top"><![endif]-->
<div class="u-col u-col-100" style="max-width: 320px;min-width: 600px;display: table-cell;vertical-align: top;">
  <div style="height: 100%;width: 100% !important;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;">
  <!--[if (!mso)&(!IE)]><!--><div style="padding: 30px 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;"><!--<![endif]-->
  
<table style="font-family:'Montserrat',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
  <tbody>
    <tr>
      <td class="v-container-padding-padding" style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:'Montserrat',sans-serif;" align="left">
      </td>
    </tr>
  </tbody>
</table>

<table style="font-family:'Montserrat',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
  <tbody>
    <tr>
      <td class="v-container-padding-padding" style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:'Montserrat',sans-serif;" align="left">
        
<div align="center">
  <div style="display: table; max-width:110px;">
  <!--[if (mso)|(IE)]><table width="110" cellpadding="0" cellspacing="0" border="0"><tr><td style="border-collapse:collapse;" align="center"><table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse; mso-table-lspace: 0pt;mso-table-rspace: 0pt; width:110px;"><tr><![endif]-->
    <!--[if (mso)|(IE)]></tr></table></td></tr></table><![endif]-->
  </div>
</div>

      </td>
    </tr>
  </tbody>
</table>

<table style="font-family:'Montserrat',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
  <tbody>
    <tr>
      <td class="v-container-padding-padding" style="overflow-wrap:break-word;word-break:break-word;padding:10px 40px 0px;font-family:'Montserrat',sans-serif;" align="left">
        
  <div class="v-text-align" style="line-height: 140%; text-align: center; word-wrap: break-word;">
    <p style="font-size: 14px; line-height: 140%; color: #333;">Llámanos al 664-484-1922 de 9:00am a 4:00pm de lunes a viernes.</p>
    <p style="font-size: 14px; line-height: 140%; color: #999;">Manuel Doblado 2721, Piso Int 11-01 A, Col. Calete, Tijuana B.C., C.P. 22044</p>
  </div>

      </td>
    </tr>
  </tbody>
</table>

  <!--[if (!mso)&(!IE)]><!--></div><!--<![endif]-->
  </div>
</div>
<!--[if (mso)|(IE)]></td><![endif]-->
      <!--[if (mso)|(IE)]></tr></table></td></tr></table><![endif]-->
    </div>
  </div>
</div>


    <!--[if (mso)|(IE)]></td></tr></table><![endif]-->
    </td>
  </tr>
  </tbody>
  </table>
  <!--[if mso]></div><![endif]-->
  <!--[if IE]></div><![endif]-->
</body>

</html>
EOT;

		$titulo = 'Envío de Factura PDF/XML';
		$this->enviarCorreoFactura($cuerpo, $titulo, $destinatario, $xmlArchivo, $pdfArchivo);
	}

	// Recibo de Pago
	public function reciboPago($id, $correo) {
		try {
			$sth = $this->_db->prepare("
				SELECT cm.id, cm.id_hist_rec, cm.id_arrendadora
				FROM cobranza_mantenimientos cm
				WHERE cm.id = ?
			");
			$sth->bindParam(1, $id);
			if(!$sth->execute()) throw New Exception();
			$datos = $sth->fetch();

			if ($datos['id'] >= 196613) {
				$tipo = 'ir';
				$folioRecibo = $datos['id'];
			} else {
				$tipo = 'ihr';
				$folioRecibo = $datos['id_hist_rec'];
			}
			$idArrendadora = $datos['id_arrendadora'];

			if ($idArrendadora == 'ARR3' || $idArrendadora == 'ARR2') {
				$recibo = Modelos_Contenedor::crearModelo('Cobranza_Amortizaciones');
				$nombreArchivo = $recibo->recibo($folioRecibo, $tipo, 1);
			} elseif ($idArrendadora == 'ARR5') {
				$recibo = Modelos_Contenedor::crearModelo('Cobranza_Au');
				$nombreArchivo = $recibo->recibo($folioRecibo, $tipo, 1);
			}

			$pdfArchivo = ROOT_DIR . "data/tmp/$nombreArchivo";

			$cuerpo = <<<EOT
				<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional //EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> <html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office"> <head> <!--[if gte mso 9]> <xml> <o:OfficeDocumentSettings> <o:AllowPNG/> <o:PixelsPerInch>96</o:PixelsPerInch> </o:OfficeDocumentSettings> </xml> <![endif]--> <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <meta name="x-apple-disable-message-reformatting"> <!--[if !mso]><!--><meta http-equiv="X-UA-Compatible" content="IE=edge"><!--<![endif]--> <title></title> <style type="text/css"> @media only screen and (min-width: 620px) {.u-row {width: 600px !important; } .u-row .u-col {vertical-align: top; } .u-row .u-col-100 {width: 600px !important; } } @media (max-width: 620px) {.u-row-container {max-width: 100% !important; padding-left: 0px !important; padding-right: 0px !important; } .u-row .u-col {min-width: 320px !important; max-width: 100% !important; display: block !important; } .u-row {width: calc(100% - 40px) !important; } .u-col {width: 100% !important; } .u-col > div {margin: 0 auto; } } body {margin: 0; padding: 0; } table, tr, td {vertical-align: top; border-collapse: collapse; } p {margin: 0; } .ie-container table, .mso-container table {table-layout: fixed; } * {line-height: inherit; } a[x-apple-data-detectors='true'] {color: inherit !important; text-decoration: none !important; } table, td { color: #000000; } a { color: #0000ee; text-decoration: underline; } @media (max-width: 480px) { #u_content_heading_2 .v-font-size { font-size: 20px !important; } #u_content_text_1 .v-container-padding-padding { padding: 10px 20px !important; } #u_content_text_1 .v-text-align { text-align: center !important; } } </style> <!--[if !mso]><!--><link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet" type="text/css"><!--<![endif]--> </head> <body class="clean-body u_body" style="margin: 0;padding: 0;-webkit-text-size-adjust: 100%;background-color: #ffffff;color: #000000"> <!--[if IE]><div class="ie-container"><![endif]--> <!--[if mso]><div class="mso-container"><![endif]--> <table style="border-collapse: collapse;table-layout: fixed;border-spacing: 0;mso-table-lspace: 0pt;mso-table-rspace: 0pt;vertical-align: top;min-width: 320px;Margin: 0 auto;background-color: #ffffff;width:100%" cellpadding="0" cellspacing="0"> <tbody> <tr style="vertical-align: top"> <td style="word-break: break-word;border-collapse: collapse !important;vertical-align: top"> <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" style="background-color: #ffffff;"><![endif]--> <div class="u-row-container" style="padding: 0px;background-color: transparent"> <div class="u-row" style="Margin: 0 auto;min-width: 320px;max-width: 600px;overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;"> <div style="border-collapse: collapse;display: table;width: 100%;height: 100%;background-color: transparent;"> <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding: 0px;background-color: transparent;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:600px;"><tr style="background-color: transparent;"><![endif]--> <!--[if (mso)|(IE)]><td align="center" width="600" style="background-color: #ffffff;width: 600px;padding: 30px 0px 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;" valign="top"><![endif]--> <div class="u-col u-col-100" style="max-width: 320px;min-width: 600px;display: table-cell;vertical-align: top;"> <div style="background-color: #ffffff;height: 100%;width: 100% !important;"> <!--[if (!mso)&(!IE)]><!--><div style="padding: 30px 0px 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;"><!--<![endif]--> <table style="font-family:'Montserrat',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0"> <tbody> <tr> <td class="v-container-padding-padding" style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:'Montserrat',sans-serif;" align="left"> <table width="100%" cellpadding="0" cellspacing="0" border="0"> <tr> <td class="v-text-align" style="padding-right: 0px;padding-left: 0px;" align="center"> <img align="center" border="0" src="https://saevalcas.mx/img/cobroplan.png" alt="image" title="image" style="outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;clear: both;display: inline-block !important;border: none;height: auto;float: none;width: 100%;max-width: 485px;" width="485"/> </td> </tr> </table> </td> </tr> </tbody> </table> <!--[if (!mso)&(!IE)]><!--></div><!--<![endif]--> </div> </div> <!--[if (mso)|(IE)]></td><![endif]--> <!--[if (mso)|(IE)]></tr></table></td></tr></table><![endif]--> </div> </div> </div> <div class="u-row-container" style="padding: 0px;background-color: transparent"> <div class="u-row" style="Margin: 0 auto;min-width: 320px;max-width: 600px;overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;"> <div style="border-collapse: collapse;display: table;width: 100%;height: 100%;background-color: transparent;"> <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding: 0px;background-color: transparent;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:600px;"><tr style="background-color: transparent;"><![endif]--> <!--[if (mso)|(IE)]><td align="center" width="600" style="background-color: #ffffff;width: 600px;padding: 0px 0px 30px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;" valign="top"><![endif]--> <div class="u-col u-col-100" style="max-width: 320px;min-width: 600px;display: table-cell;vertical-align: top;"> <div style="background-color: #ffffff;height: 100%;width: 100% !important;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;"> <!--[if (!mso)&(!IE)]><!--><div style="padding: 0px 0px 30px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;"><!--<![endif]--> <table id="u_content_heading_2" style="font-family:'Montserrat',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0"> <tbody> <tr> <td class="v-container-padding-padding" style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:'Montserrat',sans-serif;" align="left"> <h1 class="v-text-align v-font-size" style="margin: 0px; line-height: 140%; text-align: center; word-wrap: break-word; font-weight: normal; font-family: 'Montserrat',sans-serif; font-size: 25px;"> <strong>¡Gracias por tu pago!</strong> </h1> </td> </tr> </tbody> </table> <!--[if (!mso)&(!IE)]><!--></div><!--<![endif]--> </div> </div> <!--[if (mso)|(IE)]></td><![endif]--> <!--[if (mso)|(IE)]></tr></table></td></tr></table><![endif]--> </div> </div> </div> <div class="u-row-container" style="padding: 0px;background-color: #f1f1f1"> <div class="u-row" style="Margin: 0 auto;min-width: 320px;max-width: 600px;overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;"> <div style="border-collapse: collapse;display: table;width: 100%;height: 100%;background-color: transparent;"> <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding: 0px;background-color: #f1f1f1;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:600px;"><tr style="background-color: transparent;"><![endif]--> <!--[if (mso)|(IE)]><td align="center" width="600" style="background-color: #f1f1f1;width: 600px;padding: 30px 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;" valign="top"><![endif]--> <div class="u-col u-col-100" style="max-width: 320px;min-width: 600px;display: table-cell;vertical-align: top;"> <div style="background-color: #f1f1f1;height: 100%;width: 100% !important;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;"> <!--[if (!mso)&(!IE)]><!--><div style="padding: 30px 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;"><!--<![endif]--> <table id="u_content_text_1" style="font-family:'Montserrat',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0"> <tbody> <tr> <td class="v-container-padding-padding" style="overflow-wrap:break-word;word-break:break-word;padding:0px 30px;font-family:'Montserrat',sans-serif;" align="left"> <div class="v-text-align" style="line-height: 160%; text-align: justify; word-wrap: break-word;"> <p style="font-size: 14px; line-height: 160%;"><span style="font-size: 16px; line-height: 25.6px;"><strong>Hola!</strong></span></p> <p style="font-size: 14px; line-height: 160%;"> </p> <p style="font-size: 14px; line-height: 160%;">Enviamos adjunto el comprobante de pago en formato PDF. Cualquier duda o aclaración favor de contactarnos.</p> <p style="font-size: 14px; line-height: 160%;"> </p> <p style="font-size: 14px; line-height: 160%;"><strong>Equipo Cobroplan</strong></p> </div> </td> </tr> </tbody> </table> <!--[if (!mso)&(!IE)]><!--></div><!--<![endif]--> </div> </div> <!--[if (mso)|(IE)]></td><![endif]--> <!--[if (mso)|(IE)]></tr></table></td></tr></table><![endif]--> </div> </div> </div> <div class="u-row-container" style="padding: 0px;background-color: transparent"> <div class="u-row" style="Margin: 0 auto;min-width: 320px;max-width: 600px;overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;"> <div style="border-collapse: collapse;display: table;width: 100%;height: 100%;background-color: transparent;"> <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding: 0px;background-color: transparent;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:600px;"><tr style="background-color: transparent;"><![endif]--> <!--[if (mso)|(IE)]><td align="center" width="600" style="width: 600px;padding: 30px 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;" valign="top"><![endif]--> <div class="u-col u-col-100" style="max-width: 320px;min-width: 600px;display: table-cell;vertical-align: top;"> <div style="height: 100%;width: 100% !important;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;"> <!--[if (!mso)&(!IE)]><!--><div style="padding: 30px 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;"><!--<![endif]--> <table style="font-family:'Montserrat',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0"> <tbody> <tr> <td class="v-container-padding-padding" style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:'Montserrat',sans-serif;" align="left"> </td> </tr> </tbody> </table> <table style="font-family:'Montserrat',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0"> <tbody> <tr> <td class="v-container-padding-padding" style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:'Montserrat',sans-serif;" align="left"> <div align="center"> <div style="display: table; max-width:110px;"> <!--[if (mso)|(IE)]><table width="110" cellpadding="0" cellspacing="0" border="0"><tr><td style="border-collapse:collapse;" align="center"><table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse; mso-table-lspace: 0pt;mso-table-rspace: 0pt; width:110px;"><tr><![endif]--> <!--[if (mso)|(IE)]></tr></table></td></tr></table><![endif]--> </div> </div> </td> </tr> </tbody> </table> <table style="font-family:'Montserrat',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0"> <tbody> <tr> <td class="v-container-padding-padding" style="overflow-wrap:break-word;word-break:break-word;padding:10px 40px 0px;font-family:'Montserrat',sans-serif;" align="left"> <div class="v-text-align" style="line-height: 140%; text-align: center; word-wrap: break-word;"> <p style="font-size: 14px; line-height: 140%; color: #333;">Llámanos al 664-484-1922 de 9:00am a 4:00pm de lunes a viernes.</p> <p style="font-size: 14px; line-height: 140%; color: #999;">Manuel Doblado 2721, Piso Int 11-01 A, Col. Calete, Tijuana B.C., C.P. 22044</p> </div> </td> </tr> </tbody> </table> <!--[if (!mso)&(!IE)]><!--></div><!--<![endif]--> </div> </div> <!--[if (mso)|(IE)]></td><![endif]--> <!--[if (mso)|(IE)]></tr></table></td></tr></table><![endif]--> </div> </div> </div> <!--[if (mso)|(IE)]></td></tr></table><![endif]--> </td> </tr> </tbody> </table> <!--[if mso]></div><![endif]--> <!--[if IE]></div><![endif]--> </body> </html>
EOT;

			$titulo = 'Comprobante de Pago';
			$this->enviarCorreoComprobante($cuerpo, $titulo, $correo, $pdfArchivo);
		} catch (Exception $e) {
			var_dump($e->getMessage());
		}
	}

}