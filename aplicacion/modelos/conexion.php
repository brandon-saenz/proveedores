<?php
final class Modelos_Conexion {
	private $_host =		 'localhost';
	private $_bd =			 null;
	static $_objeto;
	
	private function __construct() {
		try {  
			$lista = array('localhost', '127.0.0.1');
			if(in_array($_SERVER['HTTP_HOST'], $lista)) {
				$_usuario =		 'root';
				$_contrasena =	 '';
				$_nombre =		 'saevalcas_db';
			} else {
		    	$_usuario =		 'saevalcas_db';
				$_contrasena =	 '8%Y&=H!YE1FV';
				$_nombre =		 'saevalcas_db';
			}

			$this->_bd = new PDO("mysql:host=$this->_host;dbname=$_nombre;charset=utf8", $_usuario, $_contrasena);
			$this->_bd->exec("SET NAMES UTF8");
			$this->_bd->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			$this->_bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		catch(PDOException $e) {
			echo $e->getMessage();
		}
	}
	
	private function __clone() {}
	
	public static function obtenerObjeto() {
		if( ! (self::$_objeto instanceof self)) {
			self::$_objeto = new self();
		}
		$objeto = self::$_objeto;
		return $objeto->_bd;
	}
}