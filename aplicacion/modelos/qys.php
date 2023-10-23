<?php
final class Modelos_Qys extends Modelo {
	protected $_db = null;
	public $mensajes = array();

	public function iniciarDb($db) {
    	if (!$this->_db) {
			$this->_db = $db;
        }
    }

    public function nueva() {
		try {
			$datosArray = array();

			$sth = $this->_db->query("SELECT id FROM qys ORDER BY id DESC LIMIT 1");
			$datosArray['folio'] = str_pad($sth->fetchColumn()+1, 5, '0', STR_PAD_LEFT);

			return $datosArray;
		} catch (Exception $e) {
			echo Modelos_Sistema::status(0, $e->getMessage());
		}
	}

	public function generar() {
		try {
			require APP . 'inc/class.upload.php';

			function reArrayFiles(&$file_post) {
				$file_ary = array();
				$file_count = count($file_post['name']);
				$file_keys = array_keys($file_post);
				for ($i=0; $i<$file_count; $i++) {
					foreach ($file_keys as $key) {
						$file_ary[$i][$key] = $file_post[$key][$i];
					}
				}
				return $file_ary;
			}

			$idPropietario = $_SESSION['login_id'];
			$tipo = $_POST['tipo'];
			$id_servicio = $_POST['id_servicio'];
			$otro = $_POST['otro'];
			$descripcion = $_POST['descripcion'];

			$arregloDatos = array($idPropietario, $tipo, $id_servicio, $otro, $descripcion);
			$sth = $this->_db->prepare("INSERT INTO qys (id_propietario, fecha_creacion, tipo, id_servicio, otro, descripcion) VALUES (?, NOW(), ?, ?, ?, ?)");
			if(!$sth->execute($arregloDatos)) throw New Exception();
			$id = $this->_db->lastInsertId();

			if (!$_FILES['archivo']['size'][0] == 0) {
				$file_ary = reArrayFiles($_FILES['archivo']);

				foreach ($file_ary as $file) {
					$handle = new upload($file);
					if ($handle->uploaded) {
						$archivo = str_replace(' ', '_', $handle->file_src_name_body) . '-' . time();
						$handle->file_new_name_body   = $archivo;
						$archivoDb = $archivo . '.' . $handle->file_src_name_ext;
						$handle->process(ROOT_DIR . 'data/privada/archivos/');
						if ($handle->processed) {
							$handle->clean();
						}
					}

					$arregloDatos = array($id, $archivoDb);
					$sth = $this->_db->prepare("INSERT INTO qys_archivos (id_qys, archivo) VALUES (?, ?)");
					if(!$sth->execute($arregloDatos)) throw New Exception();
				}
			}

			header('Location:' . STASIS. '/movimientos/qys/enviada');
		} catch (Exception $e) {
			var_dump($sth->errorInfo());
			die;
		}
	}

}