<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;

class model {

	protected $data = array();

	public $db;
	public $table;

	final public static function instance( $name,$varname=null ) {
		if ( is_null( $varname ) ) {
			$varname = str_replace( array('/','__'),'_',$name );
		}
		if ( ( $instance = app::$registry->find( registry::model,$varname ) ) === false ) {
			$instance = autoloader::class_by_type( $name,\ionmvc\CLASS_TYPE_MODEL,array(
				'instance' => true
			) );
			if ( $instance === false ) {
				throw new app_exception( 'Unable to load model: %s',$name );
			}
			app::$registry->add( registry::model,$varname,$instance );
		}
		return $instance;
	}

	final public static function register( $obj,$name,$varname=null ) {
		if ( is_null( $varname ) ) {
			$varname = str_replace( '/','_',$name );
		}
		$instance = self::instance( $name,$varname );
		$obj->{$varname.'_model'} = $instance;
	}

	public static function __callStatic( $class,$args ) {
		return self::instance( $class );
	}

	public function __construct( $table=null ) {
		$this->db = ( isset( $this->connection ) ? db::connection( $this->connection ) : app::db()->current() );
		if ( !is_null( $table ) ) {
			$this->table = $this->db->table( $table );
		}
	}

	public function __isset( $key ) {
		return isset( $this->data[$key] );
	}

	public function __get( $key ) {
		return ( array_key_exists( $key,$this->data ) ? $this->data[$key] : null );
	}

	public function __set( $key,$value ) {
		$this->data[$key] = $value;
	}

	public function __unset( $key ) {
		unset( $this->data[$key] );
	}

}

?>