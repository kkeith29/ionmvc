<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;

abstract class error {

	private static $display_styles = true;

	public static function init() {
		set_error_handler( __CLASS__ . '::error_handler' );
		set_exception_handler( __CLASS__ . '::exception_handler' );
		error_reporting( E_ALL | E_STRICT );
		$config = [
			'display_errors' => ( app::env( \ionmvc\ENV_PRODUCTION ) ? 'Off' : 'On' ),
			'log_errors'     => 'On',
			'error_log'      => path::get('storage-log') . config::get('log.php')
		];
		foreach( $config as $name => $value ) {
			ini_set( $name,$value );
		}
	}

	public static function error_handler( $number,$message,$file,$line ) {
		$error_reporting = error_reporting();
		if ( !( $error_reporting & $number ) ) {
			return;
		}
		$severity = 0;
		switch( $number ) {
			case \E_ERROR:
			case \E_USER_ERROR:
			case \E_RECOVERABLE_ERROR:
				$type = 'Fatal Error';
				$severity = 1;
			break;
			case \E_WARNING:
			case \E_USER_WARNING:
				$type = 'Warning';
			break;
			case \E_NOTICE:
			case \E_USER_NOTICE:
				$type = 'Notice';
			break;
			case \E_DEPRECATED:
			case \E_USER_DEPRECATED:
				$type = 'Deprecated';
			break;
			default:
				$type = 'Unknown Error';
				$severity = 1;
			break;
		}
		$exception = new \ErrorException( "{$type}: {$message}",$number,$severity,$file,$line );
		if ( $severity === 1 ) {
			self::exception_handler( $exception );
			exit;
		}
		throw $exception;
	}

	public static function exception_handler( $exception ) {
		self::log( $exception->getMessage() . ' in file ' . $exception->getFile() . ' on line: ' . $exception->getLine() );
		if ( !app::mode( request::mode_uri ) && app::env( \ionmvc\ENV_PRODUCTION ) ) {
			event::trigger('app.error.technical_difficulties');
			app::stop();
		}
		if ( app::mode( request::mode_cli ) ) {
			cli::error( $exception );
			return;
		}
		try {
			echo view::fetch('ionmvc-view:error/exception',[
				'full'      => ( method_exists( $exception,'getSeverity' ) && $exception->getSeverity() === 1 ? true : false ),
				'display_styles' => self::$display_styles,
				'message'   => $exception->getMessage(),
				'file'      => $exception->getFile(),
				'line'      => $exception->getLine(),
				'backtrace' => $exception->getTraceAsString()
			])->render();
		}
		catch( app_exception $e ) {
			echo $e->getMessage();
		}
		self::$display_styles = false;
	}

	public static function log( $message ) {
		\error_log( $message );
	}

	public static function get_backtrace( $index=null ) {
		$trace = debug_backtrace();
		if ( !is_null( $index ) ) {
			isset( $trace[$index] ) or die("Backtrace index '{$index}' not found");
			$trace = [ '0'=>$trace[$index] ];
		}
		$retval = '';
		foreach( $trace as $data ) {
			if ( isset( $data['file'] ) ) {
				$retval .= $data['file'];
			}
			if ( isset( $data['line'] ) ) {
				$retval .= ( isset( $data['file'] ) ? ':' : 'Line: ' ) . $data['line'];
			}
			if ( isset( $data['class'] ) ) {
				$retval .= ( isset( $data['file'] ) || isset( $data['line'] ) ? '    ' : '' ) . $data['class'];
			}
			if ( isset( $data['type'] ) ) {
				$retval .= $data['type'];
			}
			if ( isset( $data['function'] ) ) {
				$retval .= "{$data['function']}(";
				if ( isset( $data['args'] ) ) {
					$args = [];
					foreach( $data['args'] as $arg ) {
						$args[] = "'" . ( is_string( $arg ) ? $arg : gettype( $arg ) ) . "'";
					}
					$retval .= implode( ',',$args );
				}
				$retval .= ')';
			}
			$retval .= "\n";
		}
		return $retval;
	}

}

?>