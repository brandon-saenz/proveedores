<?php
final class Modelos_Usuario extends Modelo {
	protected $_db = null;
	public $nombre;
	public $apellidos;
	public $grado;
	public $email;
	public $telefono;
	public $celular;
	public $extension;
	public $huella;
	public $mensajes = array();

	public function iniciarDb($db) {
    	if (!$this->_db) {
			$this->_db = $db;
        }
    }

	public function obtenerDatos() {
		try {
			$sth = $this->_db->prepare("SELECT nombre, apellidos, grado, email, telefono, celular, extension, huella FROM usuarios WHERE id = ?");
			$sth->bindParam(1, $_SESSION['login_id']);
			$sth->setFetchMode(PDO::FETCH_INTO, $this);
			if(!$sth->execute()) throw New Exception();
			$sth->fetch();

	  		return $this;
		} catch (Exception $e) {
			$this->mensajes[] = Modelos_Sistema::status(0, $e->getMessage());
		}
	}
	
	public function modificarDatosPersonales($datos) {
		try {
			$nombre = strtoupper($datos['nombre']);
			$apellidos = strtoupper($datos['apellidos']);
			$email = strtolower($datos['email']);
			$grado = $datos['grado'];
			$telefono = $datos['telefono'];
			$extension = $datos['extension'];
			$celular = $datos['celular'];
			$contrasena1 = $datos['contrasena1'];
			$contrasena2 = $datos['contrasena2'];
			
			if ($nombre && $apellidos && $email && $telefono) {
				if (($contrasena1 != '' && $contrasena2 != '') && ($contrasena1 == $contrasena2)) {
					$salt = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'), 0, 64);
					$contrasenaEncriptada = hash("sha256", $contrasena1.$salt);
					$arregloDatos = array($nombre, $apellidos, $email, $grado, $telefono, $extension, $celular, $salt, $contrasenaEncriptada, $_SESSION['login_id']);

					$sth = $this->_db->prepare("UPDATE usuarios SET
												nombre = ?,
												apellidos = ?,
												grado = ?,
												email = ?,
												telefono = ?,
												extension = ?,
												celular = ?,
												salt = ?,
												contrasena = ?
												WHERE id = ?");
					if($sth->execute($arregloDatos)) {
						$this->mensajes[] = Modelos_Sistema::status(2, 'Datos modificados exitosamente.');
					} else {
						throw New Exception();
					}
				} elseif($contrasena1 == '' && $contrasena2 == '') {
					$arregloDatos = array($nombre, $apellidos, $grado, $email, $telefono, $extension, $celular, $_SESSION['login_id']);

					$sth = $this->_db->prepare("UPDATE usuarios SET 
												nombre = ?,
												apellidos = ?,
												grado = ?,
												email = ?,
												telefono = ?,
												extension = ?,
												celular = ?
												WHERE id = ?");
					if($sth->execute($arregloDatos)) {
						$this->mensajes[] = Modelos_Sistema::status(2, 'Datos modificados exitosamente.');
					} else {
						throw New Exception();
					}
				} else {
					$this->mensajes[] = Modelos_Sistema::status(1, 'Las contrase&ntilde;as no coinciden.');
				}
			} else {
				$this->mensajes[] = Modelos_Sistema::status(1, 'Es necesario llenar los campos obligatorios.');
			}
		} catch (Exception $e) {
			$this->mensajes[] = Modelos_Sistema::status(0, $e->getMessage());
		}
	}

	public function actualizarDatos() {
		try {
			$email = $_POST['email'];
			$telefono1 = $_POST['telefono1'];
			$telefono2 = $_POST['telefono2'];
			
			$sth = $this->_db->prepare("
				UPDATE propietarios SET 
				email = ?,
				telefono1 = ?,
				telefono2 = ?
				WHERE id = ?
			");
			$arregloDatos = array($email, $telefono1, $telefono2, $_SESSION['login_id']);
			if(!$sth->execute($arregloDatos)) throw New Exception();

			$correo = Modelos_Contenedor::crearModelo('Correo');
			$correo->informacionActualizada();

			header('Location:' . STASIS. '/usuario/actualizar/1');
		} catch (Exception $e) {
			$this->mensajes[] = Modelos_Sistema::status(0, $e->getMessage());
		}
	}
}