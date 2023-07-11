<?php
final class Usuario extends Controlador {

	public function actualizar($status = null) {
		$acceso = $this->cargarModelo('acceso');
		$usuario = $this->cargarModelo('usuario');
		!$acceso->estaLoggeado()? $pagina = $this->redireccionar('./') : $pagina = $this->cargarVista('usuario');

		if (isset($_POST['actualizarDatos']))
		$usuario->actualizarDatos();

		$pagina->set('titulo', 'Actualizar Datos Personales');
		$pagina->set('menu', 'usuario');

		if ($status == 1) $pagina->set('status', Modelos_Sistema::status(2, 'Información de contacto actualizada.'));
		$pagina->renderizar();
	}

	public function verificar_sesion() {
		echo 1;
	}

	public function cs() {
		$acceso = $this->cargarModelo('acceso');
		$acceso->cerrarSesion();
		$this->redireccionar('./');
	}

}