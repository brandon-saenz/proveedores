<?php
class Controlador {
	public function __construct() {
		spl_autoload_register(function ($nombreClase) {
			$nombreArchivo = str_replace('_', DIRECTORY_SEPARATOR, strtolower($nombreClase)).'.php';
		    $archivo = APP . $nombreArchivo;

	     	if ((class_exists($archivo,FALSE))) {
	            return FALSE;
	        }
	        $pClassFilePath = str_replace('_',DIRECTORY_SEPARATOR,$archivo);
	        if ((file_exists($pClassFilePath) === FALSE) || (is_readable($pClassFilePath) === FALSE)) {
	            return FALSE;
	        }
	        
	        require($pClassFilePath);
		});
	}
	
	public function cargarModelo($nombre) {
		return Modelos_Contenedor::crearModelo($nombre);
	}
	
	public function cargarVista($name) {
		$view = new Vista($name);
		return $view;
	}
	
	public function cargarPlugin($name) {
		require(APP .'plugins/'. strtolower($name) .'.php');
	}
	
	public function cargarInc($name) {
		require(APP .'inc/'. strtolower($name) .'.php');
		$helper = new $name;
		return $helper;
	}
	
	public function redireccionar($loc) {
		global $config;
		
		header('Location: '. $config['base_url'] . '/' . $loc);
	}
}