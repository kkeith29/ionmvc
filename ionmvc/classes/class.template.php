<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;

class template {

	const overwrite = 1;
	const prepend   = 2;
	const append    = 3;
	const merge     = 4;

	private $vars = [];
	private $path = null;

	public function __isset( $key ) {
		return isset( $this->vars[$key] );
	}

	public function __get( $key ) {
		return ( isset( $this->vars[$key] ) ? $this->vars[$key] : null );
	}

	public function __set( $key,$value ) {
		$this->vars[$key] = $value;
	}

	public function __unset( $key ) {
		if ( !isset( $this->vars[$key] ) ) {
			return;
		}
		unset( $this->vars[$key] );
	}

	public function __toString() {
		return $this->render();
	}

	public function set( $key,$value,$action=self::overwrite ) {
		if ( $action !== self::overwrite && !isset( $this->vars[$key] ) ) {
			$action = self::overwrite;
		}
		switch( $action ) {
			case self::overwrite:
				$this->vars[$key] = $value;
				break;
			case self::prepend:
				$this->vars[$key] = $value . $this->vars[$key];
				break;
			case self::append:
				$this->vars[$key] .= $value;
				break;
			default:
				throw new app_exception('Invalid action');
				break;
		}
		return $this;
	}

	public function vars( $vars,$action=self::overwrite ) {
		switch( $action ) {
			case self::overwrite:
				$this->vars = $vars;
				break;
			case self::merge:
				$this->vars = array_merge( $this->vars,$vars );
				break;
			default:
				throw new app_exception('Invalid action');
				break;
		}
		return $this;
	}

	public function path( $path ) {
		$this->path = $path . '.tpl';
		return $this;
	}

	public function render() {
		if ( is_null( $this->path ) ) {
			throw new app_exception('Template path is required');
		}
		$path = path::template( $this->path  );
		if ( !file_exists( $path ) ) {
			throw new app_exception( "Template '%s' does not exist",$this->path );
		}
		if ( ( $data = file_get_contents( $path ) ) === false ) {
			throw new app_exception( "Unable to get contents of template: %s",$this->path );
		}
		$keys = array_map( function( $value ) {
			return '{{' . strtoupper( $value ) . '}}';
		},array_keys( $this->vars ) );
		return str_replace( $keys,array_values( $this->vars ),$data );
	}

	public static function fetch( $path,$vars=[] ) {
		$templ = new self;
		return $templ->path( $path )->vars( $vars )->render();
	}

}

?>