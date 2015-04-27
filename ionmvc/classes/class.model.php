<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;
use ionmvc\traits\magic_vars;

class model {

	use magic_vars;

	public $db;
	public $table;

	final public static function new_instance( $name ) {
		$instance = autoloader::class_by_type( $name,\ionmvc\CLASS_TYPE_MODEL,array(
			'instance' => true
		) );
		if ( $instance === false ) {
			throw new app_exception( 'Unable to load model: %s',$name );
		}
		return $instance;
	}

	final public static function instance( $name,$varname=null ) {
		if ( is_null( $varname ) ) {
			$varname = str_replace( ['/'],'_',$name );
		}
		if ( ( $instance = app::$registry->find( \ionmvc\CLASS_TYPE_MODEL,$varname ) ) === false ) {
			$instance = self::new_instance( $name );
			app::$registry->add( \ionmvc\CLASS_TYPE_MODEL,$varname,$instance );
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

}

?>