<?php

namespace ionmvc\classes\input;

use ionmvc\classes\config;

class useragent {

	private $agent = '';

	private $is_mobile = false;
	private $mobile = '';

	private $is_robot = false;
	private $robot = '';

	private $mobiles = array();
	private $robots = array();

	public function __construct() {
		config::load('useragents.php',array(
			'extend' => true
		));
		$this->mobiles = config::get('agents.mobile');
		$this->robots  = config::get('agents.robots');
		if ( !isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return;
		}
		$this->agent = $_SERVER['HTTP_USER_AGENT'];
		if ( is_array( $this->agent ) ) {
			$this->agent = array_shift( $this->agent );
		}
		foreach( array('mobiles'=>'mobile','robots'=>'robot') as $type => $data ) {
			if ( count( $this->{$type} ) > 0 ) {
				foreach( $this->{$type} as $datum => $name ) {
					if ( stripos( $this->agent,$datum ) !== false ) {
						$var = "is_{$data}";
						$this->{$var} = true;
						$this->{$data} = $name;
						break 2;
					}
				}
			}
		}
	}

	public function __toString() {
		return $this->agent;
	}

	public function is_mobile() {
		return $this->is_mobile;
	}

	public function is_robot() {
		return $this->is_robot;
	}

}

?>