<?php

use ionmvc\classes\config;

switch( ENV ) {
	case 'production':
		config::set('production',true);
	break;
}

?>