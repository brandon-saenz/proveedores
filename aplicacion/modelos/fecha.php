<?php
final class Modelos_Fecha extends Modelo {
	static public function hora($fecha) {
		return date('h:i A', strtotime($fecha));
	}

	static public function formatearFecha($original='', $format="%d/%m/%Y") {
		$format = ($format=='date' ? "%m-%d-%Y" : $format);
		$format = ($format=='datetime' ? "%m-%d-%Y %H:%M:%S" : $format);
		$format = ($format=='mysql-date' ? "%Y-%m-%d" : $format);
		$format = ($format=='mysql-datetime' ? "%Y-%m-%d %H:%M:%S" : $format);
		return (!empty($original) ? strftime($format, strtotime($original)) : "" );
	}

	static public function formatearFechaUsa($original='', $format="%m/%d/%Y") {
		$format = ($format=='date' ? "%m-%d-%Y" : $format);
		$format = ($format=='datetime' ? "%m-%d-%Y %H:%M:%S" : $format);
		$format = ($format=='mysql-date' ? "%Y-%m-%d" : $format);
		$format = ($format=='mysql-datetime' ? "%Y-%m-%d %H:%M:%S" : $format);
		return (!empty($original) ? strftime($format, strtotime($original)) : "" );
	}

	static public function formatearFechaHora($fecha) {
		return date('d/m/Y - h:i A', strtotime($fecha));
	}

	static public function formatearFechaHoraUsa($fecha) {
		return date('m/d/Y - h:i A', strtotime($fecha));
	}

	static public function formatearHora($fecha) {
		return date('H:i', strtotime($fecha));
	}
}