<?php

namespace ionmvc\classes\asset;

use ionmvc\classes\asset;
use ionmvc\classes\func;
use ionmvc\classes\path;

class css {

	public static function minify( $css ) {
		$preserve_urls = ( config::get('css.minify.preserve_urls') === true );
		if ( $preserve_urls ) {
			$css = preg_replace_callback( '/url\s*\((.*)\)/siU',__CLASS__ . '::encode_url',$css );
		}
		$css = preg_replace( '/\/\*[\d\D]*?\*\/|\t+/','',$css );
		$css = str_replace( array("\n","\r","\t"),'',$css );
		$css = preg_replace( '/\s\s+/','',$css );
		$css = preg_replace( '/\s*({|}|\[|\]|=|~|\+|>|\||;|:|,)\s*/','$1',$css );
		if ( config::get('css.minify.remove_last_semicolon') === true ) {
			$css = str_replace( ';}','}',$css );
		}
		$css = trim( $css );
		if ( $preserve_urls ) {
			$css = preg_replace_callback( '/url\s*\((.*)\)/siU',__CLASS__ . '::decode_url',$css );
		}
		return $css;
	}

	public static function encode_url( $match ) {
		return 'url(' . base64_encode( trim( $match[1] ) ) . ')';
	}

	public static function decode_url( $match ) {
		return 'url(' . base64_decode( $match[1] ) . ')';
	}

	public static function to_array( $css,$options='' ) {
		$r = array();
		$css = self::minify( $css,$options );
		preg_match_all( '/(.+){(.+:.+);}/U',$css,$items );
		if ( count( $items[0] ) > 0 ) {
			$c = count( $items[0] );
			for( $i=0;$i<$c;$i++ ) {
				$keys = explode( ',',$items[1][$i] );
				$styles_tmp = explode( ';',$items[2][$i] );
				$styles = array();
				foreach( $styles_tmp as $style ) {
					$style_tmp = explode( ':',$style );
					$styles[$style_tmp[0]] = $style_tmp[1];
				}
				$r[] = array(
					'keys' => self::array_clean( $keys ),
					'styles' => self::array_clean( $styles )
				);
			}
		}
		return $r;
	}

	public static function to_string( $array ) {
		$r = '';
		foreach( $array as $item ) {
			$r .= implode( ',',$item['keys'] ) . '{';
			foreach( $item['styles'] as $key => $value ) {
				$r . "{$key}:{$value};";
			}
			$r .= '}';
		}
		return $r;
	}

	public static function array_clean( $array ) {
		$r = array();
		if ( func::array_is_assoc( $array ) ) {
			foreach( $array as $key => $value ) {
				$r[$key] = trim( $value );
			}
		}
		else {
			foreach( $array as $value ) {
				$value = trim( $value );
				if ( $value !== '' ) {
					$r[] = $value;
				}
			}
		}
		return $r;
	}

	public static function handle_images( $data,$workdir ) {
		$old_workdir = getcwd();
		chdir( $workdir );
		$images = array();
		$data = preg_replace_callback( '#url\(([^\)]+)\)#is',function( $match ) use ( &$images ) {
			$i = count( $images );
			$images[$i] = trim( $match[1],'\'"' );
			return "url('{image:{$i}}')";
		},$data );
		$image_path       = path::get('asset-image');
		$third_party_path = path::get('asset-third-party');
		foreach( $images as $i => &$image ) {
			if ( !func::is_url( $image ) ) {
				if ( strpos( $image,'/' ) === 0 ) {
					$image = uri::base() . ltrim( $image,'/' );
				}
				else {
					$image = realpath( $image );
					if ( $image !== false ) {
						if ( strpos( $image,$image_path ) === 0 ) {
							$image = ltrim( str_replace( $image_path,'',$image ),'/' );
						}
						elseif ( strpos( $image,$third_party_path ) === 0 ) {
							$image = ltrim( str_replace( $third_party_path,'',$image ),'/' );
						}
						$image = asset::path( $image );
					}
				}
			}
			$data = str_replace( "{image:{$i}}",( $image !== false ? $image : '' ),$data );
		}
		chdir( $old_workdir );
		return $data;
	}

}

?>