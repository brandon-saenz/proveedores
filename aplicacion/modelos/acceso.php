<?php
final class Modelos_Acceso extends Modelo {
    protected $_db 					= null;
    private $_loggeado 				= false;
    private $_nombreUsuario 		= '';
    
    public function __construct($db) {
		$this->iniciarDb($db);

		if ($this->loggearConDataSesion()) {
			$this->_loggeado = true;
		} elseif ($this->loggearConDataPost()) {
			$this->_loggeado = true;
		}
    }

    public function iniciarDb($db) {
    	if (!$this->_db) {
			$this->_db = $db;
        }
    }

    private function loggearConDataSesion() {
        if (!empty($_SESSION['login_id']) && ($_SESSION['login_flag']==1)) {
			if (empty($_SESSION['sId'])) {
				if (isset($_COOKIE['sId'])) {
					$sId = $_COOKIE['sId'];
				} else {
					$sId = session_id();
					setcookie('sId', $sId, time()+60*60*24*365);
				}
				
				$_SESSION['sId'] = $sId; 
			}

			$sth = $this->_db->prepare("SELECT * FROM proveedores WHERE id = ?");
			$sth->bindParam(1, $_SESSION['login_id']);
			if(!$sth->execute()) throw New Exception();
			$datos = $sth->fetch();
			
			$_SESSION['login_uniqueid'] = $datos['uniqueid'];
			$_SESSION['login_status'] = $datos['status'];
			$_SESSION['login_logo'] = $datos['logo'];
			$_SESSION['login_nombre'] = $datos['nombre'];
			$_SESSION['login_rfc'] = $datos['rfc'];
			$_SESSION['login_tipo'] = $datos['tipo'];
			$_SESSION['login_contacto'] = $datos['contacto'];
			$_SESSION['login_telefono'] = $datos['telefono'];
			$_SESSION['login_correo'] = $datos['correo'];
			$_SESSION['login_contrasena1'] = $datos['contrasena1'];
			$_SESSION['login_contrasena2'] = $datos['contrasena2'];

			return true;
        } else {
            return false;
        }
    }

    private function loggearConDataPost() {
        if(isset($_POST["login"]) && !empty($_POST['nombreUsuario']) && !empty($_POST['contrasena'])) {
			try {
				$this->_nombreUsuario = $_POST['nombreUsuario'];
				
				$sth = $this->_db->prepare("SELECT * FROM proveedores WHERE email = ? LIMIT 1");
				$sth->bindParam(1, $this->_nombreUsuario);
				if(!$sth->execute()) throw New Exception();
				$datos = $sth->fetch();
				if (hash("sha256", $_POST["contrasena"].$datos['salt']) == $datos['contrasena']) {
					$_SESSION['login_flag'] = 1;
					$_SESSION['login_id'] = $datos['id'];
					$_SESSION['login_nombre'] = $datos['nombre'];
					$_SESSION['login_rfc'] = $datos['rfc'];
					$_SESSION['login_tipo'] = $datos['tipo'];
					$_SESSION['login_contacto'] = $datos['contacto'];
					$_SESSION['login_telefono'] = $datos['telefono'];
					$_SESSION['login_correo'] = $datos['correo'];
					$_SESSION['login_contrasena1'] = $datos['contrasena1'];
					$_SESSION['login_contrasena2'] = $datos['contrasena2'];

					return true;
				} else {
					$this->mensaje = Modelos_Sistema::status(1, "Correo o contraseña inválida, favor de verificar.");
					return false;
				}
			} catch (Exception $e) {
				$this->mensaje = $e->getMessage();
			}
        } elseif (isset($_POST["login"]) && !empty($_POST['nombreUsuario']) && empty($_POST['contrasena'])) {
            $this->mensaje = "Campos obligatorios";
        }
    }
    
    public function cerrarSesion() {
		setcookie('sId', '', time()-60*60*24*365);
	
        $_SESSION = array();
        session_regenerate_id(); 
        session_destroy();
        return true;
    }
    
    public function estaLoggeado() {
        return $this->_loggeado;
    }
}