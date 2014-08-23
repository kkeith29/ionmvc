<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;

class http {

	private $statuses = array(
		200	=> 'OK',
		201	=> 'Created',
		202	=> 'Accepted',
		203	=> 'Non-Authoritative Information',
		204	=> 'No Content',
		205	=> 'Reset Content',
		206	=> 'Partial Content',
		300	=> 'Multiple Choices',
		301	=> 'Moved Permanently',
		302	=> 'Found',
		303	=> 'See Other',
		304	=> 'Not Modified',
		305	=> 'Use Proxy',
		307	=> 'Temporary Redirect',
		400	=> 'Bad Request',
		401	=> 'Unauthorized',
		403	=> 'Forbidden',
		404	=> 'Not Found',
		405	=> 'Method Not Allowed',
		406	=> 'Not Acceptable',
		407	=> 'Proxy Authentication Required',
		408	=> 'Request Timeout',
		409	=> 'Conflict',
		410	=> 'Gone',
		411	=> 'Length Required',
		412	=> 'Precondition Failed',
		413	=> 'Request Entity Too Large',
		414	=> 'Request-URI Too Long',
		415	=> 'Unsupported Media Type',
		416	=> 'Requested Range Not Satisfiable',
		417	=> 'Expectation Failed',
		422	=> 'Unprocessable Entity',
		500	=> 'Internal Server Error',
		501	=> 'Not Implemented',
		502	=> 'Bad Gateway',
		503	=> 'Service Unavailable',
		504	=> 'Gateway Timeout',
		505	=> 'HTTP Version Not Supported'
	);
	private $mime_types = null;
	private $headers = array();
	private $content_type = array(
		'data'    => 'text/html',
		'charset' => 'UTF-8',
		'sent'    => false
	);

	public static function __callStatic( $method,$args ) {
		$class = app::http();
		$method = "_{$method}";
		if ( !method_exists( $class,$method ) ) {
			throw new app_exception( "Method '%s' not found",$method );
		}
		return call_user_func_array( array( $class,$method ),$args );
	}

	public function __construct() {
		app::register('http',function() {
			app::http()->send_headers();
		});
	}

	public function _get_headers() {
		if ( $this->content_type['sent'] === false ) {
			$this->_header("Content-Type: {$this->content_type['data']}" . ( !is_null( $this->content_type['charset'] ) ? "; charset: {$this->content_type['charset']}" : '' ));
			$this->content_type['sent'] = true;
		}
		return $this->headers;
	}

	public function _set_headers( $headers ) {
		$this->headers = $headers;
	}

	public function send_headers() {
		foreach( $this->_get_headers() as $header ) {
			header( $header['data'],$header['overwrite'],$header['code'] );
		}
	}

	public function _mime_type( $extn ) {
		if ( is_null( $this->mime_types ) ) {
			config::load('mime_types.php',array(
				'extend' => true
			));
			$this->mime_types = config::get('mime_types');
		}
		if ( !isset( $this->mime_types[$extn] ) ) {
			return false;
		}
		return $this->mime_types[$extn];
	}

	public function _header( $data,$overwrite=false,$code=null ) {
		$this->headers[] = compact('data','overwrite','code');
	}

	public function _content_type( $data,$charset=null ) {
		if ( strpos( $data,'/' ) === false && ( $data = $this->_mime_type( $data ) ) === false ) {
			throw new app_exception('Mime type not found');
		}
		$this->content_type = array(
			'data'    => $data,
			'charset' => $charset,
			'sent'    => false
		);
	}

	public function _status_code( $code,$text=null ) {
		if ( !isset( $this->statuses[$code] ) ) {
			throw new app_exception('Status not found');
		}
		$text = $this->statuses[$code];
		if ( strpos( php_sapi_name(),'cgi' ) === 0 ) {
			$this->_header( "Status: {$code} {$text}",true );
			return;
		}
		$server_protocol = ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1' );
		$this->_header( "{$server_protocol} {$code} {$text}",true,$code );
	}

	public function _cache( $last_modified,$expiration ) {
		//maybe do 304 here
		$this->_header('Pragma: public');
		$this->_header('Cache-Control: max-age=' . ( $expiration - $_SERVER['REQUEST_TIME'] ) . ', public');
		$this->_header('Expires: ' . gmdate( 'D, d M Y H:i:s',$expiration ) . ' GMT');
		$this->_header('Last-modified: ' . gmdate( 'D, d M Y H:i:s',$last_modified ) . ' GMT');
	}

}

?>