<?php
class Error extends Controlador {
	function index() {
		$this->error404();
	}
	
	function error404() {
		$pagina = $this->cargarVista('error');
		$pagina->renderizar();
	}
}