<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;

class view {

	const overwrite = 1;
	const prepend   = 2;
	const append    = 3;

	private $name = null;
	private $path = null;
	private $vars = [];
	private $parent = null;
	private $rendered = false;

	final public static function register( $obj,$name,$varname=null ) {
		if ( is_null( $varname ) ) {
			$varname = str_replace( '/','_',$name );
		}
		$obj->{$varname.'_view'} = self::fetch( $name );
	}

	public static function fetch( $path,$data=null ) {
		return new self( $path,$data );
	}

	public static function __callStatic( $name,$params ) {
		return response::view_registry()->find( $name );
	}

	public function __construct( $path,$data=null,$config=[] ) {
		if ( !isset( $config['register'] ) && !response::exists() ) {
			$config['register'] = false;
		}
		$this->name = str_replace( '/','_',$path );
		if ( !isset( $config['register'] ) || $config['register'] ) {
			if ( ( $registry = response::registry()->find( \ionmvc\CLASS_TYPE_DEFAULT,'view_registry' ) ) === false ) {
				$registry = new view\registry;
				response::registry()->add( \ionmvc\CLASS_TYPE_DEFAULT,'view_registry',$registry );
			}
			$registry->add( $this->name,$this );
		}
		$this->path = $path;
		if ( !is_null( $data ) ) {
			$this->data( $data );
		}
	}

	public function __isset( $key ) {
		return isset( $this->vars[$key] );
	}

	public function __get( $key ) {
		if ( isset( $this->vars[$key] ) ) {
			return $this->vars[$key];
		}
		return false;
	}

	public function __set( $key,$value ) {
		$this->vars[$key] = $value;
	}

	public function __unset( $key ) {
		unset( $this->vars[$key] );
	}

	public function __toString() {
		try {
			$data = $this->render();
			return $data;
		}
		catch( \Exception $e ) {
			return $e->getMessage();
		}
	}

	public function parent( view $view ) {
		$this->parent = $view;
		return $this;
	}

	public function has_parent() {
		return !is_null( $this->parent );
	}

	public function rendered() {
		return $this->rendered;
	}

	public function set( $key,$value,$type=self::overwrite ) {
		$class = __CLASS__;
		if ( $value instanceof $class ) {
			$value->parent( $this );
		}
		if ( !isset( $this->vars[$key] ) || $type == self::overwrite ) {
			$this->vars[$key] = $value;
			return $this;
		}
		if ( is_array( $this->vars[$key] ) ) {
			switch( $type ) {
				case self::prepend:
					array_unshift( $this->vars[$key],$value );
				break;
				case self::append:
					$this->vars[$key][] = $value;
				break;
			}
			return $this;
		}
		$this->vars[$key] = ( $type == self::prepend ? $value . $this->vars[$key] : $this->vars[$key] . $value );
		return $this;
	}

	public function data( $data ) {
		if ( !is_array( $data ) ) {
			throw new app_exception('view::data() requires an array');
		}
		foreach( $data as $key => $value ) {
			$this->set( $key,$value );
		}
		return $this;
	}

	private function process_vars( $vars ) {
		foreach( $vars as $key => &$value ) {
			if ( $value instanceof \Closure ) {
				$value = $value();
			}
		}
		return $vars;
	}

	public function render() {
		extract( $this->process_vars( $this->vars ) );
		if ( ( $path = path::get("{$this->path}.php",'view') ) === false || !file_exists( $path ) ) {
			throw new app_exception( "Unable to find view '%s'",$this->path );
			return;
		}
		$this->rendered = true;
		ob_start();
		include $path;
		$data = ob_get_contents();
		ob_end_clean();
		return $data;
	}

}

?>