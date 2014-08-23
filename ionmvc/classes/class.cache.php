<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;

class cache {

	const second = 1;
	const minute = 60;
	const hour   = 3600;
	const day    = 86400;
	const week   = 604800;

	const lock_retries = 50;

	private $path = null;
	private $id = null;
	private $group = null;
	private $min_time = null;
	private $serialize = true;
	private $expiration = null;
	private $lock_file = null;

	public function __construct( $path=null ) {
		if ( !is_null( $path ) ) {
			$this->set_path( $path );
		}
	}

	public function get_path() {
		if ( is_null( $this->path ) ) {
			throw new app_exception('Path is not set');
		}
		if ( is_null( $this->id ) ) {
			throw new app_exception('Cache id is required');
		}
		return $this->path . '/' . ( !is_null( $this->group ) ? "{$this->group}." : '' ) . "{$this->id}.cache";
	}

	public function set_path( $path ) {
		if ( ( $this->path = path::get( $path,'storage-cache' ) ) === false ) {
			throw new app_exception( 'Unable to find cache path %s',$path );
		}
		$this->path = rtrim( $this->path,'/' );
		return $this;
	}

	public function serialize( $bool ) {
		$this->serialize = $bool;
		return $this;
	}

	public function id( $id ) {
		$this->id = md5( $id );
		return $this;
	}

	public function group( $name ) {
		$this->group = md5( $name );
		return $this;
	}

	public function min_time( $time ) {
		$this->min_time = $time;
		return $this;
	}

	public function exists() {
		return file_exists( $this->get_path() );
	}

	public function fetch( $time=null,$type=null,$callback=null ) {
		if ( !$this->exists() ) {
			if ( !is_null( $callback ) ) {
				$cache_data = call_user_func( $callback );
				$this->set_data( $cache_data );
				return $cache_data;
			}
			return false;
		}
		$path = $this->get_path();
		if ( is_null( $time ) && is_null( $type ) ) {
			$cache_data = file_get_contents( $path );
			if ( preg_match( '#\[expiration\]([0-9]+)\[\/expiration\]#',$cache_data,$match ) !== 1 ) {
				return false;
			}
			$expiration = $match[1];
			if ( $this->expired( $expiration ) ) {
				if ( !is_null( $callback ) ) {
					$cache_data = call_user_func( $callback );
					$this->set_data( $cache_data );
					return $cache_data;
				}
				return false;
			}
			$cache_data = str_replace( $match[0],'',$cache_data );
		}
		else {
			if ( $this->expired( $time,$type ) ) {
				if ( !is_null( $callback ) ) {
					$cache_data = call_user_func( $callback );
					$this->set_data( $cache_data );
					return $cache_data;
				}
				return false;
			}
			$cache_data = file_get_contents( $path );
		}
		if ( $this->serialize == true ) {
			$cache_data = unserialize( $cache_data );
		}
		return $cache_data;
	}

	public function expired( $time=5,$type=null ) {
		$path = $this->get_path();
		if ( !file_exists( $path ) || ( $cache_mtime = filemtime( $path ) ) < ( time() - ( is_null( $type ) ? $time : ( $time * $type ) ) ) || ( !is_null( $this->min_time ) && $this->min_time > $cache_mtime ) ) {
			return true;
		}
		return false;
	}

	public function expiration( $time,$type=self::minute ) {
		$this->expiration = ( $time * $type );
		return $this;
	}

	public function set_data( $data ) {
		$path = $this->get_path();
		if ( ( $fp = fopen( $path,'wb' ) ) !== false && flock( $fp,LOCK_EX ) ) {
			if ( $this->serialize == true ) {
				$data = serialize( $data );
			}
			if ( !is_null( $this->expiration ) ) {
				$data = "[expiration]{$this->expiration}[/expiration]" . $data;
			}
			fwrite( $fp,$data );
			flock( $fp,LOCK_UN );
			fclose( $fp );
			chmod( $path,0666 );
			return true;
		}
		return false;
	} 

	public function clear() {
		$path = $this->get_path();
		if ( file_exists( $path ) ) {
			unlink( $path );
			return true;
		}
		return false;
	}

	private function acquire_lock() {
		$fh = fopen( "{$this->path}/cache.lock",'w' );
		$retries = 0;
		while( !flock( $fh,LOCK_EX ) ) {
			if ( $retries == self::lock_retries ) {
				return false;
			}
			if ( $retries > 0 ) {
				usleep( rand( 1,10000 ) );
			}
			$retries++;
		}
		fwrite( $fh,time() );
		$this->lock_file = $fh;
		return true;
	}

	private function release_lock() {
		flock( $this->lock_file,LOCK_UN );
		fclose( $this->lock_file );
	}

	public function clear_all( $group=null ) {
		if ( !$this->acquire_lock() ) {
			return false;
		}
		$files = scandir( $this->path );
		foreach( $files as $file ) {
			if ( $file == '.' || $file == '..' || ( !is_null( $group ) && ( ( $pos = strpos( $file,'.' ) ) === false || $pos == ( strlen( $file ) - 6 ) ) ) ) {
				continue;
			}
			if ( !is_null( $group ) && substr( $file,0,$pos ) !== $group ) {
				continue;
			}
			unlink( "{$this->path}/{$file}" );
		}
		$this->release_lock();
	}

	public function clear_group( $group=null ) {
		if ( is_null( $group ) && is_null( $this->group ) ) {
			throw new app_exception('Group is required to clear all group files');
		}
		$this->clear_all(( is_null( $group ) ? $this->group : md5( $group ) ));
	}

}

?>