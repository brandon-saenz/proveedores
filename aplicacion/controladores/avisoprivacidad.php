<?php
final class AvisoPrivacidad extends Controlador {

	function index() {
		$pagina = $this->cargarVista('aviso_privacidad');
		$pagina->renderizar();
	}

}