<?php

namespace ionmvc\commands\app;

use ionmvc\classes\cli;

class package extends \ionmvc\classes\command {

	public function install_command( $config=[] ) {
		if ( ( $name = cli::arg('name',false) ) !== false ) {
			//check for package with name
			//check for install ability
			//run install functions
		}
	}

}

?>