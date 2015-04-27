<?php

namespace ionmvc\classes;

use ionmvc\classes\input\useragent;
use ionmvc\exceptions\app as app_exception;

class input {

	private static $useragent = null;

	private $data = [];
	private $all  = [];

	public static function __callStatic( $method,$args ) {
		$class = request::input();
		$method = "_{$method}";
		if ( !method_exists( $class,$method ) ) {
			throw new app_exception( "Method '%s' not found",$method );
		}
		return call_user_func_array( [ $class,$method ],$args );
	}

	public function __construct( $data=array() ) {
		foreach( ['get','post','cookie','files','server'] as $key ) { //think of how to integrate headers
			$this->data[$key] = ( isset( $data[$key] ) ? $data[$key] : [] );
		}
		$this->data['request'] = array_func::merge( $this->data['get'],$this->data['post'] );
		$this->data['files']   = array_func::expand_key( $this->parse_files() );
		$this->all = array_func::merge( $this->data['request'],$this->data['files'] );
	}

	public function __call( $method,$args ) {
		$method = "_{$method}";
		if ( !method_exists( $this,$method ) ) {
			throw new app_exception( "Method '%s' not found",$method );
		}
		return call_user_func_array( [ $this,$method ],$args );
	}

	private function parse_files() {
		$files = [];
		if ( count( $this->data['files'] ) === 0 ) {
			return $files;
		}
		foreach( $this->data['files'] as $name => $data ) {
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
		return array_func::get( $this->data['get'],$key,$retval );
	}

	public function _post( $key=null,$retval=false ) {
		return array_func::get( $this->data['post'],$key,$retval );
	}

	public function _request( $key=null,$retval=false ) {
		return array_func::get( $this->data['request'],$key,$retval );
	}

	public function _cookie( $key=null,$retval=false ) {
		return array_func::get( $this->data['cookie'],$key,$retval );
	}

	public function _server( $key=null,$retval=false ) {
		return array_func::get( $this->data['server'],$key,$retval );
	}

	public function _all() {
		return $this->all;
	}

	public function _file( $key=null,$retval=false ) {
		return array_func::get( $this->data['files'],$key,$retval );
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
		return [
			'file' => $name,
			'path' => $path
		];
	}

	public static function useragent() {
		if ( is_null( self::$useragent ) ) {
			self::$useragent = new useragent;
		}
		return self::$useragent;
	}

}

?>