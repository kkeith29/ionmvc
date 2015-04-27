<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;

include PATH_IONMVC . 'exceptions/exception.app.php';
include PATH_IONMVC . 'exceptions/exception.path.php';

include PATH_IONMVC . 'classes/class.registry.php';
include PATH_IONMVC . 'classes/class.config.php';
include PATH_IONMVC . 'classes/class.path.php';
include PATH_IONMVC . 'classes/class.autoloader.php';

class app {

	public static $registry = null;
	public static $config = [];

	private static $start_time = 0;
	private static $end_time = 0;
	private static $mode;
	private static $env;

	public static function initialize( array $config ) {
		self::$start_time = microtime(true);
		
		self::$registry = new registry;
		$config = self::$registry->add( \ionmvc\CLASS_TYPE_DEFAULT,'config',new config( $config ) );
		self::$registry->add( \ionmvc\CLASS_TYPE_DEFAULT,'path',new path( $config ) );
		
		autoloader::register();
		
		if ( ( $path = path::config('constants.php') ) !== false ) {
			include $path;
		}
		if ( ( $path = path::config('app.php') ) !== false ) {
			include $path;
		}
		if ( ( $path = path::config('routes.php') ) !== false ) {
			include $path;
		}
		
		self::$config = config::get('framework');
		
		if ( !isset( self::$config['env'] ) ) {
			self::$config['env'] = \ionmvc\ENV_PRODUCTION;
		}
		self::$env = self::$config['env'];

		self::$mode = request::mode_uri;
		if ( php_sapi_name() === 'cli' ) {
			self::$mode = request::mode_cli;
		}
		
		error::init();
		time::init(); //might move to request init
		
		self::init('event');
		
		if ( ( $path = path::config('events.php') ) !== false ) {
			include $path;
		}
		
		$request_config = [
			'mode'        => self::$mode,
			'use_globals' => true,
			'router'      => router::instance()
		];
		switch( self::$mode ) {
			case request::mode_uri:
				$hooks = ['init','request.create','request.destruct','response.create','stop']; 
				break;
			case request::mode_cli:
				$hooks = ['init','request.create','request.destruct','stop'];
				break;
			default:
				throw new app_exception('Invalid application mode');
				break;
		}
		$hook = self::$registry->add( \ionmvc\CLASS_TYPE_DEFAULT,'hook',new hook( $hooks ) );
		
		package::init();
		
		$hook->run('init');
		
		//hook to modify request config for initial
		
		//inital request
		$request = new request( $request_config );
		
		$request->handle();
		
		self::stop();
	}

	public static function stop( $code=0 ) {
		self::$registry->find( \ionmvc\CLASS_TYPE_DEFAULT,'hook' )->run('stop');
		exit( $code );
	}

	public static function mode( $mode=null ) {
		if ( is_null( $mode ) ) {
			return self::$mode;
		}
		return ( self::$mode === $mode );
	}

	public static function env( $env=null ) {
		if ( is_null( $env ) ) {
			return self::$env;
		}
		return ( self::$env === $env );
	}

	public static function __callStatic( $method,$args ) {
		return self::init( $method,$args );
	}

	public static function init( $name,$args=[] ) {
		$type = \ionmvc\CLASS_TYPE_DEFAULT;
		if ( ( $instance = self::$registry->find( $type,$name ) ) === false ) {
			$instance = autoloader::class_by_type( $name,$type,[
				'instance' => true,
				'args'     => $args
			] );
			if ( $instance === false ) {
				throw new app_exception( 'Unable to init class: %s',$name );
			}
			self::$registry->add( $type,$name,$instance );
		}
		return $instance;
	}

	public static function is_init( $class ) {
		return self::$registry->exists( \ionmvc\CLASS_TYPE_DEFAULT,$class );
	}

	public static function load_time() {
		if ( self::$end_time == 0 ) {
			self::$end_time = microtime(true);
		}
		return ( self::$end_time - self::$start_time );
	}

    public static function detect_ssl() {
		$https = input::server('HTTPS',false);
		if ( $https !== false && strtolower( $https ) === 'on' || (int) $https === 1 ) {
			return true;
		}
		return false;
	}

	public static function secure() {
		if ( !self::$config['ssl'] || self::detect_ssl() ) {
			return;
		}
		redirect::to_url( uri::current([
			'ssl' => true
		]) );
	}

	public static function non_secure() {
		if ( !self::detect_ssl() ) {
			return;
		}
		redirect::to_url( uri::current([
			'ssl' => false
		]) );
	}

	public static function log( $data ) {
		file_put_contents( path::get('storage-log') . config::get('log.app'),'[' . date('m-d-Y h:i:s') . "] - {$data}\n",\FILE_APPEND );
	}

}

?>