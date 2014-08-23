<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;
use ionmvc\exceptions\path as path_exception;

class config {

	private static $instance = null;

	protected $data = array();

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	public static function init( $data ) {
		$config = self::instance()->data( $data );
	}

	public static function __callStatic( $method,$args ) {
		$class = self::instance();
		$method = "_{$method}";
		if ( !method_exists( $class,$method ) ) {
			throw new app_exception( "Method '%s' not found",$method );
		}
		return call_user_func_array( array( $class,$method ),$args );
	}

	public static function load( $file,$_config=array() ) {
		try {
			if ( !isset( $_config['full_path'] ) || !$_config['full_path'] ) {
				$path = path::config( $file );
			}
			else {
				$path = $file;
			}
			include $path;
			if ( isset( $_config['extend'] ) && $_config['extend'] ) {
				if ( !isset( $config ) ) {
					throw new app_exception( 'No config variable set in file: %s',$file );
				}
				self::instance()->_extend( $config );
			}
		}
		catch( path_exception $e ) {
			throw new app_exception( 'Unable to load config file %s - Reason: %s',$file,$e->getMessage() );
		}
	}

	public function data( $data ) {
		$this->data = $data;
	}

	public function _extend( $data ) {
		$this->data = array_func::merge_recursive_distinct( $this->data,$data );
	}

	public function _get( $key=null,$retval=false ) {
		$array = $this->data;
		if ( is_null( $key ) ) {
			return $array;
		}
		if ( isset( $array[$key] ) ) {
			return $array[$key];
		}
		foreach( explode( '.',$key ) as $_key ) {
			if ( !is_array( $array ) || !array_key_exists( $_key,$array ) ) {
				return $retval;
			}
			$array = $array[$_key];
		}
		return $array;
	}

	public function _set( $key,$value ) {
		$array =& $this->data;
		if ( isset( $array[$key] ) ) {
			$array[$key] = $value;
			return;
		}
		$keys = explode( '.',$key );
		while( count( $keys ) > 1 ) {
			$key = array_shift( $keys );
			if ( !isset( $array[$key] ) || !is_array( $array[$key] ) ) {
				$array[$key] = array();
			}
			$array =& $array[$key];
		}
		$array[array_shift( $keys )] = $value;
	}

}

?>