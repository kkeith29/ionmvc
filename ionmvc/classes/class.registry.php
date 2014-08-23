<?php

namespace ionmvc\classes;

class registry {

	const app     = 1;
	const helper  = 2;
	const model   = 3;

	private $instances = array();

	public function add( $type,$name,$instance ) {
		if ( !isset( $this->instances[$type] ) ) {
			$this->instances[$type] = array();
		}
		$this->instances[$type][$name] = $instance;
	}

	public function exists( $type,$name ) {
		return isset( $this->instances[$type][$name] );
	}

	public function find( $type,$name ) {
		if ( isset( $this->instances[$type][$name] ) ) {
			return $this->instances[$type][$name];
		}
		return false;
	}

}

?>