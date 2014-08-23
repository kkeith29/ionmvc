<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;
use ionmvc\exceptions\autoloader as autoloader_exception;
use ionmvc\exceptions\load_exception;

class router {

	const method_get = 'GET';
	const method_post = 'POST';

	const type_controller = 1;
	const type_uri = 2;

	const stop = 'ionmvc.router.stop';

	private static $config = array();
	private static $routes = array();
	private static $data = false;

	public static function init() {
		self::$config['default_controller'] = config::get('framework.controller.default');
		self::$config['default_action']     = config::get('framework.action.default');
		config::load('routes.php');
		//check for matching routes otherwise use default
		$data = array();
		if ( count( self::$routes ) > 0 ) {
			$uri = uri::get_path(true);
			$uri = ( count( $uri['segments'] ) > 0 ? implode( '/',$uri['segments'] ) : '/' );
			foreach( self::$routes as $route ) {
				$route['path'] = str_replace( array(':num',':any',':all'),array('[0-9]+','[^/]+','.+'),$route['path'] );
				if ( preg_match( "#^{$route['path']}$#",$uri,$match ) ) {
					if ( !isset( $route['config']['type'] ) ) {
						throw new app_exception('Route type is required');
					}
					if ( is_callable( $route['to'] ) ) {
						$route['to'] = call_user_func( $route['to'],$uri,$match );
					}
					if ( $route['to'] === true ) {
						continue;
					}
					if ( $route['to'] === false ) {
						break;
					}
					if ( $route['to'] === self::stop ) {
						return;
					}
					switch( $route['config']['type'] ) {
						case self::type_controller:
							list( $data['controller'],$data['action'] ) = explode( ':',$route['to'],2 );
							$data['params'] = array();
							if ( isset( $route['config']['params'] ) ) {   
								$data['params'] = $route['config']['params'];
							}
						break;
						case self::type_uri:
							if ( strpos( $route['to'],'$' ) !== false && strpos( $route['path'],'(' ) !== false ) {
								$route['to'] = preg_replace( "#^{$route['path']}$#",$route['to'],$route['path'] );
							}
							app::uri()->init( $route['to'] );
						break;
					}
					break;
				}
			}
		}
		if ( !isset( $data['controller'] ) && !isset( $data['action'] ) ) {
			$data = self::get_setup_data( uri::get_segments() );
			if ( $data === false ) {
				error::show(404);
				return;
			}
		}
		self::$data = $data;
	}

	public static function get_setup_data( $segments,$load_test=false ) {
		if ( !is_array( $segments ) ) {
			$segments = explode( '/',$segments );
		}
		$segments = array_values( $segments );
		$retval = array(
			'controller' => '',
			'action'     => '',
			'params'     => array()
		);
		$seg_count = count( $segments );
		$subdirs = array();
		if ( $seg_count === 0 ) {
			$retval['controller'] = self::$config['default_controller'];
			$retval['action'] = self::$config['default_action'];
		}
		else {
			foreach( $segments as $seg ) {
				if ( preg_match( '/^[a-zA-Z0-9_\-]+$/',$seg ) === 1 ) {
					$subdirs[] = str_replace( '_','-',$seg );
					continue;
				}
				break;
			}
			$i = 0;
			$rsubdirs = array_reverse( $subdirs,true );
			foreach( $rsubdirs as $i => $subdir ) {
				unset( $subdirs[$i] );
				$subdir = str_replace( '-','_',$subdir );
				$class_name = autoloader::class_by_type( ( count( $subdirs ) > 0 ? implode( '/',$subdirs ) . '/' : '' ) . $subdir,\ionmvc\CLASS_TYPE_CONTROLLER );
				if ( $class_name === false ) {
					continue;
				}
				break;
			}
			$retval['controller'] = $segments[$i];
			$i++;
			$retval['action'] = ( isset( $segments[$i] ) ? $segments[$i] : self::$config['default_action'] );
			if ( $seg_count > $i ) {
				$retval['params'] = array_slice( $segments,++$i );
			}
		}
		$retval['controller'] = str_replace( '-','_',( count( $subdirs ) > 0 ? implode( '/',$subdirs ) . '/' : '' ) . $retval['controller'] );
		$retval['action'] = str_replace( '-','_',$retval['action'] );
		if ( in_array( $retval['controller'],config::get('framework.controller.reserved') ) || in_array( $retval['action'],config::get('framework.action.reserved') ) ) {
			return false;
		}
		if ( $load_test === true ) {
			try {
				app::load_controller( $retval['controller'],$retval['action'],$retval['params'],true );
			}
			catch ( load_exception $e ) {
				return false;
			}
			catch ( app_exception $e ) {
				throw $e;
			}
		}
		return $retval;
	}

	public static function route_data() {
		return self::$data;
	}

	public static function add( $path,$to,$config=array() ) {
		if ( !isset( $config['method'] ) ) {
			$config['method'] = self::method_get;
		}
		self::$routes[] = compact('path','to','config');
	}

	public static function controller( $path,$to,$config=array() ) {
		$config['type'] = self::type_controller;
		self::add( $path,$to,$config );
	}

	public static function uri( $path,$to,$config=array() ) {
		$config['type'] = self::type_uri;
		self::add( $path,$to,$config );
	}

}

?>