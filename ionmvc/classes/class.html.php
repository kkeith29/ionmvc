<?php

namespace ionmvc\classes;

use ionmvc\exceptions\app as app_exception;

class html {

	const expire = 3600;

	const before = 1;
	const after = 2;

	private static $output = true;

	private $doctypes = array(
		'html5'              => '<!DOCTYPE html>',
		'html-strict'        => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">',
		'html-transitional'  => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">',
		'xhtml-strict'       => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
		'xhtml-transitional' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
		'xhtml-basic'        => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">'
	);
	private $doctype = 'html5';
	private $lang = 'en';
	private $head = array(
		'title'     => array(),
		'title-sep' => ' | ',
		'title-rev' => true,
		'meta'      => array(),
		'link'      => array(),
		'style'     => array(),
		'script'    => array(),
		'insert'    => array()
	);
	private $body = array(
		'events'  => array(),
		'prepend' => array(),
		'append'  => array(),
		'js-eof'  => array()
	);

	public static function __callStatic( $method,$args ) {
		array_unshift( $args,$method );
		return call_user_func_array( array( app::html(),'_methods' ),$args );
	}

	public function __construct() {
		app::register('html_header',function() {
			app::html()->output_header();
		});
		app::register('html_footer',function() {
			app::html()->output_footer();
		});
	}

	public function _methods() {
		$args = func_get_args();
		$args = array_func::flatten( $args );
		switch( $args[0] ) {
			case 'doctype':
				$this->doctype = $args[1];
			break;
			case 'lang':
				$this->lang = $data;
			break;
			case 'title':
				$this->head['title'][] = $args[1];
			break;
			case 'title_separator':
				$this->head['title_sep'] = $args[1];
			break;
			case 'title_reverse':
				$this->head['title_rev'] = ( isset( $args[1] ) ? $args[1] : true );
			break;
			case 'meta_name':
				if ( is_array( $args[2] ) ) {
					$args[2] = implode( ',',$args[2] );
				}
				$this->head['meta'][] = "<meta name=\"{$args[1]}\" content=\"{$args[2]}\" />";
			break;
			case 'meta_http_equiv':
				if ( $args[1] == 'refresh' ) {
					$args[2] = $args[2][0] . ( isset( $args[2][1] ) ? ";url={$args[2][1]}" : '' );
				}
				$this->head['meta'][] = "<meta http-equiv=\"{$args[1]}\" content=\"{$args[2]}\" />";
			break;
			case 'meta_charset':
				$this->head['meta'][] = "<meta charset=\"{$args[1]}\" />";
			break;
			case 'link':
				$this->head['link'][] = "<link rel=\"{$args[1]}\" " . ( !is_null( $args[2] ) ? "type=\"{$args[2]}\" " : '' ) . "href=\"{$args[3]}\" />";
			break;
			case 'css_external':
				$this->_methods('link','stylesheet','text/css',$args[1]);
			break;
			case 'favicon':
				$this->_methods('link','shortcut icon','image/x-icon',$args[1]);
			break;
			case 'css_embed':
				$this->head['style'][] = "<style type=\"text/css\"><!--\n{$args[1]}\n\t--></style>";
			break;
			case 'js_external':
				$this->head['script'][] = "<script type=\"text/javascript\" src=\"{$args[1]}\"></script>";
			break;
			case 'js_embed':
				$this->head['script'][] = "<script type=\"text/javascript\"><!--\n{$args[1]}\n\t--></script>";
			break;
			case 'head_insert':
				$this->head['insert'][$args[1]][$args[2]][] = $args[3];
			break;
			case 'body_event':
				$this->body['events'][$args[1]][] = $args[2];
			break;
			case 'body_prepend':
				$this->body['prepend'][] = $args[1];
			break;
			case 'body_append':
				$this->body['append'][] = $args[1];
			break;
			case 'js_eof_external':
				$this->body['js-eof'][] = "<script type=\"text/javascript\" src=\"{$args[1]}\"></script>";
			break;
			case 'js_eof_embed':
				$this->body['js-eof'][] = "<script type=\"text/javascript\"><!--\n{$args[1]}\n\t--></script>";
			break;
			case 'clear':
				$this->_clear();
			break;
			default:
				throw new app_exception( "Method '%s' not found!",$args[0] );
			break;
		}
		return true;
	}

	public function _clear() {
		$this->doctype = 'html5';
		$this->lang = 'en';
		$this->head = array(
			'title'     => array(),
			'title-sep' => ' | ',
			'title-rev' => true,
			'meta'      => array(),
			'link'      => array(),
			'style'     => array(),
			'script'    => array(),
			'insert'    => array()
		);
		$this->body = array(
			'events'  => array(),
			'prepend' => array(),
			'append'  => array(),
			'js-eof'  => array()
		);
	}

	private function format( $data,$format=false ) {
		$output = '';
		if ( count( $data ) > 0 ) {
			foreach( $data as $str ) {
				$output .= ( $format ? "\t{$str}\n" : $str );
			}
		}
		return $output;
	}

	public function get( $var,$type='head',$format=false ) {
		if ( isset( $this->{$type}[$var] ) ) {
			switch( $type ) {
				case 'head':
					switch( $var ) {
						case 'title':
							if ( $this->head['title-rev'] ) {
								$this->head['title'] = array_reverse( $this->head['title'] );
							}
							return implode( $this->head['title-sep'],$this->head['title'] );
						break;
						default:
							if ( is_array( $this->head[$var] ) ) {
								return $this->format( $this->head[$var],$format );
							}
						break;
					}
				break;
				case 'body':
					switch( $var ) {
						case 'prepend':
							$this->body[$var] = array_reverse( $this->body[$var] );
							return $this->format( $this->body[$var] );
						break;
						default:
							if ( is_array( $this->body[$var] ) ) {
								return $this->format( $this->body[$var] );
							}
						break;
					}
				break;
			}
			return $this->{$type}[$var];
		}
		return false;
	}

	public function output_header() {
		$html_charset = config::get('html.charset');
		http::content_type('text/html',$html_charset);
		if ( isset( $this->doctypes[$this->doctype] ) ) {
			$html = $this->doctypes[$this->doctype];
			if ( $this->doctype == 'html5' ) {
				$this->_methods('meta_charset',$html_charset);
			}
			else {
				$this->_methods('meta_http_equiv','Content-Type','text/html; charset=' . $html_charset);
			}
		}
		else {
			$html = $this->doctype;
		}
		$html .= "\n<html" . ( strpos( $this->doctype,'xhtml' ) !== false ? " xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"{$this->lang}\"" : '' ) . " lang=\"{$this->lang}\">\n";
		$html .= "<head>\n\t<title>" . $this->get('title') . "</title>\n";
		foreach( array('meta','link','style','script') as $type ) {
			if ( isset( $this->head['insert'][$type][self::before] ) ) {
				$html .= implode( '',$this->head['insert'][$type][self::before] );
			}
			$html .= $this->get( $type,'head',true );
			if ( isset( $this->head['insert'][$type][self::after] ) ) {
				$html .= implode( '',$this->head['insert'][$type][self::after] );
			}
		}
		$html .= "</head>\n";
		$events = array();
		foreach( $this->body['events'] as $type => $data ) {
			$events[] = " {$type}=\"" . implode( ';',$data ) . "\"";
		}
		$html .= "<body" . implode( '',$events ) . ">\n";
		foreach( array('prepend') as $type ) {
			$html .= $this->get( $type,'body' );
		}
		output::set_data( $html );
	}
	
	public function output_footer() {
		$html = '';
		foreach( array('append','js-eof') as $type ) {
			$html .= $this->get( $type,'body' );
		}
		$html .= "\n</body>\n";
		$html .= "</html>";
		output::set_data( $html );
	}

	public static function entity_encode( $data ) {
		return htmlentities( $data,ENT_QUOTES,'UTF-8' );
	}

	public static function entity_decode( $data ) {
		return html_entity_decode( $data,ENT_QUOTES,'UTF-8' );
	}

}

?>