<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;
use ionmvc\exceptions\load as load_exception;

class request {

	const mode_uri = 1;
	const mode_cli = 2;

	const method_get  = 1;
	const method_post = 2;

	const type_controller = 1;
	const type_closure    = 2;
	const type_command    = 3;
	const type_request    = 4;

	private static $instances = [];
	private static $instance_idx = null;

	protected $mode;
	protected $method;
	protected $type;
	protected $input = null;
	protected $resource_id = null;
	protected $info = false;
	protected $response = null;

	protected $registry = null;

	public static function __callStatic( $method,$args ) {
		if ( is_null( self::$instance_idx ) ) {
			throw new app_exception('No request found');
		}
		$class = self::$instances[self::$instance_idx];
		$_method = "_{$method}";
		if ( method_exists( $class,$_method ) ) {
			return call_user_func_array( [ $class,$_method ],$args );
		}
		$type = \ionmvc\CLASS_TYPE_DEFAULT;
		if ( ( $instance = $class->_registry()->find( $type,$method ) ) === false ) {
			$instance = autoloader::class_by_type( $method,$type,[
				'instance' => true,
				'args'     => $args
			] );
			if ( $instance === false ) {
				throw new app_exception( 'Unable to find method/initialize class: %s',$method );
			}
			$class->_registry()->add( $type,$method,$instance );
		}
		return $instance;
	}

	public function __construct( $config=[] ) {
		if ( !isset( $config['mode'] ) ) {
			throw new app_exception('Request mode is required');
		}
		self::$instance_idx = count( self::$instances );
		self::$instances[self::$instance_idx] = $this;
		$this->mode = $config['mode'];
		switch( $this->mode ) {
			case self::mode_uri:
				if ( isset( $config['use_globals'] ) && $config['use_globals'] ) {
					$config['input'] = [
						'server' => $_SERVER,
						'get'    => $_GET,
						'post'   => $_POST,
						'files'  => $_FILES,
						'cookie' => $_COOKIE
					];
					break;
				}
				break;
			case self::mode_cli:
				if ( isset( $config['use_globals'] ) && $config['use_globals'] ) {
					$config['input'] = [
						'server' => $_SERVER
					];
					$config['cli'] = [
						'in'    => STDIN,
						'out'   => STDOUT,
						'error' => STDERR
					];
					break;
				}
				if ( !isset( $config['cli'] ) ) {
					$config['cli'] = [];
				}
				break;
			default:
				throw new app_exception('Invalid request mode');
				break;
		}
		if ( !isset( $config['input'] ) ) {
			$config['input'] = [];
		}
		$this->input = new input( $config['input'] );
		$this->method = self::method_get;
		if ( ( $method = $this->input->server('REQUEST_METHOD') !== false ) ) {
			switch( $method ) {
				case 'GET':
					$this->method = self::method_get;
					break;
				case 'POST':
					$this->method = self::method_post;
					break;
				default:
					//add in hook eventually so others can define their own request methods (PUT, DELETE, etc)
					throw new app_exception( 'Unsupported request method: %s',$method );
					break;
			}
		}
		$this->registry = new registry;
		$this->registry->add( \ionmvc\CLASS_TYPE_DEFAULT,'hook',new hook(['destruct']) );
		switch( $this->mode ) {
			case self::mode_uri:
				$this->resource_id = new uri( $this->input );
				if ( !isset( $config['type'] ) ) {
					break;
				}
				$this->info = [
					'type' => $config['type']
				];
				switch( $config['type'] ) {
					case self::type_controller:
						if ( !isset( $config['controller'] ) ) {
							throw new app_exception('Controller request type requires a controller name, come on son');
						}
						$this->info['controller'] = $config['controller'];
						$this->info['action']     = ( isset( $config['action'] ) ? $config['action'] : app::$config['action']['default'] );
						$this->info['params']     = ( isset( $config['params'] ) ? $config['params'] : [] );
						break;
					case self::type_closure:
						if ( !isset( $config['closure'] ) ) {
							throw new app_exception('Closure request type requires a closure, really...');
						}
						$this->info['closure']    = $config['closure'];
						$this->info['args']       = ( isset( $config['args'] ) ? $config['args'] : [] );
						break;
					default:
						throw new app_exception('Invalid request type for mode');
						break;
				}
				break;
			case self::mode_cli:
				$this->resource_id = new cli( $this->input,$config['cli'] );
				break;
		}
		if ( $this->info === false ) {
			//request isn't specific enough, so we are going to have to pull the info from the resource_id
			if ( !isset( $config['router'] ) || !( $config['router'] instanceof router ) ) {
				throw new app_exception('Request requires a router if type isn\'t specified');
			}
			$info = $config['router']->request( $this )->go();
			if ( isset( $info['response'] ) ) {
				$this->response = $info['response'];
			}
			else {
				$this->info = $info;
			}
		}
		app::hook()->run('request.create',[
			&$this
		]);
	}

	public function __call( $method,$args ) {
		$method = "_{$method}";
		if ( !method_exists( $this,$method ) ) {
			throw new app_exception( "Method '%s' not found",$method );
		}
		return call_user_func_array( [ $this,$method ],$args );
	}

	public function handle() {
		if ( is_null( $this->response ) ) {
			switch( $this->info['type'] ) {
				case self::type_controller:
					$directories = explode( '/',$this->info['controller'] );
					$controller = array_pop( $directories );
					$class_id = ( count( $directories ) > 0 ? implode( '/',$directories ) . '/' : '' ) . $controller;
					if ( ( $class_name = autoloader::class_by_type( $class_id,\ionmvc\CLASS_TYPE_CONTROLLER ) ) === false ) {
						$this->response = response::not_found();
						break;
					}
					$name = ( count( $directories ) > 0 ? str_replace( '_','-',implode( '/',$directories ) ) . '/' : '' ) . str_replace( '_','-',$controller );
					$oaction = $this->info['action'];
					$action = "{$this->info['action']}_action";
					$oaction = str_replace( '_','-',$oaction );
					if ( ( $methods = get_class_methods( $class_name ) ) === null || ( !in_array( '_remap',$methods ) && !in_array( $action,$methods ) ) ) {
						$this->response = response::not_found();
						break;
					}
					$this->response = new response;
					$params = [];
					if ( isset( $this->info['params'] ) ) {
						$params = $this->info['params'];
					}
					$this->info['name']     = $name;
					$this->info['class']    = $class_name;
					$this->info['action']   = $oaction;
					$this->info['method']   = $action;
					$this->info['instance'] = new $class_name;
					if ( in_array( '_before',$methods ) ) {
						$retval = call_user_func_array( [ $this->info['instance'],'_before' ],$params );
						if ( $retval instanceof response ) {
							$this->response = $retval;
							break;
						}
					}
					if ( in_array( '_remap',$methods ) ) {
						$retval = $this->info['instance']->_remap( $oaction,$params );
					}
					else {
						$retval = call_user_func_array( [ $this->info['instance'],$action ],$params );
					}
					if ( $retval instanceof response ) {
						$this->response = $retval;
						break;
					}
					if ( in_array( '_after',$methods ) ) {
						$retval = call_user_func_array( [ $this->info['instance'],'_after' ],$params );
						if ( $retval instanceof response ) {
							$this->response = $retval;
							break;
						}
					}
					break;
				case self::type_closure:
					$this->response = new response;
					$retval = call_user_func_array( $this->info['closure'],$this->info['args'] );
					if ( $retval instanceof response ) {
						$this->response = $retval;
						break;
					}
					break;
				case self::type_command:
					if ( $this->info['command'] === false ) {
						cli::line('Unable to find command');
						return;
					}
					$command = autoloader::class_by_type( $this->info['command'],\ionmvc\CLASS_TYPE_COMMAND,[
						'instance' => true
					] );
					$method = "{$this->info['method']}_command";
					if ( !method_exists( $command,$method ) ) {
						cli::line('Unable to find command');
						return;
					}
					call_user_func( [ $command,$method ] );
					$this->_destruct();
					return;
			}
		}
		$this->_destruct();
		$this->response->handle();
	}

	public function _destruct() {
		$hook = $this->registry->find( \ionmvc\CLASS_TYPE_DEFAULT,'hook' );
		$hook->run('destruct',[
			&$this
		]);
		app::hook()->run('request.destruct',[
			&$this
		]);
	}

	public function _mode() {
		return $this->mode;
	}

	public function _method() {
		return $this->method;
	}

	public function _type() {
		return $this->type;
	}

	public function _resource_id( $resource_id=null ) {
		if ( !is_null( $resource_id ) ) {
			$this->resource_id = $resource_id;
		}
		return $this->resource_id;
	}

	public function _input() {
		return $this->input;
	}

	public function _registry() {
		return $this->registry;
	}

}

?>