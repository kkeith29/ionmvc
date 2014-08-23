<?php

namespace ionmvc\classes\view;

use ionmvc\classes\app;
use ionmvc\classes\output;
use ionmvc\classes\view;
use ionmvc\exceptions\app as app_exception;

class registry {

	private $views = array();
	private $compiled_views = array();

	public function __construct() {
		app::register('view',function() {
			app::init('view/registry')->compile();
		});
		app::register('view_output',function() {
			app::init('view/registry')->output();
		});
	}

	public function compile() {
		foreach( $this->views as $view ) {
			if ( $view->has_parent() || $view->rendered() ) {
				continue;
			}
			$this->compiled_views[] = array(
				'instance' => $view,
				'data' => $view->render()
			);
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