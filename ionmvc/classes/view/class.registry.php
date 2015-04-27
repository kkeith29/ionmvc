<?php

namespace ionmvc\classes\view;

use ionmvc\classes\output;
use ionmvc\classes\response;
use ionmvc\classes\view;
use ionmvc\exceptions\app as app_exception;

class registry {

	private $views          = [];
	private $compiled_views = [];

	public function __construct() {
		response::hook()->attach('view',function() {
			response::view_registry()->compile();
		});
		response::hook()->attach('view_output',function() {
			response::view_registry()->output();
		});
	}

	public function compile() {
		foreach( $this->views as $view ) {
			if ( $view->has_parent() || $view->rendered() ) {
				continue;
			}
			$this->compiled_views[] = [
				'instance' => $view,
				'data'     => $view->render()
			];
		}
	}

	public function output() {
		foreach( $this->compiled_views as $view ) {
			output::set_data( $view['data'] );
		}
	}

	public function add( $name,view $view ) {
		$this->views[$name] = $view;
	}

	public function find( $name ) {
		if ( !isset( $this->views[$name] ) ) {
			throw new app_exception( "Unable to find view '%s'",$name );
		}
		return $this->views[$name];
	}

}

?>