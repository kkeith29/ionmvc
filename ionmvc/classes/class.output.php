<?php

namespace ionmvc\classes;

class output {

	const append  = 1;
	const prepend = 2;
	const file    = 3;

	private static $zlib = null;

	private $compression = false;
	private $cache = null;
	private $data = array();

	public static function __callStatic( $method,$args ) {
		$class = response::output();
		$method = "_{$method}";
		if ( !method_exists( $class,$method ) ) {
			throw new app_exception( "Method '%s' does not exist",$method );
		}
		return call_user_func_array( [ $class,$method ],$args );
	}

	public function __construct() {
		if ( is_null( self::$zlib ) ) {
			self::$zlib = extension_loaded('zlib');
		}
		response::hook()->attach('output',function() {
			response::output()->handle();
		});
		$this->compression = config::get('output.compression.enabled');
		if ( ( $accept_encoding = input::server('HTTP_ACCEPT_ENCODING') ) !== false && strpos( $accept_encoding,'gzip' ) === false ) {
			self::$compression = false;
		}
		$this->_compression( $this->compression );
	}

	public function handle() {
		if ( !is_array( $this->data ) ) {
			echo $this->data;
			return;
		}
		$data = '';
		foreach( $this->data as $datum ) {
			switch( $datum['type'] ) {
				case self::prepend:
					$data = $datum['data'] . $data;
				break;
				case self::append:
					$data .= $datum['data'];
				break;
				case self::file:
					echo $data;
					$data = '';
					readfile( $datum['data'] );
				break;
			}
		}
		if ( app::env( \ionmvc\ENV_PRODUCTION ) && !is_null( $this->cache ) ) {
			$cache = new cache('storage-cache-output');
			$cache->id( uri::current() )->expiration( $this->cache['data'],$this->cache['type'] )->set_data([
				'headers' => http::get_headers(),
				'data'    => $data
			]);
		}
		echo $data;
	}

	public function _set_data( $data,$type=self::append ) {
		$this->data[] = compact('data','type');
	}

	public function _cache( $data,$type=cache::minute ) {
		if ( !config::get('output.caching.enabled') ) {
			throw new app_exception('Output caching has been disabled');
		}
		$this->cache = compact('data','type');
	}

	public function _check_cache() {
		if ( !app::env( \ionmvc\ENV_PRODUCTION ) ) {
			return;
		}
		$cache = new cache('storage-cache-output');
		if ( ( $data = $cache->id( uri::current() )->fetch() ) !== false ) {
			http::set_headers( $data['headers'] );
			$this->data = $data['data'];
			app::destruct();
		}
	}

	public function _compression( $bool ) {
		if ( self::$zlib === false || headers_sent() ) {
			return;
		}
		ini_set('zlib.output_compression',( (bool) $bool ? 'On' : 'Off' ));
	}

}

?>