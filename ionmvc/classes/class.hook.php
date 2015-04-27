<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;

class hook {

	const position_first  = 1;
	const position_before = 2;
	const position_after  = 3;
	const position_last   = 4;

	private $hooks       = [];
	private $config      = [];
	private $attachments = [];

/*
	public static function __callStatic( $method,$args ) {
		$class = response::hook();
		$method = "_{$method}";
		if ( !method_exists( $class,$method ) ) {
			throw new app_exception( "Method '%s' does not exist",$method );
		}
		return call_user_func_array( array( $class,$method ),$args );
	}
*/

	public function __construct( $hooks=[] ) {
		if ( count( $hooks ) > 0 ) {
			$this->_add( $hooks );
		}
	}

	public function __call( $method,$args ) {
		$method = "_{$method}";
		if ( !method_exists( $this,$method ) ) {
			throw new app_exception( "Method '%s' not found",$method );
		}
		return call_user_func_array( [ $this,$method ],$args );
	}

	public function _add( $names,$config=[] ) {
		if ( !is_array( $names ) ) {
			$names = [ $names ];
		}
		if ( !isset( $config['position'] ) ) {
			$config['position'] = self::position_last;
		}
		if ( !isset( $config['config'] ) ) {
			$config['config'] = [];
		}
		if ( !isset( $config['config']['required'] ) || !is_bool( $config['config']['required'] ) ) {
			$config['config']['required'] = false;
		}
		foreach( $names as $name ) {
			switch( $config['position'] ) {
				case self::position_first:
					array_unshift( $this->hooks,$name );
				break;
				case self::position_before:
				case self::position_after:
					if ( !isset( $config['hook'] ) ) {
						throw new app_exception('Hook name is required');
					}
					if ( ( $key = array_search( $config['hook'],$this->hooks ) ) === false ) {
						throw new app_exception( 'Unable to find hook with name: %s',$config['hook'] );
					}
					$offset = ( $config['position'] == self::position_before ? $key : ( $key + 1 ) );
					array_splice( $this->hooks,$offset,0,$name );
				break;
				case self::position_last:
					$this->hooks[] = $name;
				break;
			}
			$this->config[$name] = $config['config'];
		}
	}

	public function _config( $name,array $config,$overwrite=false ) {
		$this->config[$name] = ( !$overwrite ? array_func::merge_recursive_distinct( $this->config[$name],$config ) : $config );
	}

	public function _is_required( $name ) {
		return $this->config[$name]['required'];
	}

	public function _exists( $name ) {
		return in_array( $name,$this->hooks );
	}

	public function _remove( $name ) {
		if ( ( $key = array_search( $name,$this->hooks ) ) === false ) {
			throw new app_exception( 'Unable to find hook with name: %s',$name );
		}
		unset( $this->hooks[$key] );
		$this->_detach_all( $name );
	}

	public function _remove_all() {
		$this->hooks = [];
		$this->_detach_all();
	}

	public function _attach( $hook,\Closure $function,$priority=5 ) {
		if ( !isset( $this->attachments[$hook] ) ) {
			$this->attachments[$hook] = [];
		}
		if ( !isset( $this->attachments[$hook][$priority] ) ) {
			$this->attachments[$hook][$priority] = [];
		}
		$i = count( $this->attachments[$hook][$priority] );
		$this->attachments[$hook][$priority][$i] = $function;
		return "{$hook}|{$priority}|{$i}";
	}

	public function _detach( $id ) {
		if ( strpos( $id,'|' ) === false ) {
			return false;
		}
		list( $hook,$priority,$i ) = explode( '|',$id );
		unset( $this->attachments[$hook][$priority][$i] );
	}

	public function _detach_all( $hook=null ) {
		if ( !is_null( $hook ) ) {
			if ( isset( $this->attachments[$hook] ) ) {
				unset( $this->attachments[$hook] );
			}
			return;
		}
		$this->attachments = [];
	}

	public function _run( $hook,$params=[],$value=false ) {
		if ( !in_array( $hook,$this->hooks ) ) {
			throw new app_exception( 'Unable to find hook with name: %s',$hook );
		}
		if ( !isset( $this->attachments[$hook] ) ) {
			return;
		}
		krsort( $this->attachments[$hook] );
		$params = array_merge( [ &$value ],$params );
		foreach( $this->attachments[$hook] as $priority => $functions ) {
			foreach( $functions as $function ) {
				call_user_func_array( $function,$params );
			}
		}
		return $value;
	}

	public function _run_all( $config=array() ) {
		$hooks = $this->hooks;
		if ( isset( $config['required_only'] ) && $config['required_only'] ) {
			$that = $this;
			$hooks = array_filter( $hooks,function( $hook ) use( $that ) {
				return $that->_is_required( $hook );
			} );
		}
		foreach( $hooks as $hook ) {
			$this->_run( $hook );
		}
	}

}

?>