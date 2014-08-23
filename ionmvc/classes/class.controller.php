<?php

namespace ionmvc\classes;

class controller {

	protected $data = array();

	public function __construct() {}

	public function __isset( $key ) {
		return isset( $this->data[$key] );
	}

	public function __get( $key ) {
		return ( array_key_exists( $key,$this->data ) ? $this->data[$key] : null );
	}

	public function __set( $key,$value ) {
		$this->data[$key] = $value;
	}

	public function __unset( $key ) {
		unset( $this->data[$key] );
	}

}

?>