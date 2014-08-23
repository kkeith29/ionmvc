<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;
use ionmvc\exceptions\path as path_exception;

class path {

	const writable = 1;
	const readable = 2;
	const is_directory = 3;
	const is_file = 4;

	const path_only = 1;
	const full_info = 2;

	const prepend   = 1;
	const append    = 2;
	const overwrite = 3;

	private static $instance = null;

	private $paths = array();
	private $groups = array();

	public static function __callStatic( $method,$args ) {
		$class = self::instance();
		if ( $class->has_group( $method ) ) {
			array_unshift( $args,$method );
			return call_user_func_array( array( $class,'handle_group' ),$args );
		}
		$method = "_{$method}";
		if ( !method_exists( $class,$method ) ) {
			throw new app_exception( "Method '%s' not found",$method );
		}
		return call_user_func_array( array( $class,$method ),$args );
	}

	private function __construct() {
		$this->paths = config::get('framework.paths');
		foreach( $this->paths as $alias => &$path ) {
			$path = $this->path_get( $alias );
		}
		$this->groups = config::get('framework.path_groups');
	}

	private function path_get( $alias ) {
		$alias = ( is_array( $alias ) ? $alias[1] : $alias );
		if ( isset( $this->paths[$alias] ) ) {
			return preg_replace_callback( '/\{([A-Za-z_\-]+)\}/',array( $this,'path_get' ),$this->paths[$alias] );
		}
		return '';
	}

	private function has_group( $group ) {
		if ( !isset( $this->groups[$group] ) ) {
			return false;
		}
		return true;
	}

	private function handle_group( $group,$file,$config=array() ) {
		$paths = $this->groups[$group];
		foreach( $paths as $path ) {
			$_path = $this->paths[$path] . '/' . $file;
			if ( file_exists( $_path ) ) {
				return $_path;
			}
		}
		throw new path_exception( 'Unable to load group \'%s\' file: %s',$group,$file );
	}

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	public function _add( $alias,$path ) {
		$this->paths[$alias] = $path;
		$this->paths[$alias] = $this->path_get( $alias );
	}

	public function _get( $path,$default_key=null,$config=array() ) {
		$opath = $path;
		if ( strpos( $path,':' ) === false ) {
			if ( is_null( $default_key ) || isset( $this->paths[$path] ) ) {
				$key = $path;
				$path = '';
			}
			else {
				$key = $default_key;
			}
		}
		else {
			list( $key,$path ) = explode( ':',$path,2 );
		}
		if ( !isset( $this->paths[$key] ) && !isset( $this->groups[$key] ) ) {
			throw new path_exception( 'Unable to find path/group with name: %s',$key );
		}
		if ( !isset( $config['return'] ) ) {
			$config['return'] = self::path_only;
		}
		if ( isset( $this->groups[$key] ) ) {
			foreach( $this->groups[$key] as $_key ) {
				$config['group'] = $_key;
				if ( ( $_path = $this->_get( $path,$_key,$config ) ) !== false ) {
					return $_path;
				}
			}
			return false;
		}
		$_path = "{$this->paths[$key]}/{$path}";
		if ( file_exists( $_path ) ) {
			switch( $config['return'] ) {
				case self::path_only:
					return $_path;
				break;
				case self::full_info:
					return array(
						'group'     => ( isset( $config['group'] ) ? $config['group'] : false ),
						'key'       => $key,
						'path'      => ( $path !== '' ? ( is_dir( $_path ) ? rtrim( $path,'/' ) . '/' : $path ) : $path ),
						'full_path' => ( is_dir( $_path ) ? rtrim( $_path,'/' ) . '/' : $_path )
					);
				break;
				default:
					throw new app_exception( "Invalid return type '%s'",$config['return'] );
				break;
			}
		}
		if ( isset( $config['throw_exceptions'] ) && $config['throw_exceptions'] === true ) {
			throw new path_exception( 'Unable to get path for %s',$opath );
		}
		return false;
	}

	public function _debug() {
		echo '<pre>' . print_r( $this->paths,true ) . '</pre>';
		echo '<pre>' . print_r( $this->groups,true ) . '</pre>';
	}

	public static function test( $type,$path,$default_key=null ) {
		$functions = array(
			self::writable     => 'is_writable',
			self::readable     => 'is_readable',
			self::is_directory => 'is_dir',
			self::is_file      => 'file_exists'
		);
		if ( !isset( $functions[$type] ) ) {
			throw new app_exception('Test type is not valid');
		}
		try {
			$path = ( $default_key === false ? $path : self::get( $path,$default_key ) );
			if ( $functions[$type]( $path ) ) {
				return true;
			}
			return false;
		}
		catch( app_exception $e ) {
			return false;
		}
	}

}

?>