<?php

namespace ionmvc\classes\asset;

use ionmvc\classes\asset;
use ionmvc\classes\app_exception;

class order {

	private $data = array();
	private $groups = array();
	private $group = array();

	private $idx = 0;

	public function __construct( $data ) {
		$this->data = $data;
	}

	private function type( $data ) {
		if ( isset( $data['func'] ) ) {
			return asset::type_function;
		}
		return ( $data['url'] === true ? asset::type_external : asset::type_internal );
	}

	private function next( $idx ) {
		$idx++;
		if ( !isset( $this->data[$idx] ) ) {
			return false;
		}
		return $this->data[$idx];
	}

	private function group_add( $data ) {
		if ( $this->group === false ) {
			throw new app_exception('No group set');
		}
		$this->group[] = $data;
	}

	private function group_clear( $type ) {
		$this->groups[] = array(
			'type' => $type,
			'assets' => $this->group
		);
		$this->group = array();
	}

	public function reorder() {
		foreach( $this->data as $idx => $data ) {
			$type = $this->type( $data );
			$this->group_add( $data );
			if ( ( $next = $this->next( $idx ) ) === false || $this->type( $next ) !== $type ) {
				$this->group_clear( $type );
			}
		}
		return $this->groups;
	}

}

?>