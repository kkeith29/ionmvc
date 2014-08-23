<?php

namespace ionmvc\classes;

use ionmvc\classes\input\useragent;
use ionmvc\exceptions\app as app_exception;

class input {

	private static $useragent = null;

	private $request = array();
	private $files = array();
	private $all = array();

	public static function __callStatic( $method,$args ) {
		$class = app::input();
		$method = "_{$method}";
		if ( !method_exists( $class,$method ) ) {
			throw new app_exception( "Method '%s' not found",$method );
		}
		return call_user_func_array( array( $class,$method ),$args );
	}

	public function __construct() {
		$this->request = array_func::merge( $_GET,$_POST );
		$this->files = array_func::expand_key( $this->parse_files() );
		$this->all = array_func::merge( $this->request,$this->files );
	}

	private function parse_files() {
		$files = array();
		if ( count( $_FILES ) === 0 ) {
			return $files;
		}
		foreach( $_FILES as $name => $data ) {
			foreach( $data as $type => $value ) {
				if ( is_array( $value ) ) {
					$value = array_func::flatten_key( $value,$name );
					foreach( $value as $_key => $_value ) {
						$files[$_key][$type] = $_value;
					}
					continue;
				}
				$files[$name][$type] = $value;
			}
		}
		return $files;
	}

	public function _get( $key=null,$retval=false ) {
		return array_func::get( $_GET,$key,$retval );
	}

	public function _post( $key=null,$retval=false ) {
		return array_func::get( $_POST,$key,$retval );
	}

	public function _request( $key=null,$retval=false ) {
		return array_func::get( $this->request,$key,$retval );
	}

	public function _all() {
		return $this->all;
	}

	public function _file( $key=null,$retval=false ) {
		return array_func::get( $this->files,$key,$retval );
	}

	public function _has( $key ) {
		if ( $this->_request( $key,false ) === false ) {
			return false;
		}
		return true;
	}

	public function _has_file( $key ) {
		if ( ( $tmp_name = $this->_file( "{$key}/tmp_name",false ) ) === false || $tmp_name == '' ) {
			return false;
		}
		return true;
	}

	public function _upload( $key,$destination ) {
		if ( !$this->_has_file( $key ) ) {
			return false;
		}
		$path = path::get( $destination );
		if ( !path::test( path::writable,$path,false ) ) {
			throw new app_exception('Upload destination path is not writable');
		}
		$path = rtrim( $path,'/' );
		$file = $this->_file( $key );
		$name = func::rand_string( 20,'alpha,numeric' ) . '.' . file::get_extension( $file['name'] );
		$path = "{$path}/{$name}";
		if ( !move_uploaded_file( $file['tmp_name'],$path ) ) {
			return false;
		}
		return array(
			'file' => $name,
			'path' => $path
		);
	}

	public static function useragent() {
		if ( is_null( self::$useragent ) ) {
			self::$useragent = new useragent;
		}
		return self::$useragent;
	}

}

?>