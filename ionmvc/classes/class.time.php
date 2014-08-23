<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;

class time {

	const second = 1;
	const minute = 60;
	const hour   = 3600;
	const day    = 86400;
	const week   = 604800;
	const month  = 2629000;
	const year   = 31536000;

	private static $dst = false;
	private static $offset = 0;

	public static function init() {
		date_default_timezone_set('UTC');
	}

	public static function now() {
		return time();
	}

	public static function future( $data,$unit ) {
		return ( self::now() + ( $data * $unit ) );
	}

	public static function past( $data,$unit ) {
		return ( self::now() - ( $data * $unit ) );
	}

	public static function formatted( $format,$time=null,$adjust=true ) {
		if ( is_null( $time ) ) {
			$time = self::now();
		}
		if ( $adjust === true ) {
			$time = ( (int) $time + self::$offset + ( self::$dst == true ? self::hour : 0 ) );
		}
		return gmdate( $format,$time );
	}

	public static function timezone_offset( $hours=null,$dst=null ) {
		if ( is_null( $hours ) ) {
			return ( ( self::$offset + ( self::$dst == true ? self::hour : 0 ) ) / self::hour );
		}
		self::$offset = ( $hours * self::hour );
		if ( !is_null( $dst ) ) {
			self::dst( $dst );
		}
	}

	public static function dst( $bool=null ) {
		if ( is_null( $bool ) ) {
			return self::$dst;
		}
		self::$dst = (bool) $bool;
	}

	public static function timezone( $tz_id ) {
		$tz = new \DateTimeZone( $tz_id );
		if ( ( $trans = $tz->getTransitions( self::now() ) ) > 0 ) {
			self::$offset = $trans[0]['offset'];
			self::$dst    = $trans[0]['isdst'];
			return true;
		}
		return false;
	}

	public static function convert_date( $from,$to,$date ) {
		$date_sep = substr( preg_replace( '#[0-9]+#','',$date ),0,1 );
		$from_sep = substr( str_replace( array('MM','DD','YYYY','YY'),'',$from ),0,1 );
		$to_sep = substr( str_replace( array('MM','DD','YYYY','YY'),'',$to ),0,1 );
		$date = explode( $date_sep,$date );
		$from = explode( $from_sep,$from );
		$to = explode( $to_sep,$to );
		$keys = array();
		foreach( $from as $i => $part ) {
			if ( ( $key = array_search( $part,$to ) ) === false ) {
				throw new app_exception('Incompatible formats');
			}
			$keys[$key] = $i;
		}
		$data = array();
		foreach( $to as $i => $part ) {
			if ( isset( $keys[$i] ) ) {
				$data[] = $date[$keys[$i]];
			}
		}
		return implode( $to_sep,$data );
	}

	public static function readable( $data,$date=false,$allow_past=true,$allow_future=true ) {
		$periods = array('second','minute','hour','day','week','month','year','decade');
		$lengths = array('60','60','24','7','4.35','12','10');
		$now = time();
		$unix_date = ( $date == true ? strtotime( $data ) : $data );
		if ( empty( $unix_date ) ) {   
			return false;
		}
		if ( $now > $unix_date ) {
			$difference = ( $allow_past == false ? 0 : ( $now - $unix_date ) );
			$tense = ( $allow_past == false ? '' : ' ago' );
		}
		else {
			$difference = ( $allow_future == false ? 0 : ( $unix_date - $now ) );
			$tense = ( $allow_future == false ? '' : ' from now' );
		}
		for( $j=0;$difference >= $lengths[$j] && $j < ( count( $lengths ) - 1 );$j++ ) {
			$difference /= $lengths[$j];
		}
		$difference = round($difference);
		if ( $difference < 1 || $difference > 1 ) {
			$periods[$j] .= 's';
		}
		return "{$difference} {$periods[$j]}{$tense}";
	}

	public static function readable_seconds( $seconds,$use=null,$labels=array(),$show_zero=false ) {
		if ( !is_null( $use ) ) {
			$use = explode( ',',$use );
		}
		$times = array(
			'y' => ( 60 * 60 * 24 * 365 ),
			'd' => ( 60 * 60 * 24 ),
			'h' => ( 60 * 60 ),
			'm' => 60,
			's' => 1
		);
		$retval = array();
		$zero = !$show_zero;
		foreach( $times as $label => $secs ) {
			if ( !is_null( $use ) && !in_array( $label,$use ) ) {
				continue;
			}
			$val = ( $seconds / $secs );
			$floor = floor( $val );
			if ( (int) $floor !== 0 ) {
				$zero = false;
			}
			$rmd = ( $val - $floor );
			if ( $rmd >= 0 ) {
				if ( $zero == true ) {
					continue;
				}
				$retval[] = $floor . ( isset( $labels[$label] ) ? $labels[$label] : $label );
				if ( $rmd == 0 ) {
					break;
				}
				$seconds = ( $seconds - ( $secs * $floor ) );
			}
		}
		return implode( ' ',$retval );
	}

}

?>