<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;

class helper {

	protected $data = array();

	final public static function instance( $name,$varname=null ) {
		if ( is_null( $varname ) ) {
			$varname = str_replace( array('/','__'),'_',$name );
		}
		if ( ( $instance = app::$registry->find( registry::helper,$varname ) ) === false ) {
			$instance = autoloader::class_by_type( $name,\ionmvc\CLASS_TYPE_HELPER,array(
				'instance' => true
			) );
			if ( $instance === false ) {
				throw new app_exception( 'Unable to load helper: %s',$name );
			}
			app::$registry->add( registry::helper,$varname,$instance );
		}
		return $instance;
	}

	final public static function register( $obj,$name,$varname=null ) {
		if ( is_null( $varname ) ) {
			$varname = str_replace( '/','_',$name );
		}
		$instance = self::instance( $name,$varname );
		$obj->{$varname.'_helper'} = $instance;
	}

	public static function __callStatic( $class,$args ) {
		return self::instance( $class );
	}

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