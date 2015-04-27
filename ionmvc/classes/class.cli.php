<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;

class cli {

	private $args;
	private $in;
	private $out;
	private $error;

	public static function __callStatic( $method,$args ) {
		$class = request::resource_id();
		$method = "_{$method}";
		if ( !method_exists( $class,$method ) ) {
			throw new app_exception( 'Unable to find method: %s',$method );
		}
		return call_user_func_array( [ $class,$method ],$args );
	}

	public function __construct( input $input,$config=[] ) {
		$this->args = $input->server( 'argv',[] );
		array_shift( $this->args );
		$this->args = $this->parse_args( $this->args );
		foreach( ['in','out','error'] as $key ) {
			if ( !isset( $config[$key] ) ) {
				throw new app_exception( 'Config item missing: %s',$key );
			}
			$this->{$key} = $config[$key];
		}
	}

	public function __call( $method,$args ) {
		$method = "_{$method}";
		if ( !method_exists( $this,$method ) ) {
			throw new app_exception( 'Unable to find method: %s',$method );
		}
		return call_user_func_array( [ $this,$method ],$args );
	}

	private function parse_args( $args ) {
		if ( !is_array( $args ) ) {
			$args = array_filter( explode( ' ',$args ) );
		}
		$out = [];
		foreach( $args as $arg ) {
			if ( substr( $arg,0,2 ) == '--' ) {
				$pos = strpos( $arg,'=' );
				if ( $pos === false ) {
					$key = substr( $arg,2 );
					$out[$key] = ( isset( $out[$key] ) ? $out[$key] : true );
				}
				else {
					$key = substr( $arg,2,( $pos - 2 ) );
					$out[$key] = substr( $arg,( $pos + 1 ) );
				}
			}
			else if ( substr( $arg,0,1 ) == '-' ) {
				if ( substr( $arg,2,1 ) == '=' ) {
					$key = substr( $arg,1,1 );
					$out[$key] = substr( $arg,3 );
				}
				else {
					$chars = str_split( substr( $arg,1 ) );
					foreach( $chars as $char ) {
						$key = $char;
						$out[$key] = ( isset( $out[$key] ) ? $out[$key] : true );
					}
				}
			}
			else {
				$out[] = $arg;
			}
		}
		return $out;
	}

	public function _all_args() {
		return $this->args;
	}

	public function _has_arg( $idx ) {
		return isset( $this->args[$idx] );
	}

	public function _arg( $idx,$retval=false ) {
		if ( !isset( $this->args[$idx] ) ) {
			return $retval;
		}
		return $this->args[$idx];
	}

	public function _args_to_string( $subcmd=true ) {
		$cmd = '';
		foreach( $this->args as $key => $arg ) {
			if ( is_numeric( $key ) ) {
				if ( !$subcmd ) {
					continue;
				}
				$cmd .= " {$arg}";
				continue;
			}
			if ( strlen( $key ) === 1 ) {
				$cmd .= " -{$key}";
				continue;
			}
			$cmd .= " --{$key}" . ( $arg !== true ? "={$arg}" : '' );
		}
		return trim( $cmd );
	}

	private function call( $method,$args ) {
		return call_user_func_array( [ $this,$method ],$args );
	}

	private function render() {
		$args = func_get_args();
		$data = array_shift( $args );
		if ( count( $args ) === 0 ) {
			return $data;
		}
		$data = preg_replace( '#(%([^\w]|$))#','%$1',$data );
		return vsprintf( $data,$args );
	}

	public function _out() {
		fwrite( $this->out,$this->call( 'render',func_get_args() ) );
	}

	public function _line() {
		$args = array_merge( func_get_args(),[''] );
		$args[0] .= "\n";
		$this->call( '_out',$args );
	}

	public function _error() {
		$args = array_merge( func_get_args(),[''] );
		$args[0] .= "\n";
		fwrite( $this->error,$this->call( 'render',$args ) );
	}

	public function _input() {
		$line = fgets( $this->in );
		if ( $line === false ) {
			throw new app_exception('Caught ^D during input');
		}
		return trim( $line );
	}

	public function _prompt( $question,$config=[] ) {
		if ( isset( $config['default'] ) && strpos( $question,'[' ) === false ) {
			$question .= " [{$config['default']}]";
		}
		if ( !isset( $config['marker'] ) ) {
			$config['marker'] = ': ';
		}
		while(true) {
			$this->_out( $question . $config['marker'] );
			$line = $this->_input();
			if ( $line !== '' ) {
				if ( isset( $config['validation'] ) && is_callable( $config['validation'] ) && !call_user_func( $config['validation'],$line ) ) {
					continue;
				}
				return $line;
			}
			if ( isset( $config['default'] ) ) {
				return $config['default'];
			}
		}
	}

	public function _menu( $options,$config=[] ) {
		if ( isset( $config['default'] ) && !isset( $options[$config['default']] ) ) {
			throw new app_exception('Default selection must be a valid option');
		}
		if ( !isset( $config['title'] ) ) {
			$config['title'] = 'Choose an item';
		}
		if ( isset( $config['default'] ) && strpos( $config['title'],'[' ) === false ) {
			$config['title'] .= " [{$options[$config['default']]}]";
		}
		$list = array_values( $options );
		if ( isset( $config['cancel'] ) && $config['cancel'] ) {
			$cancel_idx = count( $list );
			$list[$cancel_idx] = ( isset( $config['cancel_title'] ) ? $config['cancel_title'] : 'Cancel' );
		}
		$list_count = count( $list );
		foreach( $list as $idx => $item ) {
			$this->_line( '  %d. %s',( $idx + 1 ),$item );
		}
		$this->_line();
		while(true) {
			$this->_out( '%s: ',$config['title'] );
			$line = $this->_input();
			if ( is_numeric( $line ) ) {
				$line = ( (int) $line - 1 );
				if ( isset( $cancel_idx ) && $line === $cancel_idx ) {
					return false;
				}
				if ( isset( $list[$line] ) ) {
					return array_search( $list[$line],$options );
				}
				if ( $line < 0 || $line >= $list_count ) {
					$this->_error('Invalid menu selection: out of range');
				}
			}
			elseif ( isset( $config['default'] ) ) {
				return $config['default'];
			}
		}
	}

	public function _choose( $question,$choices=['y','n'],$config=[] ) {
		if ( !is_array( $choices ) ) {
			$choices = str_split( $choices );
		}
		$choices = $_choices = array_map( 'strtolower',$choices );
		$prompt_config = [];
		if ( isset( $config['default'] ) && ( $d_key = array_search( $config['default'],$_choices ) ) !== false ) {
			$_choices[$d_key] = strtoupper( $_choices[$d_key] );
			$prompt_config['default'] = $config['default'];
		}
		$_choices = trim( implode( '/',$_choices ),'/' );
		while(true) {
			$line = $this->_prompt( "{$question} [{$_choices}]",$prompt_config );
			$line = strtolower( $line );
			if ( in_array( $line,$choices ) ) {
				return $line;
			}
			if ( isset( $config['default'] ) ) {
				return $config['default'];
			}
		}
	}

	public function _confirm( $question,$default=false ) {
		$config = [];
		if ( $default !== false ) {
			$config['default'] = $default;
		}
		return $this->_choose( $question,['y','n'],$config );
	}

}

?>