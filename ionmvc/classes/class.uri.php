<?php

namespace ionmvc\classes;

use ionmvc\classes\config\string as config_string;
use ionmvc\exceptions\app as app_exception;

class uri {

	const csm_uri = 'csm';

	const title_dash = '-';
	const title_underline = '_';

	private static $routes = array();

	private $config = array(
		'url_nonsecure' => '',
		'url_secure'    => '',
		'url_extension' => false,
		'append_index'  => true,
		'ssl' => false
	);
	private $segments = array();
	private $named_segments = array();

	public static function get_path( $parse=false ) {
		$path = '';
		if ( !isset( $_GET['_uri_'] ) ) {
			if ( isset( $_SERVER['PATH_INFO'] ) ) {
				$path = $_SERVER['PATH_INFO'];
			}
			else {
				//setup request_uri or query_string method of getting url
			}
		}
		else {
			$path = $_GET['_uri_'];
		}
		$path = trim( $path,'/' );
		return ( $parse == true ? self::parse_path( $path,true ) : $path );
	}

	public static function parse_path( $path,$init=false ) {
		$retval = array(
			'segments' => array(),
			'named_segments' => array()
		);
		if ( is_string( $path ) && $path !== '' ) {
			$parts = explode( '/',trim( $path,'/' ) );
			$c = count( $parts );
			$i = $n = 0;
			$regex = config::get('url.permitted_chars');
			foreach( $parts as $part ) {
				if ( ++$i === $c ) {
					foreach( array('#'=>'frag','.'=>'extn') as $str => $name ) {
						if ( ( $pos = strrpos( $part,$str ) ) !== false ) {
							$retval[$name] = substr( $part,( $pos + 1 ) );
							$part = substr( $part,0,$pos );
						}
					}
				}
				if ( $init === true && !preg_match( "#^[{$regex}]+$#",$part ) ) {
					event::trigger('app.error.invalid_uri_chars');
					break;
				}
				if ( strpos( $part,':' ) !== false ) {
					list( $name,$part ) = explode( ':',$part,2 );
					$retval['named_segments'][$name] = $part;
					continue;
				}
				$retval['segments'][] = $part;
			}
			$retval['segments'] = array_func::reindex( $retval['segments'] );
		}
		elseif ( is_array( $path ) && count( $path ) > 0 ) {
			foreach( $path as $key => $value ) {
				$type = ( is_numeric( $key ) ? 'segments' : 'named_segments' );
				$retval[$type][$key] = $value;
			}
		}
		return $retval;
	}

	public static function __callStatic( $method,$args ) {
		$class = app::uri();
		$method = "_{$method}";
		if ( !method_exists( $class,$method ) ) {
			throw new app_exception( "Method '%s' not found",$method );
		}
		return call_user_func_array( array( $class,$method ),$args );
	}

	public function __construct() {
		$base = trim(( isset( $_SERVER['HTTP_HOST'] ) ? "{$_SERVER['HTTP_HOST']}" . str_replace( basename( $_SERVER['SCRIPT_NAME'] ),'',$_SERVER['SCRIPT_NAME'] ) : 'localhost' ),'/');
		$this->config['url_nonsecure'] = config::get('url.nonsecure','');
		if ( $this->config['url_nonsecure'] === '' ) {
			$this->config['url_nonsecure'] = "http://{$base}/"; 
		}
		else {
			$this->config['url_nonsecure'] = rtrim( $this->config['url_nonsecure'],'/' ) . '/';
		}
		$this->config['url_secure'] = config::get('url.secure','');
		if ( $this->config['url_secure'] === '' ) {
			$this->config['url_secure'] = "https://{$base}/";
		}
		else {
			$this->config['url_secure'] = rtrim( $this->config['url_secure'],'/' ) . '/';
		}
		if ( config::get('url.append_index') === false ) {
			$this->config['append_index'] = false;
		}
		if ( ( $extn = config::get('url.extension') ) !== false ) {
			$this->config['url_extension'] = $extn;
		}
		$this->config['ssl'] = ( config::get('framework.ssl') === true );
		$this->init( self::get_path() );
	}

	public function init( $path ) {
		$data = self::parse_path( $path );
		if ( count( $data['segments'] ) > 0 ) {
			$data['segments'] = array_func::reindex( $data['segments'] );
		}
		$this->segments = $data['segments'];
		$this->named_segments = $data['named_segments'];
	}

	public function _segment_count() {
		return count( $this->segments );
	}

	public function _get_segments( $s=0,$e=null ) {
		return array_slice( $this->segments,$s,( is_null( $e ) ? count( $this->segments ) : ( $e - $s ) ) );
	}

	public function _get_named_segments() {
		return $this->named_segments;
	}

	public function _is_set( $idx ) {
		$type = ( is_numeric( $idx ) ? 'segments' : 'named_segments' );
		return isset( $this->{$type}[$idx] );
	}

	public function _set( $idx,$value ) {
		$type = ( is_numeric( $idx ) ? 'segments' : 'named_segments' );
		$this->{$type}[$idx] = $value;
	}

	public function _get( $idx,$return=false ) {
		$type = ( is_numeric( $idx ) ? 'segments' : 'named_segments' );
		return ( isset( $this->{$type}[$idx] ) ? $this->{$type}[$idx] : $return );
	}

	public function _base( $ssl=null ) {
		$ssl = ( !$this->config['ssl'] ? false : ( is_null( $ssl ) ? app::detect_ssl() : $ssl ) );
		return ( $ssl == true ? $this->config['url_secure'] : $this->config['url_nonsecure'] );
	}

	public function _uri_string( $segments=null,$named_segments=null,$remove_csm=false ) {
		$segments = ( is_null( $segments ) ? $this->segments : $segments );
		$segments = array_filter( $segments );
		if ( $named_segments !== false ) {
			$named_segments = ( is_null( $named_segments ) ? $this->named_segments : $named_segments );
			$named_segments = array_filter( $named_segments );
			if ( isset( $named_segments[self::csm_uri] ) ) {
				$csm = $named_segments[self::csm_uri];
				unset( $named_segments[self::csm_uri] );
			}
			ksort( $named_segments,SORT_STRING );
			if ( isset( $csm ) && $remove_csm === false ) {
				$named_segments[self::csm_uri] = $csm;
			}
		}
		$uri = implode( '/',$segments );
		if ( $named_segments !== false && count( $named_segments ) > 0 ) {
			foreach( $named_segments as $idx => $segment ) {
				$uri .= "/{$idx}:{$segment}";
			}
		}
		return $uri;
	}

	public function _create( $path='',$config='' ) {
		$config = config_string::parse( $config );
		$defaults = array(
			'all'     => false,
			'ssl'     => null,
			'base'    => true,
			'csm'     => false,
			'no-extn' => false,
			'no-frag' => false
		);
		$info = self::parse_path( $path );
		$config = array_merge( $defaults,$info,$config );
		unset( $info,$defaults );
		if ( ( is_string( $path ) && $path == '' ) || ( is_array( $path ) && count( $path ) == 0 ) ) {
			$config['all'] = true;
		}
		if ( $config['all'] === true ) {
			$config['segments'] = array_func::merge( $this->segments,$config['segments'] );
			$config['named_segments'] = array_func::merge( $this->named_segments,$config['named_segments'] );
		}
		$url_base = ( $config['base'] === true ? $this->_base( $config['ssl'] ) : '' ) . ( $this->config['append_index'] === true ? 'index.php/' : '' );
		$uri_string = $this->_uri_string( $config['segments'],$config['named_segments'],( $config['csm'] === true ? true : false ) );
		if ( $config['csm'] === true ) {
			$config['named_segments'][self::csm_uri] = security::checksum( $url_base . $uri_string );
			$uri_string = $this->_uri_string( $config['segments'],$config['named_segments'] );
		}
		$url = $url_base . $uri_string;
		if ( $uri_string !== '' && $config['no-extn'] === false && ( isset( $config['extn'] ) || ( ( $config['extn'] = $this->config['url_extension'] ) !== false ) ) ) {
			$url .= ".{$config['extn']}";
		}
		if ( $config['no-frag'] === false && isset( $config['frag'] ) ) {
			$url .= "#{$config['frag']}";
		}
		return $url;
	}

	public function _current( $config=array() ) {
		$config['all'] = true;
		return $this->_create( '',$config );
	}

	public function _validate_csm() {
		if ( $this->_get('csm') == security::checksum( $this->_create( array('csm'=>''),'all|no-extn|no-frag' ) ) ) {
			return true;
		}
		return false;
	}

	public static function segment( $idx,$return=false ) {
		return app::init('uri')->_get( $idx,$return );
	}

	public static function title( $str,$sep=self::title_dash,$lowercase=false ) {
		$replacements = array(
			'&\#\d+?;'       => '',
			'&\S+?;'         => '',
			'\s+'            => $sep,
			'[^a-z0-9\-\._]' => '',
			"{$sep}+"        => $sep,
			"{$sep}$"        => $sep,
			"^{$sep}"        => $sep,
			'\.+$'           => ''
		);
		$str = strip_tags( $str );
		foreach( $replacements as $key => $value ) {
			$str = preg_replace( "#{$key}#i",$value,$str );
		}
		if ( $lowercase == true ) {
			$str = strtolower( $str );
		}
		return trim( stripslashes( $str ) );
	}

	public static function base64_encode( $data ) {
		return str_replace( array('+','/','='),array('-','_','~'),base64_encode( $data ) );
	}

	public static function base64_decode( $data ) {
		return base64_decode( str_replace( array('-','_','~'),array('+','/','='),$data ) );
	}

}

?>