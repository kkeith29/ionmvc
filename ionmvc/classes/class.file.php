<?php

namespace ionmvc\classes;

abstract class file {

	const downloadable = 1;
	const not_downloadable = 2;
	const force_download = 3;

	public static function uri_file( $file,$config=array() ) {
		$config['csm'] = true;
		$config['extn'] = self::get_extension( $file );
		return uri::create( 'app/file/local/' . uri::base64_encode( $file ),$config );
	}

	public static function uri_db( $id,$config=array() ) {
		$config['csm'] = true;
		return uri::create("app/file/fetch/{$id}",$config );
	}

    public static function get_extension( $file ) {
        return strtolower( pathinfo( $file,PATHINFO_EXTENSION ) );
    }

	public static function format_filesize( $bytes,$p=2 ) {
		$units = array('B','KB','MB','GB','TB');
		$bytes = max( $bytes,0 );
		$pow = min( floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) ),( count( $units ) - 1 ) );
		$bytes /= pow( 1024,$pow );
		return round( $bytes,$p ) . ' ' . $units[$pow];
	}

	public static function to_bytes( $str ) {
		$sizes = array('KB','MB','GB','TB');
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

	public static function download( $file,$name=null,$mime=null,$data=null ) {
		output::compression(false);
		if ( is_null( $mime ) && ( $mime = http::mime_type( file::get_extension( $file ) ) ) === false ) {
			throw new app_exception('Mime type for file not found');
		}
		http::content_type( $mime );
		http::header('Content-Disposition: attachment; filename="' . ( is_null( $name ) ? basename( $file ) : $name ) . '"');
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
		app::terminate();
		exit;
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
		app::terminate();
		exit;
	}

}

/* TABLE STRUCTURE
CREATE TABLE IF NOT EXISTS `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `base` varchar(255) NOT NULL,
  `file` varchar(50) NOT NULL,
  `path` text NOT NULL,
  `type` varchar(50) NOT NULL,
  `size` int(11) NOT NULL,
  `download` tinyint(1) NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
*/

?>