<?php

namespace ionmvc\classes;

use ionmvc\classes\asset\css;
use ionmvc\classes\asset\jquery;
use ionmvc\classes\asset\order;
use ionmvc\exceptions\app as app_exception;

class asset {

	private static $base_uri = null;

	const type_internal = 1;
	const type_external = 2;
	const type_function = 3;

	private $registered_groups = array();
	private $groups = array();

	private $group = false;
	private $assets = array(
		'css' => array(),
		'js'  => array()
	);
	private $order = array(
		'css' => array(),
		'js'  => array()
	);

	public static function __callStatic( $method,$args ) {
		$class = app::asset();
		$method = "_{$method}";
		if ( !method_exists( $class,$method ) ) {
			throw new app_exception( "Method '%s' not found",$method );
		}
		return call_user_func_array( array( $class,$method ),$args );
	}

	public function __construct() {
		app::register('asset',function() {
			app::asset()->handle();
		});
	}

	public function _add( $data,$priority=5,$config=array() ) {
		$info = array();
		if ( is_string( $data ) ) {
			$info['url']  = func::is_url( $data );
			$info['extn'] = ( isset( $config['type'] ) ? $config['type'] : file::get_extension( $data ) );
			switch( $info['extn'] ) {
				case 'css':
					if ( $info['url'] === false && !path::test( path::is_file,$data,'css' ) ) {
						throw new app_exception( "CSS file '%s' not found",$data );
					}
				break;
				case 'js':
					if ( $info['url'] === false && !path::test( path::is_file,$data,'js' ) ) {
						throw new app_exception( "JS file '%s' not found",$data );
					}
				break;
			}
			$info['file'] = $data;
			$key = md5( $data );
		}
		elseif ( $data instanceof \Closure ) {
			if ( !isset( $config['type'] ) ) {
				throw new app_exception('Type is required when using a closure');
			}
			if ( !isset( $this->assets[$config['type']] ) ) {
				throw new app_exception('Type is not valid');
			}
			$info['extn'] = $config['type'];
			$info['func'] = $data;
			$key = count( $this->assets[$info['extn']] );
		}
		else {
			throw new app_exception('Invalid data sent to function');
		}
		$info['priority']  = $priority;
		$info['group']     = $this->group;
		$info['allow_php'] = ( isset( $config['allow_php'] ) ? $config['allow_php'] : false );
		if ( $this->group === false ) {
			$this->assets[$info['extn']][$key] = $info;
			$this->order[$info['extn']][] =& $this->assets[$info['extn']][$key];
		}
		elseif ( !isset( $this->registered_groups[$this->group] ) || !in_array( $key,$this->registered_groups[$this->group] ) ) {
			$this->registered_groups[$this->group][$key] = array(
				'type'  => $info['extn'],
				'asset' => $info
			);
		}
		if ( isset( $config['return_info'] ) && $config['return_info'] === true ) {
			return $info;
		}
	}

	public function _register( $name,\Closure $function ) {
		$this->group = $name;
		$function();
		$this->group = false;
	}

	public function _group( $name ) {
		if ( !isset( $this->registered_groups[$name] ) ) {
			throw new app_exception( "Unable to find group '%s'",$name );
		}
		if ( isset( $this->groups[$name] ) ) {
			return;
		}
		if ( $this->group === false ) {
			$this->groups[$name] = true;
			foreach( $this->registered_groups[$name] as $data ) {
				switch( $data['type'] ) {
					case 'css':
					case 'js':
						$this->order[$data['asset']['extn']][] =& $data['asset'];
					break;
					case 'group':
						$this->_group( $data['name'] );
					break;
				}
			}
		}
		else {
			$this->registered_groups[$this->group][] = array(
				'type' => 'group',
				'name' => $name
			);
		}
	}

	public function handle() {
		$types = array();
		foreach( $this->order as $extn => $order ) {
			foreach( $order as $asset ) {
				if ( !isset( $types[$extn] ) ) {
					$types[$extn] = array();
				}
				$types[$extn][$asset['priority']][] = $asset;
			}
			if ( isset( $types[$extn] ) ) {
				krsort( $types[$extn] );
			}
		}
		$assets = array();
		foreach( $types as $type => $_assets ) {
			if ( !isset( $assets[$type] ) ) {
				$assets[$type] = array();
			}
			foreach( $_assets as $priority => $__assets ) {
				foreach( $__assets as $asset ) {
					$assets[$type][] = $asset;
				}
			}
		}
		$production = config::get('production');
		foreach( $assets as $type => $_assets ) {
			$function = "{$type}_external";
			$order = new order( $_assets );
			$groups = $order->reorder();
			foreach( $groups as $group ) {
				switch( $group['type'] ) {
					case self::type_external:
						foreach( $group['assets'] as $asset ) {
							html::$function( $asset['file'] );
						}
					break;
					case self::type_internal:
						if ( $production === true ) {
							$group['assets'] = array_map( function( $data ) { return $data['file']; },$group['assets'] );
							html::$function( uri::create("app/{$type}/type:combine/files:" . uri::base64_encode( implode( '<|>',$group['assets'] ) ),"extn[{$type}]|csm[yes]") );
							break;
						}
						foreach( $group['assets'] as $asset ) {
							html::$function( asset::path( $asset['file'],$type,array( 'allow_php'=>$asset['allow_php'] ) ) );
						}
					break;
					case self::type_function:
						foreach( $group['assets'] as $asset ) {
							call_user_func( $asset['func'] );
						}
					break;
				}
			}
		}
	}

	public static function get_data( $path ) {
		ob_start();
		include $path;
		$data = ob_get_contents();
		ob_end_clean();
		return $data;
	}

	public function handle_css() {
		$production = config::get('production');
		try {
			if ( !uri::validate_csm() ) {
				throw new app_exception('Checksum failed');
			}
			switch( uri::segment('type') ) {
				case 'single':
					if ( !uri::is_set('file') ) {
						throw new app_exception('No file segment found in url');
					}
					$files = array( uri::base64_decode( uri::segment('file') ) );
				break;
				case 'combine':
					if ( !uri::is_set('files') ) {
						throw new app_exception('No files segment found in url');
					}
					$files = $mtimes = array_unique( array_filter( explode( '<|>',uri::base64_decode( uri::segment('files') ) ) ) );
				break;
				default:
					throw new app_exception('Invalid type');
				break;
			}
			$min_time = 0;
			foreach( $files as &$file ) {
				if ( ( $file = path::get( $file,'css' ) ) === false ) {
					throw new app_exception('CSS file not found');
				}
				$mtime = filemtime( $file );
				if ( $mtime > $min_time ) {
					$min_time = $mtime;
				}
			}
			if ( $production === true && config::get('asset.css.caching.enabled') === true ) {
				$cache = new cache('storage-cache-css');
				$cache->id( implode( '|',$files ) )->serialize(false)->min_time( $min_time );
				$data = $cache->fetch( config::get('asset.css.caching.days'),cache::day,function() use( $files ) {
					$data = '';
					foreach( $files as $path ) {
						$data .= css::handle_images( asset::get_data( $path ),dirname( $path ) );
					}
					if ( config::get('asset.css.minify.enabled') === true ) {
						$data = css::minify( $data );
					}
					return $data;
				} );
			}
			else {
				$data = '';
				foreach( $files as $path ) {
					$data .= css::handle_images( self::get_data( $path ),dirname( $path ) );
				}
			}
			http::content_type('text/css',config::get('asset.css.charset'));
			if ( $production === true ) {
				http::cache( time::now(),time::future( config::get('asset.css.caching.days'),time::day ) );
			}
			output::set_data( $data );
		}
		catch( app_exception $e ) {
			if ( $production === false ) {
				throw $e;
			}
			http::status_code(404,'Not Found');
		}
	}

	public function handle_js() {
		$production = config::get('production');
		try {
			if ( !uri::validate_csm() ) {
				throw new app_exception('Checksum failed');
			}
			switch( uri::segment('type') ) {
				case 'single':
					if ( !uri::is_set('file') ) {
						throw new app_exception('No file segment found in url');
					}
					$files = array( uri::base64_decode( uri::segment('file') ) );
				break;
				case 'combine':
					if ( !uri::is_set('files') ) {
						throw new app_exception('No files segment found in url');
					}
					$files = $mtimes = array_unique( array_filter( explode( '<|>',uri::base64_decode( uri::segment('files') ) ) ) );
				break;
				default:
					throw new app_exception('Invalid type');
				break;
			}
			$min_time = 0;
			foreach( $files as &$file ) {
				if ( ( $file = path::get( $file,'js' ) ) === false ) {
					throw new app_exception('JS file not found');
				}
				$mtime = filemtime( $file );
				if ( $mtime > $min_time ) {
					$min_time = $mtime;
				}
			}
			if ( $production === true && config::get('asset.js.caching.enabled') === true ) {
				$cache = new cache('storage-cache-javascript');
				$cache->id( implode( '|',$files ) )->serialize(false)->min_time( $min_time );
				$data = $cache->fetch( config::get('asset.js.caching.days'),cache::day,function() use( $files ) {
					$data = '';
					foreach( $files as $path ) {
						$data .= asset::get_data( $path );
					}
					return $data;
				} );
			}
			else {
				$data = '';
				foreach( $files as $path ) {
					$data .= self::get_data( $path );
				}
			}
			http::content_type('text/javascript',config::get('asset.js.charset'));
			if ( $production === true ) {
				http::cache( time::now(),time::future( config::get('asset.js.caching.days'),time::day ) );
			}
			output::set_data( $data );
		}
		catch( app_exception $e ) {
			if ( $production === false ) {
				throw $e;
			}
			http::status_code(404,'Not Found');
		}
	}

	public function handle_image() {
		$production = config::get('production');
		try {
			if ( !package::loaded('image') ) {
				throw new app_exception('Package \'image\' is required to manipulate images');
			}
			if ( !uri::validate_csm() ) {
				throw new app_exception('Checksum failed');
			}
			$file = uri::base64_decode( uri::get('file') );
			if ( ( $path = path::get( $file,'image' ) ) === false ) {
				throw new app_exception('Image not found');
			}
			$extn = file::get_extension( $file );
			if ( ( $mime_type = http::mime_type( $extn ) ) === false ) {
				throw new app_exception('Unable to find proper mime type');
			}
			if ( uri::is_set('resize') || uri::is_set('crop') ) {
				if ( $production === true && config::get('image.caching.enabled') === true ) {
					$cache = new cache('storage-cache-image');
					$id = $group = $file;
					foreach( array('resize','crop') as $action ) {
						if ( uri::is_set( $action ) ) {
							$id .= ':' . uri::segment( $action );
						}
					}
					$cache->id( $id )->group( $group )->serialize(false)->min_time( filemtime( $path ) );
					if ( !$cache->expired( config::get('image.caching.days'),cache::day ) ) {
						$output_path = $cache->get_path();
					}
				}
				if ( !isset( $output_path ) ) {
					$image = library::image();
					$image->load_file( $path );
					$i = 0;
					if ( uri::is_set('resize') ) {
						$parts = explode( '-',uri::segment('resize') );
						if ( count( $parts ) == 4 ) {
							list( $width,$height,$prop,$box ) = $parts;
							$image->resize( (int) $width,(int) $height,( $prop === 'true' ? true : false ),( $box === 'true' ? true : false ) );
							$i++;
						}
					}
					if ( uri::is_set('crop') ) {
						$parts = explode( '-',uri::segment('crop') );
						if ( count( $parts ) == 4 ) {
							list( $from_x,$from_y,$to_x,$to_y ) = $parts;
							$image->crop( (int) $from_x,(int) $from_y,(int) $to_x,(int) $to_y );
							$i++;
						}
					}
					if ( $i > 0 ) {
						if ( isset( $cache ) ) {
							$image->save_image( ( $output_path = $cache->get_path() ),file::get_extension( $file ) );
						}
						else {
							$output_data = $image->data();
						}
					}
				}
			}
			else {
				$output_path = $path;
			}
			image::output_headers( $mime_type );
			if ( isset( $output_path ) ) {
				output::set_data( $output_path,output::file );
			}
			elseif ( isset( $output_data ) ) {
				output::set_data( $output_data );
			}
			else {
				throw new app_exception('An error has occurred while getting image data');
			}
		}
		catch( app_exception $e ) {
			if ( $production === false ) {
				throw $e;
			}
			http::status_code(404,'Not Found');
		}
	}

	public static function path( $file,$type=null,$config=array() ) {
		$extn = file::get_extension( $file );
		if ( is_null( $type ) ) {
			$type = $extn;
			switch( $type ) {
				case 'jpeg':
				case 'jpg':
				case 'png':
				case 'gif':
					$type = 'image';
				break;
			}
		}
		$path_asset = path::get('asset');
		$info = path::get( $file,$type,array(
			'return' => path::full_info
		) );
		if ( $info === false ) {
			throw new app_exception( 'Unable to find path for %s',$file );
		}
		if ( ( !isset( $config['allow_php'] ) || $config['allow_php'] === false ) && ( strpos( path::get( $info['key'] ),$path_asset ) === 0 || $info['key'] == "asset-{$type}" ) ) {
			$path = str_replace( path::get('public'),'',$info['full_path'] );
			return uri::base() . $path;
		}
		$file = uri::base64_encode( $file );
		return uri::create("app/{$type}/type:single/file:{$file}","extn[{$extn}]|csm[yes]");
	}

	public static function image( $file,$extra=array(),$config=array() ) {
		$e = 0;
		if ( isset( $extra['resize'] ) ) {
			if ( !isset( $extra['resize']['width'] ) && !isset( $extra['resize']['height'] ) ) {
				throw new app_exception('Width and/or Height required, neither given');
			}
			if ( !isset( $extra['resize']['width'] ) ) {
				$extra['resize']['width'] = 'auto';
			}
			if ( !isset( $extra['resize']['height'] ) ) {
				$extra['resize']['height'] = 'auto';
			}
			if ( !isset( $extra['resize']['prop'] ) ) {
				$extra['resize']['prop'] = false;
			}
			if ( !isset( $extra['resize']['box'] ) ) {
				$extra['resize']['box'] = false;
			}
			$config['named_segments']['resize'] = "{$extra['resize']['width']}-{$extra['resize']['height']}-" . ( $extra['resize']['prop'] == true ? 'true' : 'false' ) . '-' . ( $extra['resize']['box'] == true ? 'true' : 'false' );	
			$e++;
		}
		if ( isset( $extra['crop'] ) ) {
			$config['named_segments']['crop'] = "{$extra['crop'][0]}-{$extra['crop'][1]}-{$extra['crop'][2]}-{$extra['crop'][3]}";
			$e++;
		}
		$info = path::get( $file,'image',array(
			'return' => path::full_info
		) );
		if ( $info['key'] == 'asset-image' && $e === 0 ) {
			$base = trim( str_replace( path::get('public'),'',path::get('asset-image') ),'/' );
			return uri::base() . "{$base}/{$file}";
		}
		$config['named_segments']['file'] = uri::base64_encode( $file );
		$config['csm'] = true;
		$config['extn'] = file::get_extension( $file );
		return uri::create( 'app/image',$config );
	}

	public static function base_uri() {
		if ( is_null( self::$base_uri ) ) {
			self::$base_uri = uri::base() . trim( str_replace( path::get('public'),'',path::get('asset') ),'/' ) . '/';
		}
		return self::$base_uri;
	}

	public static function clear_cache() {
		$cache = new cache('storage-cache-css');
		$cache->clear_all();
		$cache = new cache('storage-cache-javascript');
		$cache->clear_all();
		$cache = new cache('storage-cache-image');
		$cache->clear_all();
	}

}

?>