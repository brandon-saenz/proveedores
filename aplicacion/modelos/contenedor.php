<?php
final class Modelos_Contenedor extends Modelo {
	public static $_db;

	public static function crearModelo($nombre) {
		if (!self::$_db) {
			self::$_db = Modelos_Conexion::obtenerObjeto();
        }

        $nombre = 'Modelos_' . $nombre;
		$modelo = new $nombre(self::$_db);
		$modelo->iniciarDb(self::$_db);
		return $modelo;
	}

	public static function crearModeloDbLess($nombre) {
		$nombre = 'Modelos_' . $nombre;
		$modelo = new $nombre();
		return $modelo;
	}
}