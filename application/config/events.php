<?php

use ionmvc\classes\app;
use ionmvc\classes\event;
use ionmvc\classes\view;

event::bind('app.error.technical_difficulties',function() {
	view::fetch('ionmvc-view:error/technical_difficulties');
});

event::bind('app.error.404',function() {
	view::fetch('ionmvc-view:error/404');
	return event::last;
});

event::bind('app.error.invalid_uri_chars',function() {
	view::fetch('ionmvc-view:general',array('title'=>'Error','message'=>'Invalid URI characters'));
	return event::last;
});

event::bind('app.general.site_offline',function() {
	view::fetch('ionmvc-view:general',array('title'=>'Site Offline','message'=>'Site is currently offline. Please check back later.'));
	return event::last;
});

event::bind('app.general.maintenance',function() {
	view::fetch('ionmvc-view:general',array('title'=>'Down for Maintenance','message'=>'Site is currently down for maintenance. Please check back later.'));
	return event::last;
});

//user event bindings here

?>