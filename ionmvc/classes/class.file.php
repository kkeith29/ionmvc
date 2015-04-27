<?php

namespace ionmvc\classes;

abstract class file {

    public static function get_extension( $file ) {
        return strtolower( pathinfo( $file,PATHINFO_EXTENSION ) );
    }

	public static function format_filesize( $bytes,$p=2 ) {
		$units = ['B','KB','MB','GB','TB'];
		$bytes = max( $bytes,0 );
		$pow = min( floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) ),( count( $units ) - 1 ) );
		$bytes /= pow( 1024,$pow );
		return round( $bytes,$p ) . ' ' . $units[$pow];
	}

	public static function to_bytes( $str ) {
		$sizes = ['KB','MB','GB','TB'];
		$multi = 1024;
		foreach( $sizes as $size ) {
			if ( strpos( $str,$size ) === false ) {
				$multi *= 1024;
				continue;
			}
			return ( (int) str_replace( $size,'',$str ) * $multi );
		}
		return false;
	}

	public static function sanitize_name( $name ) {
		$extn = self::get_extension( $name );
		return trim( preg_replace( '#[_]{2,}#','_',preg_replace( '#[^a-zA-Z0-9_]+#','_',substr( $name,0,( strlen( $name ) - ( strlen( $extn ) + 1 ) ) ) ) ),'_' ) . ".{$extn}";
	}

	public static function shorten_name( $filename,$chars ) {
		$length = strlen( $filename );
		if ( $length <= $chars ) {
			return $filename;
		}
		$extn = false;
		if ( ( $pos = strrpos( $filename,'.' ) ) !== false ) {
			$name = substr( $filename,0,$pos );
			$extn = substr( $filename,( $pos + 1 ) );
		}
		$remove = ( $length - $chars ) + 3;
		return substr( $name,0,( strlen( $name ) - $remove ) ) . '...' . ( $extn !== false ? ".{$extn}" : '' );
	}

	public static function download( $file,$name=null,$mime=null,$data=null ) {
		output::compression(false);
		if ( is_null( $mime ) && ( $mime = http::mime_type( file::get_extension( $file ) ) ) === false ) {
			throw new app_exception('Mime type for file not found');
		}
		http::content_type( $mime );
		http::header('Content-Disposition: attachment; filename="' . self::sanitize_name(( is_null( $name ) ? basename( $file ) : $name )) . '"');
		http::header('Expires: 0');
		http::header('Content-Transfer-Encoding: binary');
		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && strpos( $_SERVER['HTTP_USER_AGENT'],'MSIE' ) !== false ) {
			http::header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			http::header('Pragma: public');
		}
		else {
			http::header('Pragma: no-cache');
		}
		http::header('Content-Length: '. ( is_null( $data ) ? filesize( $file ) : strlen( $data ) ));
		$type = output::append;
		if ( is_null( $data ) ) {
			$type = output::file;
			$data = $file;
		}
		output::set_data( $data,$type );
		app::stop();
	}

	public static function output( $file,$mime=null,$data=null ) {
		output::compression(false);
		if ( is_null( $mime ) && ( $mime = http::mime_type( file::get_extension( $file ) ) ) === false ) {
			throw new app_exception('Mime type for file not found');
		}
		http::content_type( $mime );
		$type = output::append;
		if ( is_null( $data ) ) {
			$type = output::file;
			$data = $file;
		}
		output::set_data( $data,$type );
		app::stop();
	}

	public static function get_data( $path,$config=[] ) {
		if ( !file_exists( $path ) ) {
			return false;
		}
		if ( isset( $config['raw'] ) && $config['raw'] ) {
			return file_get_contents( $path );
		}
		if ( isset( $config['vars'] ) ) {
			extract( $config['vars'] );
		}
		ob_start();
		include $path;
		$data = ob_get_contents();
		ob_end_clean();
		return $data;
	}

}

?>