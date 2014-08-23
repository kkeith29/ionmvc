<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;

class finder {

	const file_only = 1;
	const directory_only = 2;

	const prepend   = 1;
	const append    = 2;
	const overwrite = 3;

	private $paths = array();
	private $filters = array();
	private $config = array(
		'recursive' => true,
		'skip'      => array('.','..','.DS_Store','Thumbs.db'),
		'full_path' => false,
		'max_depth' => false,
		'dir_only'  => false
	);
	private $data = array();

	public function path( $path ) {
		if ( !is_dir( $path ) ) {
			throw new app_exception( 'Path does not exist: %s',$path );
		}
		$this->paths[] = rtrim( $path,'/' ) . '/';
	}

	public function filter( $regex,$action=self::append ) {
		switch( $action ) {
			case self::prepend:
				array_unshift( $this->filters,$regex );
			break;
			case self::append:
				$this->filters[] = $regex;
			break;
			case self::overwrite:
				$this->filters = array( $regex );
			break;
		}
	}

	public function config( $key,$value ) {
		if ( !isset( $this->config[$key] ) ) {
			throw new app_exception('Config item not found');
		}
		$this->config[$key] = $value;
	}

	private function read( $path,$base='',$depth=0 ) {
		if ( $this->config['max_depth'] !== false && $depth > $this->config['max_depth'] ) {
			return;
		}
		$path = rtrim( $path,'/' ) . '/';
		if ( ( $data = @scandir( $path ) ) === false ) {
			throw new app_exception( 'Unable to scan directory: ' . $path );
		}
		foreach( $data as $datum ) {
			if ( in_array( $datum,$this->config['skip'] ) ) {
				continue;
			}
			$_path = $path . $datum;
			if ( is_dir( $_path ) ) {
				if ( $this->config['dir_only'] ) {
					$this->data[] = ( $this->config['full_path'] ? $_path : $base . $datum );
				}
				if ( $this->config['recursive'] ) {
					$this->read( $_path,$base . $datum . '/',( $depth + 1 ) );
				}
			}
			elseif ( !$this->config['dir_only'] && is_file( $_path ) ) {
				if ( count( $this->filters ) > 0 ) {
					foreach( $this->filters as $filter ) {
						if ( preg_match( '#' . str_replace( '#','\#',$filter ) . '#',$datum ) !== 1 ) {
							continue 2;
						}
					}
				}
				$this->data[] = ( $this->config['full_path'] ? $_path : $base . $datum );
			}
		}
	}

	public function go() {
		foreach( $this->paths as $path ) {
			$this->read( $path );
		}
		return $this->data;
	}

}

?>