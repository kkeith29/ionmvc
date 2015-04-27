<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;

class autoloader {

	const prepend   = 1;
	const append    = 2;
	const overwrite = 3;

	private static $class_types = [];
	private static $namespaces = [];

	public static function register() {
		self::$class_types = config::get('framework.class_types');
		self::$namespaces = config::get('framework.namespaces');
		foreach( self::$namespaces as $namespace => $config ) {
			self::class_type_namespace( $config['type'],$namespace );
		}
		\spl_autoload_register( __CLASS__ . '::load' );
	}

	public static function class_id( $name ) {
		$name = str_replace( '\\','.',ltrim( $name,'.' ) );
		$namespace = false;
		if ( ( $pos = strrpos( $name,'.' ) ) !== false ) {
			$namespace = explode( '.',$name );
		}
		if ( $namespace === false ) {
			return false;
		}
		$_path = [];
		$i = 1;
		while( ( $name = array_pop( $namespace ) ) !== null ) {
			$_path[$i] = $name;
			$_namespace = implode( '.',$namespace );
			if ( isset( self::$namespaces[$_namespace] ) ) {
				return implode( '/',array_reverse( $_path ) );
			}
			$i++;
		}
		return false;
	}

	public static function load( $name ) {
		if ( class_exists( $name,false ) ) {
			return true;
		}
		$name = str_replace( '\\','.',ltrim( $name,'.' ) );
		$namespace = false;
		if ( ( $pos = strrpos( $name,'.' ) ) !== false ) {
			$namespace = explode( '.',$name );
		}
		$path = false;
		if ( $namespace !== false ) {
			$_path = [];
			$i = 1;
			while( ( $name = array_pop( $namespace ) ) !== null ) {
				$_path[$i] = ( $i !== 1 ? str_replace( '_','-',$name ) : $name );
				$_namespace = implode( '.',$namespace );
				if ( isset( self::$namespaces[$_namespace] ) ) {
					$namespace = $_namespace;
					$class_type = self::class_types([
						'namespace' => $namespace
					]);
					if ( isset( $class_type['file_prefix'] ) ) {
						$_path[1] = $class_type['file_prefix'] . '.' . $_path[1];
					}
					$path = implode( '/',array_reverse( $_path ) ) . '.php';
					break;
				}
				$i++;
			}
			unset( $_path,$_namespace );
			if ( $path === false ) {
				return false;
			}
		}
		else {
			$namespace = 'ionmvc.classes';
			$path = "{$name}.php";
		}
		if ( ( $path = path::get( $path,self::$namespaces[$namespace]['path'] ) ) !== false ) {
			require_once $path;
			return true;
		}
		return false;
	}

	public static function has_class_type( $type ) {
		return isset( self::$class_types[$type] );
	}

	public static function add_class_type( $type,$config ) {
		self::$class_types[$type] = $config;
	}

	public static function class_types( $config=[] ) {
		$class_types = self::$class_types;
		if ( !isset( $config['type'] ) && !isset( $config['namespace'] ) ) {
			return $class_types;
		}
		if ( isset( $config['namespace'] ) ) {
			if ( !isset( self::$namespaces[$config['namespace']] ) ) {
				throw new app_exception( 'Unable to find namespace: %s',$config['namespace'] );
			}
			if ( !isset( self::$namespaces[$config['namespace']]['type'] ) ) {
				throw new app_exception( 'No class type defined for namespace: %s',$config['namespace'] );
			}
			$config['type'] = self::$namespaces[$config['namespace']]['type'];
		}
		if ( !isset( $class_types[$config['type']] ) ) {
			throw new app_exception( 'Unable to find class type: %s',$config['type'] );
		}
		return $class_types[$config['type']];
	}

	public static function add_namespace( $namespace,$class_type,$path ) {
		$namespace = str_replace( ['/','\\'],'.',$namespace );
		self::$namespaces[$namespace] = [
			'type' => $class_type,
			'path' => $path
		];
		self::class_type_namespace( $class_type,$namespace,self::prepend );
	}

	public static function class_type_namespace( $class_type,$namespace,$action=self::append ) {
		if ( !isset( self::$class_types[$class_type]['namespaces'] ) ) {
			self::$class_types[$class_type]['namespaces'] = [];
		}
		switch( $action ) {
			case self::prepend:
				array_unshift( self::$class_types[$class_type]['namespaces'],$namespace );
			break;
			case self::append:
				self::$class_types[$class_type]['namespaces'][] = $namespace;
			break;
			case self::overwrite:
				self::$class_types[$class_type]['namespaces'] = [ $namespace ];
			break;
		}
	}

	public static function class_by_type( $class,$class_type,$config=[] ) {
		$class_type = self::class_types([
			'type' => $class_type
		]);
		if ( !isset( $class_type['namespaces'] ) ) {
			return false;
		}
		$class = trim( str_replace( ['/'],'.',$class ),'.' );
		$found = false;
		foreach( $class_type['namespaces'] as $namespace ) {
			$_class = "{$namespace}.{$class}";
			if ( self::load( $_class ) ) {
				$found = true;
				break;
			}
		}
		if ( !$found ) {
			return false;
		}
		$_class = '\\' . str_replace( '.','\\',$_class );
		if ( !isset( $config['instance'] ) || !$config['instance'] ) {
			return $_class;
		}
		$_class = new \ReflectionClass( $_class );
		if ( isset( $config['args'] ) ) {
			return $_class->newInstanceArgs( $config['args'] );
		}
		return $_class->newInstance();
	}

	public static function debug() {
		echo '<pre>' . print_r( self::$class_types,true ) . '</pre>';
		echo '<pre>' . print_r( self::$namespaces,true ) . '</pre>';
	}

}

?>