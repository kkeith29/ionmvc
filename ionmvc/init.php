<?php

namespace ionmvc;

use ionmvc\classes\autoloader;
use ionmvc\classes\config;

if ( !defined('ENV') ) {
	define('ENV','production');
}

error_reporting(E_ALL);
ini_set('display_errors','On');

define('IONMVC',true);
define('PATH_ROOT',dirname(dirname(__FILE__)) . '/');
define('PATH_APP',PATH_ROOT . 'application/');
define('PATH_IONMVC',PATH_ROOT . 'ionmvc/');

include PATH_IONMVC . 'classes/class.config.php';
include PATH_IONMVC . 'config/config.php';

config::init( $config );

$config_file = PATH_APP . 'config/config.php';
if ( !file_exists( $config_file ) ) {
	die('Config file not found');
}

include $config_file;

include PATH_IONMVC . 'exceptions/exception.app.php';
include PATH_IONMVC . 'exceptions/exception.path.php';

include PATH_IONMVC . 'classes/class.path.php';
include PATH_IONMVC . 'classes/class.autoloader.php';

autoloader::register();

?>