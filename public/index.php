<?php

use ionmvc\classes\app;
use ionmvc\exceptions\app as app_exception;

include '../ionmvc/init.php';

try {
	app::initialize();
}
catch( app_exception $e ) {
	$e->handle();
}

?>