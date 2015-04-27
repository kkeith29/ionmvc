<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;

class library {

	final public static function instance( $name,$args=[] ) {
		$instance = autoloader::class_by_type( $name,\ionmvc\CLASS_TYPE_LIBRARY,[
			'instance' => true,
			'args'     => $args
		] );
		if ( $instance === false ) {
			throw new app_exception( 'Unable to load library: %s',$name );
		}
		return $instance;
	}

	final public static function register( $obj,$name,$varname=null ) {
		if ( is_null( $varname ) ) {
			$varname = str_replace( ['/'],'_',$name );
		}
		$obj->{$varname.'_library'} = self::instance( $name );
	}

	public static function __callStatic( $method,$args ) {
		return self::instance( $method,$args );
	}

}

?>