<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;

class router {

	const type_rewrite    = 1;
	const type_controller = 2;
	const type_closure    = 3;

	private static $instance = null;

	protected $request = null;
	protected $routes = [];

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	public static function __callStatic( $method,$args ) {
		$class = self::instance();
		$method = "_{$method}";
		if ( !method_exists( $class,$method ) ) {
			throw new app_exception( "Method '%s' not found",$method );
		}
		return call_user_func_array( [ $class,$method ],$args );
	}

	public function request( request $request ) {
		$this->request = $request;
		return $this;
	}

	public function go() {
		//handle the routing here and return the data it needs to handle this shit
		switch( $this->request->mode() ) {
			case request::mode_uri:
				$uri = $this->request->resource_id()->all_segments();
				if ( count( $this->routes ) > 0 ) {
					$uri_string = ( count( $uri['segments'] ) > 0 ? implode( '/',$uri['segments'] ) : '/' );
					foreach( $this->routes as $route ) {
						if ( !isset( $route['config']['type'] ) ) {
							throw new app_exception('Route type is required');
						}
						$route['path'] = str_replace( array(':num',':any',':all'),array('[0-9]+','[^/]+','.*?'),$route['path'] );
						if ( preg_match( "#^{$route['path']}$#",$uri_string,$match ) !== 1 ) {
							continue;
						}
						switch( $route['config']['type'] ) {
							case self::type_rewrite:
								if ( $route['path_new'] instanceof \Closure ) {
									$route['path_new'] = call_user_func( $route['path_new'],$uri_string,$match );
								}
								if ( strpos( $route['path_new'],'$' ) !== false && strpos( $route['path'],'(' ) !== false ) {
									$route['path_new'] = preg_replace( "#^{$route['path']}$#",$route['path_new'],$uri_string );
								}
								var_dump( $route );
								exit;
								$uri = new uri( $this->request->input(),[
									'uri' => $route['path_new']
								] );
								$this->request->resource_id( $uri );
								break 2;
							case self::type_controller:
								if ( $route['controller'] instanceof \Closure ) {
									$route['controller'] = call_user_func( $route['controller'],$uri_string,$match );
								}
								list( $controller,$action ) = explode( ':',$route['controller'],2 );
								$params = [];
								if ( isset( $route['config']['params'] ) ) {
									$params = $route['config']['params'];
								}
								return [
									'type'       => request::type_controller,
									'controller' => $controller,
									'action'     => $action,
									'params'     => $params
								];
								break;
							case self::type_closure:
								return [
									'type'    => request::type_closure,
									'closure' => $route['closure'],
									'args'    => [
										$uri_string,
										$match
									]
								];
								break;
						}
					}
				}
				$segments = array_values( $this->request->resource_id()->segments() );
				$controller = $action = '';
				$params = $subdirs = [];
				$segment_count = count( $segments );
				if ( $segment_count === 0 ) {
					$controller = app::$config['controller']['default'];
					$action     = app::$config['action']['default'];
				}
				else {
					$subdirs = array_map( function( $value ) {
						return str_replace( '_','-',$value );
					},$segments );
					$i = 0;
					foreach( array_reverse( $subdirs,true ) as $i => $subdir ) {
						unset( $subdirs[$i] );
						if ( autoloader::class_by_type( ( count( $subdirs ) > 0 ? implode( '/',$subdirs ) . '/' : '' ) . str_replace( '-','_',$subdir ),\ionmvc\CLASS_TYPE_CONTROLLER ) === false ) {
							continue;
						}
						break;
					}
					$controller = ( count( $subdirs ) > 0 ? implode( '/',$subdirs ) . '/' : '' ) . str_replace( '-','_',$segments[$i++] );
					$action     = ( isset( $segments[$i] ) ? str_replace( '-','_',$segments[$i] ) : app::$config['action']['default'] );
					if ( $segment_count > $i ) {
						$params = array_slice( $segments,++$i );
					}
				}
				return [
					'type'       => request::type_controller,
					'controller' => $controller,
					'action'     => $action,
					'params'     => $params
				];
				break;
			case request::mode_cli:
				$retval = [
					'type'    => request::type_command,
					'command' => false
				];
				if ( ( $command = $this->request->resource_id()->arg(0,false) ) === false ) {
					return $retval;
				}
				$parts = array_map( function( $value ) {
					return str_replace( '-','_',$value );
				},explode( ':',$command ) );
				$method = 'main';
				while( count( $parts ) > 0 ) {
					$class = implode( '/',$parts );
					if ( autoloader::class_by_type( $class,\ionmvc\CLASS_TYPE_COMMAND ) !== false ) {
						$retval['command'] = $class;
						$retval['method']  = $method;
						return $retval;
					}
					$method = array_pop( $parts );
				}
				return $retval;
				break;
		}
	}

	protected function add( $info ) {
		if ( !isset( $info['config']['method'] ) ) {
			$info['config']['method'] = request::method_get;
		}
		$this->routes[] = $info;
	}

	public function _rewrite( $path,$path_new,$config=[] ) {
		$config['type'] = self::type_rewrite;
		$this->add( compact('path','path_new','config') );
	}

	public function _to_controller( $path,$controller,$config=[] ) {
		$config['type'] = self::type_controller;
		$this->add( compact('path','controller','config') );
	}

	public function _to_closure( $path,\Closure $closure,$config=[] ) {
		$config['type'] = self::type_closure;
		$this->add( compact('path','closure','config') );
	}

}

?>