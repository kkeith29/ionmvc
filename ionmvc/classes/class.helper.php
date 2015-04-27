<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;
use ionmvc\traits\magic_vars;

class helper {

	use magic_vars;

	final public static function instance( $name,$varname=null ) {
		if ( is_null( $varname ) ) {
			$varname = str_replace( array('/','__'),'_',$name );
		}
		if ( ( $instance = app::$registry->find( \ionmvc\CLASS_TYPE_HELPER,$varname ) ) === false ) {
			$instance = autoloader::class_by_type( $name,\ionmvc\CLASS_TYPE_HELPER,array(
				'instance' => true
			) );
			if ( $instance === false ) {
				throw new app_exception( 'Unable to load helper: %s',$name );
			}
			app::$registry->add( \ionmvc\CLASS_TYPE_HELPER,$varname,$instance );
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

}

?>