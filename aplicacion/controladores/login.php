<?php
class Login extends Controlador {

	function index() {
		$acceso = $this->cargarModelo('acceso');
		$sistema = $this->cargarModelo('sistema');
		$proveedor = $this->cargarModelo('proveedor');

		!$acceso->estaLoggeado()? $pagina = $this->cargarVista('login') : $this->redireccionar('principal');

		if (isset($_POST['registro']))
		$proveedor->registro();

		$pagina->set('index', 1);
		
		
		if (!empty($acceso->mensaje)) $pagina->set('mensaje', $acceso->mensaje);
		if (!empty($proveedor->mensaje)) $pagina->set('mensaje', $proveedor->mensaje);

		if ($_SESSION['success_restore']){
			$pagina->set('mensaje', $_SESSION['success_restore']);
			$_SESSION['success_restore'] = '';
		} 

		$pagina->set('estaLoggeado', $acceso->estaLoggeado());
		$pagina->set('listadoSecciones', $sistema->listadoSecciones());
		$pagina->renderizar();
	}

	function forgot($accion = null, $id = null) {
		$acceso = $this->cargarModelo('acceso');
		$sistema = $this->cargarModelo('sistema');
		$proveedor = $this->cargarModelo('proveedor');

		!$acceso->estaLoggeado()? $pagina = $this->cargarVista('login') : $this->redireccionar('principal');

		if (isset($_POST['forgot']))
		$proveedor->forgot();

		if (isset($_POST['restore']))
		$proveedor->restore($id);

		switch ($accion) {
			case 'restore_password_page':
				$pagina->set('validarTempID', $proveedor->validarTempID($id));
				$pagina->set('restore_password_page', 1);
			break;
			default: 
				$pagina->set('forgot', 1);
			break;
		}
		
		if (!empty($acceso->mensaje)) $pagina->set('mensaje', $acceso->mensaje);
		if (!empty($proveedor->mensaje)) $pagina->set('mensaje', $proveedor->mensaje);

		$pagina->set('estaLoggeado', $acceso->estaLoggeado());
		$pagina->set('listadoSecciones', $sistema->listadoSecciones());
		$pagina->renderizar();
	}
}