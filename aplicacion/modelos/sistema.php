<?php
final class Modelos_Sistema {
	protected $_db = null;

	public function iniciarDb($db) {
    	if (!$this->_db) {
			$this->_db = $db;
        }
    }

	static public function status($valor, $mensaje) {
		switch ($valor) {
			// Error interno
			case 0:
			return '<div class="alert alert-custom alert-danger" role="alert"> <div class="alert-icon"><i class="fa fa-exclamation-triangle"></i></div> <div class="alert-text">Error interno del sistema: ' . $mensaje . '</div> </div>';
			break;
			
			// Error general
			case 1:
			return '<div class="alert alert-custom alert-danger" role="alert"> <div class="alert-icon"><i class="fa fa-exclamation-triangle"></i></div> <div class="alert-text">' . $mensaje . '</div> </div>';
			break;

			// Confirmacion general
			case 2:
			return '<div class="alert alert-custom alert-primary" role="alert"> <div class="alert-icon"><i class="fa fa-check"></i></div> <div class="alert-text">' . $mensaje . '</div> </div>';
			break;
			
			// Informacion general
			case 3:
			return '<div class="alert alert-custom alert-info" role="alert"> <div class="alert-icon"><i class="fa fa-info-circle"></i></div> <div class="alert-text">' . $mensaje . '</div> </div>';
			break;

			// Alerta/advertencia general
			case 4:
			return '<div class="alert alert-custom alert-warning" role="alert"> <div class="alert-icon"><i class="fa fa-exclamation-triangle"></i></div> <div class="alert-text">' . $mensaje . '</div> </div>';
			break;
		}
	}

	public function listadoSecciones() {
		try {
			$datosVista = array();
			$html = '';

			$sth = $this->_db->query("
				SELECT DISTINCT(seccion) AS seccion
				FROM propietarios
				WHERE seccion != ''
				ORDER BY seccion ASC
			");
			if(!$sth->execute()) throw New Exception();
			while ($datos = $sth->fetch()) {
				$html .= '<option value="' . $datos['seccion'] . '">' . $datos['seccion'] . '</option>';
			}

	  		return $html;
		} catch (Exception $e) {
			$this->mensajes[] = Modelos_Sistema::status(0, $e->getMessage());
		}
	}
}