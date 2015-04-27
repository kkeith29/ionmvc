<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;

class response {

	protected static $instances = [];
	protected static $instance_idx = null;

	protected $registry;

	public static function __callStatic( $method,$args ) {
		if ( is_null( self::$instance_idx ) ) {
			throw new app_exception('No response found');
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

	public static function exists() {
		return !is_null( self::$instance_idx );
	}

	public static function not_found( \Closure $func=null ) {
		$response = new static;
		http::status_code(404);
		if ( !is_null( $func ) ) {
			call_user_func( $func );
		}
		elseif ( event::exists('response.not_found') ) {
			event::trigger('response.not_found');
		}
		else {
			//load default view here
			view::fetch('ionmvc-view:error/404');
		}
		return $response;
	}

	public function __construct() {
		self::$instance_idx = count( self::$instances );
		self::$instances[self::$instance_idx] = $this;
		$this->registry = new registry;
		$this->setup();
		app::hook()->run('response.create',[
			&$this
		]);
	}

	protected function setup() {
		$this->registry->add( \ionmvc\CLASS_TYPE_DEFAULT,'hook',new hook(['main','view','view_output','http','output']) );
	}

	public function _registry() {
		return $this->registry;
	}

	public function handle() {
		if ( ( $hook = $this->registry->find( \ionmvc\CLASS_TYPE_DEFAULT,'hook' ) ) !== false ) {
			$hook->run_all();
		}
	}

}

?>