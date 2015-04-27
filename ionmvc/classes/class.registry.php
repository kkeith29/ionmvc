<?php

namespace ionmvc\classes;

class registry {

	private $instances = [];

	public function add( $type,$name,$instance ) {
		if ( !isset( $this->instances[$type] ) ) {
			$this->instances[$type] = [];
		}
		$this->instances[$type][$name] = $instance;
		return $instance;
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