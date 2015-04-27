<?php

namespace ionmvc\classes;

use ionmvc\classes\config\string as config_string;
use ionmvc\exceptions\app as app_exception;

class uri {

	const csm_uri = 'csm';

	const title_dash      = '-';
	const title_underline = '_';

	private static $base64_chars = [
		'original'     => ['+','/','='],
		'replacements' => ['-','_','~']
	];

	private $config = [
		'permitted_chars' => false,
		'url_nonsecure'   => '',
		'url_secure'      => '',
		'url_extension'   => false,
		'append_index'    => true,
		'ssl'             => false
	];
	private $uri = '';
	private $input;
	private $segments       = [];
	private $named_segments = [];
	private $frag           = '';
	private $extension      = '';

	public static function path_parse( $path,$config=[] ) {
		$retval = [
			'segments'       => [],
			'named_segments' => []
		];
		if ( is_string( $path ) && $path !== '' ) {
			$parts = explode( '/',trim( $path,'/' ) );
			$c = count( $parts );
			$i = $n = 0;
			foreach( $parts as $part ) {
				if ( ++$i === $c ) {
					foreach( ['#'=>'frag','.'=>'extn'] as $str => $name ) {
						if ( ( $pos = strrpos( $part,$str ) ) !== false ) {
							$retval[$name] = substr( $part,( $pos + 1 ) );
							$part = substr( $part,0,$pos );
						}
					}
				}
				if ( isset( $config['permitted_chars'] ) && $config['permitted_chars'] !== false && !preg_match( "#^[{$config['permitted_chars']}]+$#",$part ) ) {
					//event::trigger('app.error.invalid_uri_chars');
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

	public function __construct( input $input,$config=[] ) {
		$this->config = array_merge( $this->config,$config );
		$this->input  = $input;
		$this->uri    = ( !isset( $config['uri'] ) ? $this->path_get() : $config['uri'] );
		$info = self::path_parse( $this->uri,[
			'permitted_chars' => $this->config['permitted_chars']
		] );
		$this->segments = $info['segments'];
		$this->named_segments = $info['named_segments'];
		if ( isset( $info['frag'] ) ) {
			$this->fragment = $info['frag'];
		}
		if ( isset( $info['extn'] ) ) {
			$this->extension = $info['extn'];
		}
		$host = $this->host_get();
		if ( $this->config['url_nonsecure'] === '' ) {
			$this->config['url_nonsecure'] = "http://{$host}/"; 
		}
		else {
			$this->config['url_nonsecure'] = rtrim( $this->config['url_nonsecure'],'/' ) . '/';
		}
		if ( $this->config['url_secure'] === '' ) {
			$this->config['url_secure'] = "https://{$host}/";
		}
		else {
			$this->config['url_secure'] = rtrim( $this->config['url_secure'],'/' ) . '/';
		}
	}

	public function __call( $method,$args ) {
		$method = "_{$method}";
		if ( !method_exists( $this,$method ) ) {
			throw new app_exception( "Method '%s' not found",$method );
		}
		return call_user_func_array( [ $this,$method ],$args );
	}

	private function path_get() {
		if ( $this->input->has('_uri_') ) {
			return $this->input->request('_uri_');
		}
		if ( ( $path = $this->input->server('PATH_INFO') ) !== false ) {
			return $path;
		}
		if ( ( $path = $this->input->server('REQUEST_URI') ) !== false ) {
			return ltrim( $path,'/' );
		}
		//add request_uri and query string options here
		throw new app_exception('Unable to determine uri path');
	}

	private function host_get() {
		if ( ( $host = $this->input->server('HTTP_HOST') ) !== false ) {
			$script_name = $this->input->server('SCRIPT_NAME');
			return trim( $host . str_replace( basename( $script_name ),'',$script_name ),'/' );
		}
		return 'localhost';
	}

	public static function __callStatic( $method,$args ) {
		$class = request::resource_id();
		$_class = __CLASS__;
		if ( !is_a( $class,$_class ) ) {
			throw new app_exception("Resource ID is not an instance of URI, therefore this class is not usable");
		}
		$method = "_{$method}";
		if ( !method_exists( $class,$method ) ) {
			throw new app_exception( "Method '%s' not found",$method );
		}
		return call_user_func_array( [ $class,$method ],$args );
	}

	public function _segment_count() {
		return count( $this->segments );
	}

	public function _segments( $s=0,$e=null ) {
		return array_slice( $this->segments,$s,( is_null( $e ) ? count( $this->segments ) : ( $e - $s ) ) );
	}

	public function _named_segments() {
		return $this->named_segments;
	}

	public function _all_segments() {
		return [
			'segments'       => $this->segments,
			'named_segments' => $this->named_segments
		];
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

	public function _segment( $idx,$return=false ) {
		return $this->_get( $idx,$return );
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
		$defaults = [
			'all'     => false,
			'ssl'     => null,
			'base'    => true,
			'csm'     => false,
			'no-extn' => false,
			'no-frag' => false
		];
		$info = self::path_parse( $path );
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

	public function _current( $config=[] ) {
		$config['all'] = true;
		return $this->_create( '',$config );
	}

	public function _validate_csm() {
		if ( $this->_get('csm') == security::checksum( $this->_create( ['csm'=>''],'all|no-extn|no-frag' ) ) ) {
			return true;
		}
		return false;
	}

	public static function title( $str,$sep=self::title_dash,$lowercase=false ) {
		$replacements = [
			'&\#\d+?;'       => '',
			'&\S+?;'         => '',
			'\s+'            => $sep,
			'[^a-z0-9\-\._]' => '',
			"{$sep}+"        => $sep,
			"{$sep}$"        => $sep,
			"^{$sep}"        => $sep,
			'\.+$'           => ''
		];
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
		return str_replace( self::$base64_chars['original'],self::$base64_chars['replacements'],base64_encode( $data ) );
	}

	public static function base64_decode( $data ) {
		return base64_decode( str_replace( self::$base64_chars['replacements'],self::$base64_chars['original'],$data ) );
	}

}

?>