<?php

namespace ionmvc\traits;

trait magic_vars {

	protected $__data = [];

	public function __isset( $key ) {
		return isset( $this->__data[$key] );
	}

	public function __get( $key ) {
		return ( array_key_exists( $key,$this->__data ) ? $this->__data[$key] : null );
	}

	public function __set( $key,$value ) {
		$this->__data[$key] = $value;
	}

	public function __unset( $key ) {
		unset( $this->__data[$key] );
	}

}

?>