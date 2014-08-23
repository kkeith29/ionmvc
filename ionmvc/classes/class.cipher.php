<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;

class cipher {

	private $key = null;
	private $iv = null;

	public function __construct() {
		if ( !function_exists('mcrypt_encrypt') ) {
			throw new app_exception('Cipher class requires mcrypt to be installed');
		}
		if ( ( $key = config::get('cipher.key') ) !== false ) {
			$this->_key( $key );
		}
		$this->iv = mcrypt_create_iv(32);
	}

	public function _key( $key ) {
		$this->key = hash('sha256',$key,true);
	}

	public function _encrypt( $data ) {
		if ( is_null( $this->key ) ) {
			throw new app_exception('Cipher key is required to encrypt data');
		}
		return base64_encode( mcrypt_encrypt( MCRYPT_RIJNDAEL_256,$this->key,$data,MCRYPT_MODE_ECB,$this->iv ) );
	}

	public function _decrypt( $data ) {
		if ( is_null( $this->key ) ) {
			throw new app_exception('Cipher key is required to decrypt data');
		}
		return trim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256,$this->key,base64_decode( $data ),MCRYPT_MODE_ECB,$this->iv ) );
	}

	public static function key( $key ) {
		app::cipher()->_key( $key );
	}

	public static function encrypt( $data ) {
		return app::cipher()->_encrypt( $data );
	}

	public static function decrypt( $data ) {
		return app::cipher()->_decrypt( $data );
	}

}

?>