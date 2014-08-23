<?php

namespace ionmvc\classes;

class redirect {

	private static function _do( $loc,$full=false ) {
		$loc = ( $full !== true ? uri::create( $loc ) : $loc );
		app::terminate();
		if ( config::get('framework.redirect') === true ) {
			header("Location: {$loc}");
		}
		die("Unable redirect to location: <a href=\"{$loc}\">{$loc}</a>");
	}

	public static function to( $loc ) {
		self::_do( $loc,false );
	}

	public static function to_url( $loc ) {
		self::_do( $loc,true );
	}

	public static function current_page() {
		self::_do( uri::current(),true );
	}

}

?>