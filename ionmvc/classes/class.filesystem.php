<?php

namespace ionmvc\classes;

abstract class filesystem {

	public static $ignore = ['.git'];

	public static function is_dot_file( $file ) {
		return ( $item === '.' || $item === '..' );
	}

	public static function mode( $path,$octal ) {
		return chmod( $path,$octal );
	}

	public static function file_exists( $path ) {
		return ( file_exists( $path ) && is_file( $path ) );
	}

	public static function create_file( $path,$config=[] ) {
		if ( ( $fh = fopen( $path,'w' ) ) === false ) {
			return false;
		}
		if ( isset( $config['contents'] ) ) {
			fwrite( $fh,$config['contents'] );
		}
		fclose( $fh );
		if ( isset( $config['mode'] ) ) {
			self::mode( $path,$config['mode'] );
		}
		return true;
	}

	public static function rename_file( $path_from,$path_to ) {
		return rename( $path_from,$path_to );
	}

	public static function delete_file( $path ) {
		return @unlink( $path );
	}

	public static function set_permissions( $dir ) {
		$items = scandir( $dir );
		foreach( $items as $item ) {
			if ( $item == '.' || $item == '..' ) {
				continue;
			}
			$item = $dir . $item;
			if ( is_dir( $item ) ) {
				$octal = 0775;
				chmod( $item,$octal );
				self::set_permissions( "{$item}/" );
			}
			else {
				$octal = 0664;
				chmod( $item,$octal );
			}
		}
	}

	public static function directory_exists( $path ) {
		return is_dir( $path );
	}

	public static function create_directory( $path,$config=[] ) {
		$config = array_merge( [
			'mode'      => 0755,
			'recursive' => false,
			'recreate'  => false
		],$config );
		if ( self::directory_exists( $path ) ) {
			if ( !$config['recreate'] ) {
				return true;
			}
			self::delete_directory( $path );
		}
		return @mkdir( $path,$config['mode'],$config['recursive'] );
	}

	public static function rename_directory( $path_from,$path_to ) {
		return rename( $path_from,$path_to );
	}

	public static function clear_directory( $path,$config=[] ) {
		if ( !isset( $config['delete'] ) ) {
			$config['delete'] = false;
		}
		if ( !$config['delete'] && !self::directory_exists( $path ) ) {
			throw new app_exception( '%s is not a directory',$path );
		}
		foreach( scandir( $path ) as $item ) {
			if ( $item == '.' || $item == '..' ) {
				continue;
			}
			$name = ( isset( $config['name'] ) ? $config['name'] . '/' : '' ) . $item;
			if ( isset( $config['keep'] ) && in_array( $name,$config['keep'] ) ) {
				continue;
			}
			$_path = "{$path}/{$item}";
			if ( self::directory_exists( $_path ) ) {
				$_config = $config;
				$_config['name'] = $name;
				$_config['delete'] = true;
				self::clear_directory( $_path,$_config );
				continue;
			}
			unlink( $_path );
		}
		if ( $config['delete'] ) {
			rmdir( $path );
		}
		return self::directory_exists( $path );
	}

	public static function delete_directory( $path ) {
		return self::clear_directory( $path,[
			'delete' => true
		] );
	}

}

?>