<?php
final class Principal extends Controlador {

	function index() {
		$acceso = $this->cargarModelo('acceso');
		!$acceso->estaLoggeado()? $pagina = $this->redireccionar('./') : $pagina = $this->cargarVista('principal');

		$proveedor = $this->cargarModelo('proveedor');
		
		$pagina->set('titulo', 'Portal de Proveedores');
		$pagina->set('menu', 'principal');
		$pagina->set('datos', $proveedor->cumplimiento());
		if ($_SESSION['login_status'] == 3) $pagina->set('listado', $proveedor->requisiciones());

		$pagina->set('estaLoggeado', $acceso->estaLoggeado());
		$pagina->renderizar();
	}

}