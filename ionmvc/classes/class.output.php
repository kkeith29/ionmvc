<?php

namespace ionmvc\classes;

class output {

	const append  = 1;
	const prepend = 2;
	const file    = 3;

	private static $compression = false;
	private static $zlib = false;

	private $cache = null;
	private $data = array();

	public static function init() {
		app::register('output',function() {
			output::handle();
		});
		self::$compression = ( config::get('output.compression.enabled') === true );
		if ( isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) && strpos( $_SERVER['HTTP_ACCEPT_ENCODING'],'gzip' ) === false ) {
			self::$compression = false;
		}
		self::$zlib = extension_loaded('zlib');
		self::compression( self::$compression );
	}

	public static function __callStatic( $method,$args ) {
		$class = app::output();
		$method = "_{$method}";
		if ( !method_exists( $class,$method ) ) {
			throw new app_exception( "Method '%s' does not exist",$method );
		}
		return call_user_func_array( array( $class,$method ),$args );
	}

	public function _handle() {
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
		if ( config::get('production') === true && !is_null( $this->cache ) ) {
			$cache = new cache('storage-cache-output');
			$cache->id( uri::current() )->expiration( $this->cache['data'],$this->cache['type'] )->set_data(array(
				'headers' => http::get_headers(),
				'data'    => $data
			));
		}
		echo $data;
	}

	public function _set_data( $data,$type=self::append ) {
		$this->data[] = compact('data','type');
	}

	public function _cache( $data,$type=cache::minute ) {
		if ( config::get('output.caching.enabled') !== '1' ) {
			throw new app_exception('Output caching has been disabled');
		}
		$this->cache = compact('data','type');
	}

	public function _check_cache() {
		if ( config::get('production') === true ) {
			return;
		}
		$cache = new cache('storage-cache-output');
		if ( ( $data = $cache->id( uri::current() )->fetch() ) !== false ) {
			http::set_headers( $data['headers'] );
			$this->data = $data['data'];
			app::destruct();
		}
	}

	public static function compression( $bool ) {
		if ( self::$zlib === false || headers_sent() ) {
			return;
		}
		ini_set('zlib.output_compression',( (bool) $bool === true ? 'On' : 'Off' ));
	}

}

?>