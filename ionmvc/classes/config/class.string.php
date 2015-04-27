<?php

namespace ionmvc\classes\config;

use ionmvc\classes\array_func;

class string {

	private $replacements = [];
	private $data         = [];

	public function __construct( $data ) {
		$this->parse_str( $data );
	}

	private function replace_value( $match ) {
		$i = count( $this->replacements );
		$this->replacements[$i] = $match[1];
		return "{R:{$i}}";
	}

	private function prepare_value( $data ) {
		if ( substr( $data,0,3 ) == '{R:' && ( $pos = strpos( $data,'}' ) ) !== false ) {
			$i = substr( $data,3,( $pos - 3 ) );
			if ( isset( $this->replacements[$i] ) ) {
				$data = $this->replacements[$i];
				unset( $this->replacements[$i] );
				return $data;
			}
		}
		return rtrim( $data,']' );
	}

	private function parse_str( $data ) {
		if ( is_array( $data ) ) {
			$this->data = $data;
			return;
		}
		if ( $data == '' ) {
			return $this->data;
		}
		$replacements = false;
		if ( strpos( $data,'{' ) !== false ) {
			$data = preg_replace_callback( '#\{([^\}]+)\}#',[ $this,'replace_value' ],$data );
			$replacments = true;
		}
		$data = explode( '|',$data );
		$config = [];
		foreach( $data as $datum ) {
			$values = true;
			if ( strpos( $datum,'[' ) !== false ) {
				$values = array_map( [ $this,'prepare_value' ],explode( '[',$datum ) );
				$datum = array_shift( $values );
				foreach( $values as &$value ) {
					if ( $value == 'yes' ) {
						$value = true;
					}
					elseif ( $value == 'no' ) {
						$value = false;
					}
				}
				if ( count( $values ) == 1 ) {
					$values = $values[0];
				}
			}
			if ( strpos( $datum,':' ) !== false ) {
				$parts = explode( ':',$datum );
				$data = [];
				$_data =& $data;
				foreach( $parts as $part ) {
					$_data[$part] = [];
					$_data =& $_data[$part];
				}
				$_data = $values;
				$config = array_func::merge_recursive_distinct( $config,$data );
				continue;
			}
			$config[$datum] = $values;
		}
		$this->data = $config;
	}

	public function data() {
		return $this->data;
	}

	public static function parse( $data ) {
		$config = new self( $data );
		return $config->data();
	}

}

?>