<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;
use ionmvc\exceptions\path as path_exception;

class path {

	const writable     = 1;
	const readable     = 2;
	const is_directory = 3;
	const is_file      = 4;

	const path_only = 1;
	const full_info = 2;

	const prepend   = 1;
	const append    = 2;
	const overwrite = 3;

	private $paths  = [];
	private $groups = [];

	private static function path_get( $paths,$alias ) {
		if ( isset( $paths[$alias] ) ) {
			return preg_replace_callback( '/\{([A-Za-z_\-]+)\}/',function( $match ) use ( $paths ) {
				return self::path_get( $paths,$match[1] );
			},$paths[$alias] );
		}
		return '';
	}

	public static function __callStatic( $method,$args ) {
		$class = app::$registry->find( \ionmvc\CLASS_TYPE_DEFAULT,'path' );
		$_method = "_{$method}";
		if ( method_exists( $class,$_method ) ) {
			return call_user_func_array( [ $class,$_method ],$args );
		}
		if ( $class->has_group( $method ) ) {
			array_unshift( $args,$method );
			return call_user_func_array( [ $class,'handle_group' ],$args );
		}
		throw new app_exception( "Method or group '%s' not found",$method );
	}

	public function __construct( config $config ) {
		$this->paths = $config->get('framework.paths');
		foreach( $this->paths as $alias => &$path ) {
			$path = self::path_get( $this->paths,$alias );
			unset( $path );
		}
		$this->groups = $config->get('framework.path_groups');
	}

	private function has_group( $group ) {
		if ( !isset( $this->groups[$group] ) ) {
			return false;
		}
		return true;
	}

	private function handle_group( $group,$file,$config=[] ) {
		$paths = $this->groups[$group];
		foreach( $paths as $path ) {
			$_path = $this->paths[$path] . '/' . $file;
			if ( file_exists( $_path ) ) {
				return $_path;
			}
		}
		return false;
	}

	public function _add() {
		$args  = func_get_args();
		$count = count( $args );
		if ( $count === 0 || ( $count === 1 && !is_array( $args[0] ) ) || $count > 2 ) {
			throw new app_exception('Invalid arguments passed');
		}
		if ( $count === 1 ) {
			foreach( $args[0] as $alias => $path ) {
				$this->_add( $alias,$path );
			}
			return;
		}
		list( $alias,$path ) = $args;
		$this->paths[$alias] = $path;
		$this->paths[$alias] = self::path_get( $this->paths,$alias );
	}

	public function _group( $name,$path,$action=self::append ) {
		if ( !isset( $this->groups[$name] ) ) {
			$this->groups[$name] = [];
		}
		if ( !is_array( $path ) ) {
			$path = [ $path ];
		}
		switch( $action ) {
			case self::prepend:
				$this->groups[$name] = array_merge( $path,$this->groups[$name] );
				break;
			case self::append:
				$this->groups[$name] = array_merge( $this->groups[$name],$path );
				break;
			case self::overwrite:
				$this->groups[$name] = $path;
				break;
			default:
				throw new app_exception('Invalid action');
				break;
		}
	}

	public function _get( $path,$default_key=null,$config=[] ) {
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
				$config['group'] = $key;
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
					return [
						'group'     => ( isset( $config['group'] ) ? $config['group'] : false ),
						'key'       => $key,
						'path'      => ( $path !== '' ? ( is_dir( $_path ) ? rtrim( $path,'/' ) . '/' : $path ) : $path ),
						'full_path' => ( is_dir( $_path ) ? rtrim( $_path,'/' ) . '/' : $_path )
					];
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

	public function _get_group( $group ) {
		if ( !is_array( $group ) ) {
			$group = [ $group ];
		}
		$groups = [];
		foreach( $group as $_group ) {
			if ( !isset( $this->groups[$_group] ) ) {
				throw new app_exception( 'Group \'%s\' not found',$_group );
			}
			$groups = array_merge( $groups,$this->groups[$_group] );
		}
		return $groups;
	}

	public function _get_all() {
		return $this->paths;
	}

	public function _get_groups() {
		return $this->groups;
	}

	public static function test( $type,$path,$default_key=null ) {
		$functions = [
			self::writable     => 'is_writable',
			self::readable     => 'is_readable',
			self::is_directory => 'is_dir',
			self::is_file      => 'file_exists'
		];
		if ( !isset( $functions[$type] ) ) {
			throw new app_exception('Test type is not valid');
		}
		try {
			if ( $default_key !== false ) {
				$path = self::get( $path,$default_key );
			}
			if ( $functions[$type]( $path ) ) {
				return true;
			}
			return false;
		}
		catch( path_exception $e ) {
			return false;
		}
	}

	public function _debug() {
		echo '<pre>' . print_r( $this->paths,true ) . '</pre>';
		echo '<pre>' . print_r( $this->groups,true ) . '</pre>';
	}

}

?>