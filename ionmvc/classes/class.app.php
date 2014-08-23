<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;
use ionmvc\exceptions\load as load_exception;

class app {

	const position_first  = 1;
	const position_before = 2;
	const position_after  = 3;
	const position_last   = 4;

	public static $data;
	public static $registry;

	private static $config = array(
		'default_controller' => 'index',
		'default_action'     => 'index'
	);
	private static $start_time = 0;
	private static $end_time = 0;
	private static $hook_registry = array();
	private static $hooks = array('main','view','asset','html_header','view_output','html_footer','http','output');
	private static $required = array('http','output','session','db');

	private static $states = array();
	private static $state = 0;

	private static $controller = false;

	private static function init_state( $persist=false,$save=false ) {
		if ( $save === true ) {
			self::save_state();
		}
		self::$state++;
		if ( $persist === false ) {
			self::$data = new igsr;
			self::$registry = new registry;
			self::$hook_registry = array();
			app::init('event')->init();
			output::init();
		}
	}

	private static function save_state() {
		self::$states[self::$state] = array(
			'controller'    => self::$controller,
			'data'          => self::$data,
			'registry'      => self::$registry,
			'hook_registry' => self::$hook_registry
		);
	}

	private static function restore_state( $state ) {
		self::$controller    = self::$states[$state]['controller'];
		self::$data          = self::$states[$state]['data'];
		self::$registry      = self::$states[$state]['registry'];
		self::$hook_registry = self::$states[$state]['hook_registry'];
		unset( self::$states[$state] );
		self::$state = $state;
	}

	public static function initialize() {
		self::$start_time = microtime(true);
		config::load('constants.php');
		error::init();
		time::init();
		self::init_state();
		if ( config::get('package.enabled') === true ) {
			package::init();
		}
		if ( config::get('framework.mvc_load') === true ) {
			router::init();
			$data = router::route_data();
			if ( $data !== false ) {
				output::check_cache();
				try {
					self::load_controller( $data['controller'],$data['action'],array(
						'params' => $data['params']
					) );
				}
				catch( load_exception $e ) {
					error::show(404);
				}
				catch( app_exception $e ) {
					throw $e;
				}
			}
			self::destruct();
			return;
		}
		register_shutdown_function('\\ionmvc\\classes\\app::destruct');
	}

	public static function load_controller( $controller,$action,$config=array() ) {
		$params = ( isset( $config['params'] ) ? $config['params'] : array() );
		$directories = explode( '/',$controller );
		$controller = array_pop( $directories );
		$class_id = ( count( $directories ) > 0 ? implode( '/',$directories ) . '/' : '' ) . $controller;
		if ( ( $class_name = autoloader::class_by_type( $class_id,\ionmvc\CLASS_TYPE_CONTROLLER ) ) === false ) {
			throw new load_exception( 'Unable to load controller: %s',$controller );
		}
		//$class_name = str_replace( '.','\\',$class_name );
		$name = ( count( $directories ) > 0 ? str_replace( '_','-',implode( '/',$directories ) ) . '/' : '' ) . str_replace( '_','-',$controller );
		$oaction = $action;
		$action = "{$action}_action";
		$oaction = str_replace( '_','-',$oaction );
		if ( ( $methods = get_class_methods( $class_name ) ) === null || ( !in_array( '_remap',$methods ) && !in_array( $action,$methods ) ) ) {
			throw new load_exception( "Unable to find action '%s' in controller '%s'",$action,$controller );
		}
		if ( isset( $config['test'] ) && $config['test'] === true ) {
			return true;
		}
		//think of how to do persist only
		$init = ( self::$controller === false ? true : false );
		if ( $init !== true ) {
			if ( ( !isset( $config['persist_state'] ) || $config['persist_state'] === false ) && ( !isset( $config['final'] ) || $config['final'] === false ) ) {
				throw new app_exception('Starting a new state that does not persist and is not final is not allowed');
			}
		}
		if ( $init !== true && ( !isset( $config['persist_state'] ) || $config['persist_state'] === false ) ) {
			$prev_state = self::$state;
			self::init_state( false,true );
		}
		self::$controller = array(
			'name'   => $name,
			'class'  => $class_name,
			'action' => $oaction,
			'method' => $action,
			'params' => $params
		);
		self::$controller['instance'] = new $class_name;
		if ( in_array( '_before',$methods ) ) {
			call_user_func_array( array( self::$controller['instance'],'_before' ),$params );
		}
		if ( in_array( '_remap',$methods ) ) {
			self::$controller['instance']->_remap( $oaction,$params );
		}
		else {
			call_user_func_array( array( self::$controller['instance'],$action ),$params );
		}
		if ( in_array( '_after',$methods ) ) {
			call_user_func_array( array( self::$controller['instance'],'_after' ),$params );
		}
		if ( $init !== true && ( !isset( $config['persist_state'] ) || $config['persist_state'] === false ) ) {
			self::destruct(( isset( $config['final'] ) && $config['final'] === true ? true : false ));
			self::restore_state( $prev_state );
		}
	}

	public static function controller( $part=false ) {
		$data = self::$controller;
		if ( $part === false ) {
			return $data;
		}
		if ( !isset( $data[$part] ) ) {
			throw new app_exception( 'Controller data for %s could not be found',$part );
		}
		return $data[$part];
	}

	public static function action() {
		return self::$controller['action'];
	}

	public static function params() {
		return self::$controller['params'];
	}

	public static function param( $index,$return=false ) {
		$index = ( $index <= 0 ? 0 : ( $index - 1 ) );
		return ( isset( self::$controller['params'][$index] ) ? self::$controller['params'][$index] : $return );
	}

	public static function hook_add( $name,$position=self::position_last,$hook=null ) {
		switch( $position ) {
			case self::position_first:
				array_unshift( self::$hooks,$name );
			break;
			case self::position_before:
			case self::position_after:
				if ( is_null( $hook ) ) {
					throw new app_exception('Hook name is required');
				}
				if ( ( $key = array_search( $hook,self::$hooks ) ) === false ) {
					throw new app_exception( 'Unable to find hook with name: %s',$hook );
				}
				$offset = ( $position == self::position_before ? $key : ( $key + 1 ) );
				array_splice( self::$hooks,$offset,0,$name );
			break;
			case self::position_last:
				self::$hooks[] = $name;
			break;
		}
	}

	public static function register( $hook,\Closure $function,$priority=5 ) {
		if ( !isset( self::$hook_registry[$hook] ) ) {
			self::$hook_registry[$hook] = array();
		}
		if ( !isset( self::$hook_registry[$hook][$priority] ) ) {
			self::$hook_registry[$hook][$priority] = array();
		}
		$i = count( self::$hook_registry[$hook][$priority] );
		self::$hook_registry[$hook][$priority][$i] = $function;
		return "{$hook}|{$priority}|{$i}";
	}

	public static function unregister( $id ) {
		if ( strpos( $id,'|' ) === false ) {
			return false;
		}
		list( $hook,$priority,$i ) = explode( '|',$id );
		unset( self::$hook_registry[$hook][$priority][$i] );
	}

	public static function clear_hook_registry() {
		self::$hook_registry = array();
	}

	private static function _run( $hooks ) {
		foreach( $hooks as $hook ) {
			if ( isset( self::$hook_registry[$hook] ) ) {
				krsort( self::$hook_registry[$hook] );
				foreach( self::$hook_registry[$hook] as $priority => $functions ) {
					foreach( $functions as $function ) {
						$function();
					}
				}
			}
		}
	}

	public static function destruct() {
		self::_run( self::$hooks );
		self::_exit();
	}

	public static function terminate() {
		self::_run( self::$required );
	}

	public static function _exit() {
		exit;
	}

	public static function __callStatic( $method,$args ) {
		return self::init( $method,$args );
	}

	public static function init( $name,$args=array() ) {
		if ( ( $instance = self::$registry->find( registry::app,$name ) ) === false ) {
			$instance = autoloader::class_by_type( $name,\ionmvc\CLASS_TYPE_DEFAULT,array(
				'instance' => true,
				'args'     => $args
			) );
			if ( $instance === false ) {
				throw new app_exception( 'Unable to init class: %s',$name );
			}
			self::$registry->add( registry::app,$name,$instance );
			/*try {
				$class = '\\' . str_replace( '.','\\',autoloader::class_by_type( $name,'ionmvc.class' ) );
				if ( is_null( $vars ) ) {
					$instance = new $class;
				}
				else {
					$instance = new $class( $vars );
				}
				self::$registry->add( registry::app,$name,$instance );
			}
			catch( app_exception $e ) {
				throw new app_exception( "Unable to init class '%s' - Reason: %s",$name,$e->getMessage() );
			}*/
		}
		return $instance;
	}

	public static function is_init( $class ) {
		return self::$registry->exists( registry::app,$class );
	}

	public static function load_time() {
		if ( self::$end_time == 0 ) {
			self::$end_time = microtime(true);
		}
		return ( self::$end_time - self::$start_time );
	}

    public static function detect_ssl() {
		if ( isset( $_SERVER['HTTPS'] ) ) {
			if ( strtolower( $_SERVER['HTTPS'] ) == 'on' || $_SERVER['HTTPS'] == 1 || $_SERVER['SERVER_PORT'] == 443 ) {
				return true;
			}
		}
		return false;
	}

	public static function secure() {
		if ( config::get('SSL') == true && !self::detect_ssl() ) {
			redirect::to_url( uri::current(array('ssl'=>true)) );
		}
	}

	public static function non_secure() {
		if ( self::detect_ssl() ) {
			redirect::to_url( uri::current(array('ssl'=>false)) );
		}
	}

	public static function log( $data ) {
		file_put_contents( path::get('storage-log') . config::get('log.app'),'[' . date('m-d-Y h:i:s') . "] - {$data}\n",FILE_APPEND );
	}

}

?>