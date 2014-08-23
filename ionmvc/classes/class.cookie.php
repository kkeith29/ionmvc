<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;

class cookie {

	const remove     = -3600;
	const one_hour   = 3600;
	const six_hours  = 21600;
	const one_day    = 86400;
	const one_month  = 2592000;
	const six_months = 15552000;
	const one_year   = 31104000;

	private $salt = false;
	private $prefix = '';

	public $data = null;

	public static function __callStatic( $method,$args ) {
		$class = app::cookie();
		$method = "_{$method}";
		if ( !method_exists( $class,$method ) ) {
			throw new app_exception( "Method '%s' not found",$method );
		}
		return call_user_func_array( array( $class,$method ),$args );
	}

	public function __construct() {
		if ( ( $salt = config::get('cookie.salt') ) === false ) {
			throw new app_exception('Cookie salt string must be set in config');
		}
		$this->salt = $salt;
		$this->prefix = config::get('cookie.prefix');
		$this->data = new igsr( $_COOKIE );
		$this->data->callback( igsr::is_set,array( $this,'check_hash' ) );
		$this->data->callback( igsr::get,array( $this,'check_hash' ) );
	}

	public function _is_set() {
		$prefix = $this->prefix;
		$args = func_get_args();
		$args = array_map( function( $value ) use ( $prefix ) {
			return $prefix . $value;
		},$args );
		return $this->data->is_set( $args );
	}

	public function _get( $name ) {
		$name = $this->prefix . $name;
		return $this->data->get( $name );
	}

	public function _set( $name,$value,$expiry=self::one_day,$path='/',$domain=null,$secure=false,$httponly=false ) {
		$name = $this->prefix . $name;
		$value = md5( $this->salt . $value ) . "-{$value}";
		$expiry = ( is_numeric( $expiry ) ? time() + $expiry : strtotime( $expiry ) );
		$domain = ( is_null( $domain ) ? ( $_SERVER['SERVER_NAME'] == 'localhost' ? false : ( isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'] ) ) : $domain );
		return setcookie( $name,$value,$expiry,$path,$domain,$secure,$httponly );
	}

	public function _remove( $name,$expiry=self::remove,$path='/',$domain=null,$secure=false,$httponly=false ) {
		$domain = ( is_null( $domain ) ? ( $_SERVER['SERVER_NAME'] == 'localhost' ? false : ( isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'] ) ) : $domain );
		$this->_set( $name,'',$expiry,$path,$domain,$secure,$httponly );
	}

	public function check_hash( $type,$arg,$value,$igsr ) {
		if ( $value === false ) {
			return false;
		}
		if ( $type == igsr::is_set ) {
			$value = $igsr->get( $arg );
			return ( $value === false ? false : true );
		}
		list( $md5,$value ) = explode( '-',$value,2 );
		if ( $md5 !== md5( $this->salt . $value ) ) {
			return false;
		}
		return $value;
	}

}

?>