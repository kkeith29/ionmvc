<?php

namespace ionmvc\classes;

use ionmvc\classes\autoloader;
use ionmvc\classes\config;
use ionmvc\classes\finder;
use ionmvc\exceptions\app as app_exception;

class package {

	private static $instances = [];
	private static $_types = [
		\ionmvc\CLASS_TYPE_CONTROLLER => [
			'name' => 'controller',
			'path' => 'controllers'
		],
		\ionmvc\CLASS_TYPE_DEFAULT => [
			'name' => 'class',
			'path' => 'classes'
		],
		\ionmvc\CLASS_TYPE_COMMAND => [
			'name' => 'command',
			'path' => 'commands'
		],
		\ionmvc\CLASS_TYPE_EXCEPTION => [
			'name' => 'exception',
			'path' => 'exceptions'
		],
		\ionmvc\CLASS_TYPE_HELPER => [
			'name' => 'helper',
			'path' => 'helpers'
		],
		\ionmvc\CLASS_TYPE_LIBRARY => [
			'name' => 'library',
			'path' => 'libraries'
		],
		\ionmvc\CLASS_TYPE_MODEL => [
			'name' => 'model',
			'path' => 'models'
		]
	];

	protected $types = [];

	public $info = [];
	public $type_info = [];

	final public static function init() {
		$packages_file = path::get('packages.json','storage-app');
		if ( $packages_file === false ) {
			return false;
		}
		//load packages
		
		return;
		$finder = new finder;
		$package_path = path::get('app-package');
		$finder->path( $package_path );
		$finder->config('dir_only',true);
		$dirs = $finder->go();
		$packages = [];
		//parse directory structure
		foreach( $dirs as $dir ) {
			$package = $dir;
			$subdir  = false;
			if ( ( $pos = strpos( $package,'/' ) ) !== false ) {
				$subdir  = substr( $package,( $pos + 1 ) );
				$package = substr( $package,0,$pos );
			}
			if ( preg_match( '#^[a-z0-9\-]+$#',$package ) !== 1 ) {
				continue;
			}
			if ( !isset( $packages[$package] ) ) {
				$packages[$package] = [
					'directories' => []
				];
			}
			if ( $subdir === false ) {
				continue;
			}
			$packages[$package]['directories'][] = $subdir;
		}
		//compile package data
		foreach( $packages as $package => &$info ) {
			$info['package_dir'] = $package;
			$info['name'] = str_replace( '-','_',$package );
			$info['path'] = $package_path . $package . '/';
			$file = $info['path'] . "package.{$info['name']}.php";
			if ( !file_exists( $file ) ) {
				throw new app_exception( 'Unable to find package file for package: %s',$package );
			}
			$info['namespace'] = "ionmvc.packages.{$info['name']}";
			$info['path_alias'] = "package-{$info['name']}";
			include $file;
			$class = "\\ionmvc\\packages\\{$info['name']}";
			if ( !method_exists( $class,'package_info' ) ) {
				throw new app_exception( 'Unable to find package_info method for package: %s',$package );
			}
			$info['data'] = $class::package_info();
			if ( !isset( $info['data']['version'] ) ) {
				throw new app_exception( 'Version not defined for package: %s',$package );
			}
			if ( !isset( $info['data']['priority'] ) ) {
				$info['data']['priority'] = 5;
			}
			unset( $info );
		}
		//check requirements and prioritize packages
		$priorities = [];
		foreach( $packages as $package => $info ) {
			if ( isset( $info['data']['require'] ) ) {
				foreach( $info['data']['require'] as $_package => $version ) {
					$operator = '=';
					if ( is_array( $version ) ) {
						list( $version,$operator ) = $version;
					}
					if ( !isset( $packages[$_package] ) ) {
						throw new app_exception( 'Package \'%s\' requires package \'%s\' with version %s%s',$package,$_package,$operator,$version );
					}
					if ( !version_compare( $packages[$_package]['data']['version'],$version,$operator ) ) {
						throw new app_exception( 'Package \'%s\' requires package \'%s\' to have a version %s%s',$package,$_package,$operator,$version );
					}
				}
			}
			if ( !isset( $priorities[$info['data']['priority']] ) ) {
				$priorities[$info['data']['priority']] = [];
			}
			$priorities[$info['data']['priority']][] = $info;
		}
		unset( $packages );
		krsort( $priorities,SORT_NUMERIC );
		foreach( $priorities as $priority => $packages ) {
			foreach( $packages as $info ) {
				//load in config files
				if ( in_array( 'config',$info['directories'] ) ) {
					$config_path = $info['path'] . 'config/';
					foreach( ['constants.php'=>false,'config.php'=>true] as $config_file => $extend ) {
						$_path = $config_path . $config_file;
						if ( !file_exists( $_path ) ) {
							continue;
						}
						config::load( $_path,[
							'full_path' => true,
							'extend'    => $extend
						] );
					}
				}
				path::add( $info['path_alias'],"{app-package}/{$info['package_dir']}" );
				$class = '\\' . str_replace( '.','\\',$info['namespace'] );
				self::$instances[$info['name']] = new $class( $info );
				if ( method_exists( self::$instances[$info['name']],'setup' ) ) {
					self::$instances[$info['name']]->setup();
				}
				self::$instances[$info['name']]->_init();
			}
		}
		unset( $priorities );
	}

	final public static function instance( $name ) {
		if ( !isset( self::$instances[$name] ) ) {
			throw new app_exception( 'Instance for package \'%s\' not found',$name );
		}
		return self::$instances[$name];
	}

	public static function __callStatic( $class,$args ) {
		return self::instance( $class );
	}

	public static function loaded( $name ) {
		$name = str_replace( '-','_',$name );
		return isset( self::$instances[$name] );
	}

	public function __construct( $info ) {
		$this->info = $info;
	}

	public function add_type( $name,$config=[] ) {
		if ( !autoloader::has_class_type( $config['type'] ) ) {
			if ( !isset( $config['type_config'] ) ) {
				$config['type_config'] = [];
			}
			autoloader::add_class_type( $config['type'],$config['type_config'] );
		}
		$this->types[$config['type']] = [
			'name' => $name,
			'path' => $config['path']
		];
		$_namespace = $this->info['namespace'] . '.' . str_replace( '-','_',$config['path'] );
		$_path_name = "{$this->info['path_alias']}-{$name}";
		path::add( $_path_name,"{app-package}/{$this->info['package_dir']}/{$config['path']}" );
		if ( !isset( $config['register'] ) || $config['register'] ) {
			autoloader::add_namespace( $_namespace,$config['type'],$_path_name );
		}
		$this->type_info[$name] = [
			'namespace'  => $_namespace,
			'path_alias' => $_path_name
		];
	}

	public function _init() {
		foreach( self::$_types as $class_type => $config ) {
			if ( !in_array( $config['path'],$this->info['directories'] ) ) {
				continue;
			}
			$config['type'] = $class_type;
			$this->add_type( $config['name'],$config );
		}
	}

}

?>