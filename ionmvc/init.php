<?php

namespace ionmvc;

const VERSION = '1.0.0';

if ( !defined('PATH_ROOT') ) {
	define('PATH_ROOT',dirname(dirname(__FILE__)) . '/');
}
define('PATH_IONMVC',PATH_ROOT . 'ionmvc/');

error_reporting(E_ALL);
ini_set('display_errors','On');

include PATH_IONMVC . 'classes/class.app.php';
include PATH_IONMVC . 'config/app.php';

?>