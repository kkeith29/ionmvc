<?php

namespace ionmvc\classes;

abstract class func {

	public static function fill() {
		$args = func_get_args();
		$str = array_shift( $args );
		//if ( func_num_args() == 2 && is_array( $args[0] ) ) {
			//$args = $args[0];
		//}
		foreach( $args as $arg ) {
			if ( is_array( $arg ) ) {
				foreach( $arg as $part => $data ) {
					while( false !== ( $pos = strpos( $str,":{$part}" ) ) && substr( $str,( $pos - 1 ),1 ) !== '\\' ) {
						$str = substr_replace( $str,$data,$pos,( strlen( $part ) + 1 ) );
					}
				}
				continue;
			}
			if ( false !== ( $pos = strpos( $str,'?' ) ) && substr( $str,( $pos - 1 ),1 ) !== '\\' ) {
				$str = substr_replace( $str,$arg,$pos,1 );
			}
		}
		return $str;
	}

	public static function debug( $array ) {
		echo '<pre style="background-color:#fff;color:#000;">' . print_r( (array) $array,true ) . '</pre>';
	}

	public static function rand_string( $length,$type='all',$str='' ) {
		$types = array(
			'alpha'       => 'bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ',
			'alpha-lower' => 'bcdfghjklmnpqrstvwxyz',
			'alpha-upper' => 'BCDFGHJKLMNPQRSTVWXYZ',
			'numeric'     => '0123456789',
			'special'     => '`!"?$?%^&*()_-+={[}]:;@\'~#|\<,>.?/'
		);
		if ( $type !== 'custom' ) {
			if ( $type == 'all' ) {
				unset( $types['alpha'] );
				foreach( $types as $type => $_str ) {
					$str .= $_str;
				}
			}
			else {
				$_types = explode( ',',$type );
				foreach( $_types as $type ) {
					if ( !isset( $types[$type] ) ) {
						throw new app_exception("Type '{$type}' is invalid");
					}
					$str .= $types[$type];
				}
			}
		}
		$data = array();
		if ( is_string( $str ) ) {
			$data = str_split( $str,1 );
		}
		$i = 0;
		$str = '';
		while ( $i < $length ) {
			$rand = mt_rand( 0,( count( $data ) - 1 ) );
			$str .= $data[$rand];
		$i++;
		}
		return $str;
	}

	public static function split_string( $data,$len=50,$dots=true ) {
		$data = explode( ' ',$data );
		$result = array();
		$i = 0;
		foreach( $data as $str ) {
			$i = ( $i + strlen( $str ) + 1 );
			if ( $i <= ( $len - 1 ) ) {
				$result[] = $str;
			}
		}
		return implode( ' ',$result ) . ( $dots == true ? '…' : ' ' );
	}

	public static function is_md5( $str ) {
		return preg_match('/^[A-Fa-f0-9]{32}$/',$str);
	}

	public static function is_url( $data ) {
		foreach( array('http://','https://','//') as $str ) {
			if ( strpos( $data,$str ) === 0 ) {
				return true;
			}
		}
		return false;
	}

	public static function format_money( $number,$cents=1 ) {
		if (is_numeric($number)) {
			if (!$number) {
				$money = ($cents == 2 ? '0.00' : '0');
			}
			else {
				if (floor($number) == $number) {
					$money = number_format($number, ($cents == 2 ? 2 : 0));
				}
				else {
					$money = number_format(round($number, 2), ($cents == 0 ? 0 : 2));
				}
			}
			return $money;
		}
	}

	public static function str_at_start( $str,&$string,$replace=false ) {
		if ( ( $pos = strpos( $string,$str ) ) !== false && $pos == 0 ) {
			if ( $replace == true ) {
				$string = substr( $string,strlen( $str ) );
			}
			echo $string;
			return true;
		}
		return false;
	}

	public static function str_at_end( $str,&$string,$replace=false ) {
		if ( ( $pos = strpos( $string,$str ) ) !== false && $pos == ( strlen( $string ) - strlen( $str ) ) ) {
			if ( $replace == true ) {
				$string = substr( $string,0,$pos );
			}
			return true;
		}
		return false;
	}

	public static function sort_by_length( &$array,$type='asc' ) {
		usort( $array,array( 'func',"length_cmp_{$type}" ) );
	}

	private static function length_cmp_asc( $a,$b ) {
		return ( strlen( $a ) - strlen( $b ) );
	}

	private static function length_cmp_desc( $a,$b ) {
		return ( strlen( $b ) - strlen( $a ) );
	}

	public static function url_exists( $url ) {
		$url = str_replace( 'http://','',$url );
		if ( strstr( $url,'/' ) ) {
			$url = explode( '/',$url,2 );
			$url[1] = "/{$url[1]}";
		} else {
			$url = array( $url,'/' );
		}
		if ( ( $fh = fsockopen( $url[0],80 ) ) !== false ) {
			fputs( $fh,"GET {$url[1]} HTTP/1.1\nHost:{$url[0]}\n\n");
			if ( fread( $fh,22 ) == 'HTTP/1.1 404 Not Found' ) {
				return false;
			}
			return true;
		}
		return false;
	}

	public static function pad( $value,$length,$str='0' ) {
		if ( strlen( $value ) == $length || strlen( $value ) > $length ) {
			return $value;
		}
		$num = ( $length - strlen( $value ) );
		return str_repeat( $str,$num ) . $value;
	}

	public static function get_value( $data,$retval='N/A' ) {
		if ( $data == '' ) {
			return $retval;
		}
		return $data;
	}
	
	public static function get_list( $data,$list,$sep=', ',$retval='N/A' ) {
		if ( !is_array( $data ) ) {
			$data = array( $data );
		}
		$values = array();
		foreach( $data as $datum ) {
			if ( isset( $list[$datum] ) ) {
				$values[] = $list[$datum];
			}
		}
		return ( count( $values ) == 0 ? $retval : implode( $sep,$values ) );
	}

	public static function youtube_video_id( $url ) {
		$url = str_replace( array('http://','https://','www.youtube.com/','youtube.com/'),'',$url );
		$video_id = false;
		if ( ( $pos = strpos( $url,'?v=' ) ) !== false && substr( $url,0,$pos ) == 'watch' ) {
			$video_id = substr( $url,( $pos + 3 ) );
		}
		elseif ( strpos( $url,'/' ) !== false ) {
			list( $part_1,$part_2 ) = explode( '/',$url,2 );
			if ( in_array( $part_1,array('embed','v') ) ) {
				$video_id = $part_2;
			}
		}
		if ( $video_id !== false && ( $pos = strpos( $video_id,'&' ) ) !== false ) {
			$video_id = substr( $video_id,0,$pos );
		}
		return $video_id;
	}

	public static function vimeo_video_id( $url ) {
		$url = str_replace( array('http://','https://','www.vimeo.com/','vimeo.com/'),'',$url );
		if ( ( $pos = strpos( $url,'?' ) ) !== false ) {
			$url = substr( $url,0,$pos );
		}
		return $url;
	}

	public static function validate_email( $email ) {
		if ( ( $pos = strpos( $email,'@' ) ) === false || $pos === 0 || $pos == strlen( $email ) || substr_count( $email,'@' ) > 1 ) {
			return false;
		}
		$local_part = substr( $email,0,$pos );
		if ( preg_match( '#^[A-Za-z0-9!\#\$%&\'\*\+\-/=\?\^_`\{\|\}~\.]+$#',$local_part ) == 0 ) {
			return false;
		}
		if ( substr( $local_part,0,1 ) == '.' || substr( $local_part,-1,1 ) == '.' || preg_match( '#[\.]{2,}#',$local_part ) > 0 ) {
			return false;
		}
		$hostname = substr( $email,( $pos + 1 ) );
		if ( preg_match( '#[A-Za-z0-9\-\.]+#',$hostname ) == 0 ) {
			return false;
		}
		$not_allowed = array('-','.');
		if ( in_array( substr( $hostname,0,1 ),$not_allowed ) || in_array( substr( $hostname,-1,1 ),$not_allowed ) || preg_match( '#[\.]{2,}#',$hostname ) > 0 ) {
			return false;
		}
		return true;
	}

}

?>