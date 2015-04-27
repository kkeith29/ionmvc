<?php

namespace ionmvc\classes;

use ionmvc\classes\html\tag;

abstract class array_func {

	const trim_left  = 1;
	const trim_right = 2;

	public static function get( $array,$key,$retval=false,$sep='.' ) {
		if ( is_null( $key ) ) {
			return $array;
		}
		if ( isset( $array[$key] ) ) {
			return $array[$key];
		}
		foreach( explode( $sep,$key ) as $_key ) {
			if ( !is_array( $array ) || !array_key_exists( $_key,$array ) ) {
				return $retval;
			}
			$array = $array[$_key];
		}
		return $array;
	}

	public static function set( &$array,$key,$value,$sep='.' ) {
		if ( isset( $array[$key] ) ) {
			$array[$key] = $value;
			return;
		}
		$keys = explode( $sep,$key );
		while( count( $keys ) > 1 ) {
			$key = array_shift( $keys );
			if ( !isset( $array[$key] ) || !is_array( $array[$key] ) ) {
				$array[$key] = [];
			}
			$array =& $array[$key];
		}
		$array[array_shift( $keys )] = $value;
	}

	public static function trim( $array,$amount=1,$position=self::trim_left ) {
		if ( $amount >= count( $array ) ) {
			return $array;
		}
		for ( $i=1;$i <= $amount;$i++ ) {
			switch( $position ) {
				case self::trim_left:
					array_shift( $array );
				break;
				case self::trim_right:
					array_pop( $array );
				break;
			}
		}
		return $array;
	}

	public static function map_recursive( $func,$array ) {
		if ( !is_array( $array ) ) {
			return false;
		}
		$narray = [];
		foreach( $array as $key => $value ) {
			$narray[$key] = ( is_array( $value ) ? self::map_recursive( $func,$value ) : ( is_array( $func ) ? call_user_func_array( $func,$value ) : $func( $value ) ) );
		}
		return $narray;
	}

	public static function flatten( $array ) {
		$parts = [];
		foreach( $array as $key => $val ) {
			if ( is_array( $val ) ) {
				$parts = array_merge( $parts,self::flatten( $val ) );
			}
			else {
				$parts[] = $val;
			}
		}
		return $parts;
	}

	public static function flatten_key( $array,$prepend='',$sep='.' ) {
		$retval = [];
		foreach( $array as $key => $value ) {
			if ( is_array( $value ) ) {
				$retval = array_merge( $retval,self::flatten_key( $value,$prepend.$sep.$key,$sep ) );
				continue;
			}
			$retval[$prepend.$sep.$key] = $value;
		}
		return $retval;
	}

	public static function expand_key( $array,$sep='.' ) {
		$retval = [];
		foreach( $array as $key => $value ) {
			self::set( $retval,$key,$value,$sep );
		}
		return $retval;
	}

	public static function assoc( $array ) {
		$retval = [];
		$chunks = array_chunk( $array,2 );
		foreach( $chunks as $chunk ) {
			if ( count( $chunk ) == 2 ) {
				list( $key,$val ) = $chunk;
				$retval[$key] = $val;
			}
		}
		return $retval;
	}

	public static function merge( $array1,$array2 ) {
		foreach( $array2 as $key => $value ) {
			$array1[$key] = $value;
		}
		return $array1;
	}

	public static function merge_recursive_distinct( $a1,$a2 ) {
		$arrays = func_get_args();
		$base = array_shift( $arrays );
		if ( !is_array( $base ) ) {
			$base = ( empty( $base ) ? [] : [ $base ] );
		}
		foreach( $arrays as $array ) {
			if ( !is_array( $array ) ) {
				$array = [ $array ];
			}
			foreach( $array as $key => $value ) {
				if ( !array_key_exists( $key,$base ) && !is_numeric( $key ) ) {
					$base[$key] = $array[$key];
					continue;
				}
				if ( is_array( $value ) || ( isset( $base[$key] ) && is_array( $base[$key] ) ) ) {
					$base[$key] = self::merge_recursive_distinct( ( isset( $base[$key] ) ? $base[$key] : [] ),$array[$key] );
				}
				elseif ( is_numeric( $key ) ) {
					if ( !in_array( $value,$base ) ) {
						$base[] = $value;
					}
				}
				else {
					$base[$key] = $value;
				}
			}
		}
		return $base;
	}

	public static function implode( $array,$sep='' ) {
		$retval = [];
		foreach( $array as $key => $value ) {
			$retval[] = "{$key}{$sep}{$value}";
		}
		return $retval;
	}

	public static function diff_both( $array_1,$array_2 ) {
		return [ array_diff( $array_1,$array_2 ),array_diff( $array_2,$array_1 ) ];
	}

	public static function to_xml( $array,$level=0,$new_key=null ) {
		$xml = '';
		$_attrs = [];
		if ( isset( $array['_attrs'] ) && !is_null( $new_key ) ) {
			$_attrs = $array['_attrs'];
			unset( $array['_attrs'] );
		}
		foreach( $array as $key => $val ) {
			$arr = false;
			$multi = false;
			if ( is_array( $val ) ) {
				$arr = true;
				if ( count( array_filter( array_keys( $val ),'is_numeric' ) ) > 0 ) {
					$multi = true;
				}
			}
			if ( $multi == false ) {
				$attrs = [];
				if ( !is_null( $new_key ) ) {
					$key = $new_key;
					$attrs = $_attrs;
				}
				if ( $arr == true && isset( $val['_attrs'] ) ) {
					$attrs = $val['_attrs'];
				}
				$xml .= str_repeat( "\t",$level ) . "<{$key}" . ( count( $attrs ) > 0 ? tag::build_attrs( $attrs,null,true ) : '' ) . '>';
			}
			if ( $arr == true ) {
				if ( isset( $val['_attrs'] ) && $multi == false ) {
					unset( $val['_attrs'] );
				}
				$xml .= ( $multi == true ? self::to_xml( $val,$level,$key ) : ( !isset( $val['_value'] ) ? "\n" . self::array_to_xml( $val,( $level + 1 ) ) . str_repeat( "\t",$level ) : $val['_value'] ) );
			}
			else {
				$xml .= ( $arr == true && isset( $val['_value'] ) ? $val['_value'] : $val );
			}
			if ( $multi == false ) {
				$xml .= "</{$key}>\n";
			}
		}
		return $xml;
	}

	public static function is_assoc( $array ) {
		if ( !is_array( $array ) ) {
			return false;
		}
		krsort( $array,\SORT_STRING );
		return !is_numeric( key( $array ) );
	}

	public static function split( $array,$divisor=2,$pkeys=false ) {
		if ( count( $array ) == 0 ) {
			return $array;
		}
		$data = array_chunk( $array,ceil(( count( $array ) / $divisor )),$pkeys );
		if ( count( $data ) == $divisor ) {
			return $data;
		}
		for( $i=0;$i < $divisor;$i++ ) {
			if ( !isset( $data[$i] ) ) {
				$data[$i] = [];
			}
		}
		return $data;
	}

	public static function shuffle_assoc( &$array ) {
		$keys = array_keys( $array );
        shuffle( $keys );
        $new = [];
		foreach( $keys as $key ) {
			$new[$key] = $array[$key];
		}
		$array = $new;
		return true;
    }

	public static function reindex( $array ) {
		array_unshift( $array,false );
		unset( $array[0] );
		return $array;
	}

	public static function unshift_assoc( &$array,$key,$value ) {
		$array = array_reverse( $array,true );
		$array[$key] = $value;
		return array_reverse( $array,true );
	}

	public static function filter( $array,\Closure $func ) {
		$retval = [];
		foreach( $array as $key => $value ) {
			if ( !call_user_func( $func,$key,$value ) ) {
				continue;
			}
			$retval[$key] = $value;
		}
		return $retval;
	}

}

?>