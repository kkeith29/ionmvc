<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;

class event {

	const stop = 'ionmvc.event.stop';
	const last = 'ionmvc.event.last';

	private $events = [];

	public static function __callStatic( $method,$args ) {
		$class = app::event();
		$method = "_{$method}";
		if ( !method_exists( $class,$method ) ) {
			throw new app_exception( "Method '%s' does not exist",$method );
		}
		return call_user_func_array( [ $class,$method ],$args );
	}

	public function __call( $method,$args ) {
		$method = "_{$method}";
		if ( !method_exists( $this,$method ) ) {
			throw new app_exception( "Method '%s' does not exist",$method );
		}
		return call_user_func_array( [ $this,$method ],$args );
	}

	public function _trigger( $name,$args=[] ) {
		if ( !isset( $this->events[$name] ) ) {
			return false;
		}
		foreach( $this->events[$name] as $event ) {
			$retval = call_user_func_array( $event['function'],$args );
			if ( $retval === self::stop ) {
				break;
			}
			if ( $retval === self::last ) {
				app::stop();
				break;
			}
		}
	}

	public function _bind( $name,\Closure $function,$config=[] ) {
		if ( !isset( $this->events[$name] ) ) {
			$this->events[$name] = [];
		}
		$this->events[$name][] = compact('function','config');
	}

	public function _exists( $name ) {
		return isset( $this->events[$name] );
	}

}

?>