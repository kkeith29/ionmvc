<?php

namespace ionmvc;

use ionmvc\classes\event;
use ionmvc\classes\view;

event::bind('response.not_found',function() {
	view::fetch('ionmvc-view:error/404');
});

?>