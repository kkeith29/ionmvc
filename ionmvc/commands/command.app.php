<?php

namespace ionmvc\commands;

use ionmvc\classes\array_func;
use ionmvc\classes\cli;
use ionmvc\classes\config;
use ionmvc\classes\filesystem as fs;
use ionmvc\classes\func;
use ionmvc\classes\path;
use ionmvc\classes\template;
use ionmvc\exceptions\app as app_exception;

class app {

	public function init_command() {
		$config_path = PATH_ROOT . 'config';
		$success = fs::create_directory( $config_path,[
			'mode'     => config::get('filesystem.modes.default.directory'),
			'recreate' => true
		] );
		if ( !$success ) {
			throw new app_exception('Unable to create config directory');
		}
		$success = fs::create_file( $config_path . '/app.php',[
			'mode'     => config::get('filesystem.modes.default.file'),
			'contents' => template::fetch( 'config/app',[
				'security.salt' => str_replace( "'","\\'",func::rand_string( 50,'alpha,numeric,special' ) )
			] )
		] );
		if ( !$success ) {
			throw new app_exception('Unable to create config file');
		}
		cli::line( 'IonMVC framework initialized' );
		cli::line( 'Configuration file has been placed at: %s',$config_path );
		cli::line( 'Make any configuration changes you need and run: app:install' );
	}

	public function install_command() {
		$config_file = PATH_ROOT . "/config/app.php";
		if ( !fs::file_exists( $config_file ) ) {
			cli::line('Initializing');
			$this->init_command();
		}
		$paths = path::get_all();
		$skip = ['root','config','ionmvc','public'];
		$paths = array_func::filter( $paths,function( $key,$value ) use ( $skip ) {
			foreach( $skip as $str ) {
				if ( strpos( $key,$str ) !== 0 ) {
					continue;
				}
				return false;
			}
			return true;
		} );
		uasort( $paths,function( $a,$b ) {
			$ac = substr_count( $a,'/' );
			$bc = substr_count( $b,'/' );
			if ( $ac === $bc ) {
				return 0;
			}
			if ( $ac < $bc ) {
				return -1;
			}
			return 1;
		} );
		$config = [
			'mode' => config::get('filesystem.modes.default.directory')
		];
		cli::line('Creating necessary directories');
		foreach( $paths as $id => $path ) {
			if ( fs::create_directory( $path,$config ) !== false ) {
				continue;
			}
			throw new app_exception( 'Unable to create path for: %s',$id );
		}
		cli::line('Moving configuration into application directory');
		cli::line('Installation successful');
	}

	public function version_command() {
		cli::line( 'IonMVC PHP 5.4+ Framework' );
		cli::line( 'Version: %s',\ionmvc\VERSION );
	}

}

?>