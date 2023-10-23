<?php
final class Modelos_Dashboard extends Modelo {
	protected $_db = null;

	public function iniciarDb($db) {
    	if (!$this->_db) {
			$this->_db = $db;
        }
    }

	public function __construct($db) {
		$this->iniciarDb($db);
	}

	public function dashboard() {
		$html = array();
		return $html;
	}
}