<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;

//credits for pbkdf2 functions: havoc@defuse.ca

abstract class security {

	private static $salt_length = 15;
	private static $config = array(
		'pbkdf2' => array(
			'hash_algorithm' => 'sha256',
			'iterations' => 1000,
			'salt_bytes' => 24,
			'hash_bytes' => 24
		),
		'hash' => array(
			'sections'        => 4,
			'algorithm_index'  => 0,
			'iteration_index' => 1,
			'salt_index'      => 2,
			'pbkdf2_index'    => 3
		)
	);


	private static function pbkdf2( $algorithm,$password,$salt,$count,$key_length,$raw_output=false ) {
		$algorithm = strtolower($algorithm);
		if ( !in_array( $algorithm,hash_algos(),true ) ) {
			throw new app_exception('Invalid hash algorithm');
		}
		if ( $count <= 0 || $key_length <= 0 ) {
			throw new app_exception('Invalid parameters');
		}
		$hash_length = strlen( hash( $algorithm,'',true ) );
		$block_count = ceil( $key_length / $hash_length );
		$output = '';
		for ( $i=1;$i <= $block_count;$i++ ) {
			$last = $salt . pack( "N",$i );
			$last = $xorsum = hash_hmac( $algorithm,$last,$password,true );
			for ( $j=1;$j < $count;$j++ ) {
				$xorsum ^= ( $last = hash_hmac( $algorithm,$last,$password,true ) );
			}
			$output .= $xorsum;
		}
		if ( $raw_output == true ) {
			return substr( $output,0,$key_length );
		}
		else {
			return bin2hex( substr( $output,0,$key_length ) );
		}
	}

	private static function slow_equals( $a,$b ) {
		$diff = strlen( $a ) ^ strlen( $b );
		for ( $i=0;( $i < strlen( $a ) && $i < strlen( $b ) );$i++ ) {
			$diff |= ord( $a[$i] ) ^ ord( $b[$i] );
		}
		return $diff === 0;
	}

	public static function hash_password( $password ) {
		// format: algorithm:iterations:salt:hash
		$salt = base64_encode( mcrypt_create_iv( self::$config['pbkdf2']['salt_bytes'],MCRYPT_DEV_URANDOM ) );
		return self::$config['pbkdf2']['hash_algorithm'] . ':' . self::$config['pbkdf2']['iterations'] . ":{$salt}:" . base64_encode(self::pbkdf2(
			self::$config['pbkdf2']['hash_algorithm'],
			$password,
			$salt,
			self::$config['pbkdf2']['iterations'],
			self::$config['pbkdf2']['hash_bytes'],
			true
		));
	}

	public static function validate_password( $password,$hash ) {
		$params = explode( ':',$hash );
		if ( count( $params ) < self::$config['hash']['sections'] ) {
			return false;
		}
		$pbkdf2 = base64_decode( $params[self::$config['hash']['pbkdf2_index']] );
		return self::slow_equals(
			$pbkdf2,
			self::pbkdf2(
				$params[self::$config['hash']['algorithm_index']],
				$password,
				$params[self::$config['hash']['salt_index']],
				(int) $params[self::$config['hash']['iteration_index']],
				strlen( $pbkdf2 ),
				true
			)
		);
	}

	public static function hash( $string,$salt=false ) {
		if ( $salt === false ) {
			$res = '';
			for( $i=0;$i<self::$salt_length;$i++ ) {
				$res .= pack( 's',mt_rand() );
			}
			$salt = substr( base64_encode( $res ),0,self::$salt_length );
		}
		return $salt . sha1( $salt . $string );
	}

	public static function check_hash( $string,$hash ) {
		if ( self::hash( $string,substr( $hash,0,self::$salt_length ) ) === $hash ) {
			return true;
		}
		return false;
	}

	public static function checksum() {
		$args = func_get_args();
		$args = array_func::flatten( $args );
		return sha1( config::get('security.salt') . implode( '',$args ) );
	}

}

?>