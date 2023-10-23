<?php
// Modo produccion/desarrollo
// ini_set('display_errors', 1);
ini_set('display_errors', '0');

ini_set('session.cookie_lifetime', 60 * 60 * 24 * 7);
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 7);
ini_set('xdebug.var_display_max_depth', -1);
ini_set('xdebug.var_display_max_children', -1);
ini_set('xdebug.var_display_max_data', -1);
ini_set('memory_limit', '1024M');

// Definir constante de dominio
// Ejemplo: http://localhost
define('DOMINIO', (strtolower(getenv('HTTPS')) == 'on' ? 'https' : 'http') . '://' . getenv('HTTP_HOST') . (($p = getenv('SERVER_PORT')) != 80 AND $p != 443 ? ":$p" : ''));

// Definir constante de la direccion
// Ejemplo: /website/
define('DIRECCION', parse_url(getenv('REQUEST_URI'), PHP_URL_PATH));

// Definir carpeta raiz del sistema
// Ejemplo: D:\wamp\www\website/
define('ROOT_DIR', realpath(dirname(__FILE__)) .'/');

// Definir la carpeta de aplicacion
// Ejemplo: D:\wamp\www\website/aplicacion/
define('APP', ROOT_DIR .'aplicacion/');

// Definir el locale para la p�gina
setlocale(LC_ALL,"es_ES@euro","es_ES","esp");

// Definir la zona horaria
date_default_timezone_set('America/Tijuana');

// Definir un custom path para guardar las sesiones
ini_set('session.save_path', ROOT_DIR . 'data/sessions');

// Definir la decodificaci�n
// iconv_set_encoding("internal_encoding", "UTF-8");
mb_internal_encoding('UTF-8');

session_start();