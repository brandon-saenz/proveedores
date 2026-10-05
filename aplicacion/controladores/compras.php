<?php
final class Compras extends Controlador {
	function ordenes($accion = null, $id = null, $status = null) {
		$acceso = $this->cargarModelo('acceso');
		$modelo = $this->cargarModelo('Compras_Ordenes');

		// Sub-orden para facturas: 3er segmento de la URL o, si no llega, id_sub_orden por GET/POST
		$subFactura = ($status !== null && $status !== '') ? $status : ($_POST['id_sub_orden'] ?? $_GET['id_sub_orden'] ?? null);

		switch ($accion) {
			// $status = id de la sub-orden (opcional; obligatorio si la orden tiene sub-órdenes): /get_factura/{id_orden}[/{id_sub_orden}]
			case 'get_factura': echo json_encode($modelo->getFactura($id, $subFactura)); die; break;
			case 'guardar_factura': echo json_encode($modelo->guardarFactura($id, $_POST, $_FILES, $subFactura)); die; break;
			// /compras/ordenes/get_listado/{pestaña}[/{id_proveedor}]  (el proveedor es opcional)
			case 'get_listado': echo json_encode($modelo->getListado($id, $status)); die; break;
			case 'get_indicadores':
				// Conteo de órdenes por pestaña (generados / revisadas / autorizadas)
				if (!$acceso->estaLoggeado()) {
					http_response_code(401);
					echo json_encode(array('type' => 'error', 'msj' => 'Tu sesión expiró. Vuelve a iniciar sesión.')); die;
				}
				echo json_encode($modelo->getIndicadores()); die;
			break;
			case 'visualizar': $modelo->pdf($id); die; break; // PDF de la orden (sin cambios)

			// ---- Vista de visualizar / editar la orden ----
			case 'ver':
				// Al abrir la vista la orden pasa automáticamente de Generada a Revisada
				if (!$acceso->estaLoggeado()) {
					$pagina = $this->cargarVista('login');
					$pagina->renderizar();
					return;
				}
				$modelo->marcarRevisada($id);
				$pagina = $this->cargarVista('compras/visualizar');
				$pagina->set('titulo', 'Orden de Compra');
				$pagina->set('idOrden', (int) $id);
				$pagina->renderizar();
				return;
			break;

			// ---- Endpoints JSON de la vista (sesión obligatoria; guardar/autorizar solo POST) ----
			case 'get_orden':
			case 'guardar_orden':
			case 'autorizar':
				if (!$acceso->estaLoggeado()) {
					http_response_code(401);
					echo json_encode(array('type' => 'error', 'msj' => 'Tu sesión expiró. Vuelve a iniciar sesión.')); die;
				}
				if ($accion !== 'get_orden' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
					http_response_code(405);
					echo json_encode(array('type' => 'error', 'msj' => 'Método no permitido.')); die;
				}
				if ($accion === 'get_orden') echo json_encode($modelo->getOrden($id));
				if ($accion === 'guardar_orden') echo json_encode($modelo->guardarOrden($id, $_POST));
				if ($accion === 'autorizar') echo json_encode($modelo->autorizarOrden($id, $status)); // $status = id de la sub-orden (opcional)
				die;
			break;
		}

		!$acceso->estaLoggeado()? $pagina = $this->cargarVista('login') : $pagina = $this->cargarVista('compras/ordenes');

		$archivos = array_filter(scandir(APP . '/vistas/compras/modals/'), function($archivo) {return $archivo !== '.' && $archivo !== '..';});
		foreach ($archivos as $value) {$modals[] = APP . '/vistas/compras/modals/'.$value;}
		$pagina->set('modals', $modals);

		$pagina->set('titulo', "Ordenes de Compra");
		$pagina->renderizar();
	}

}