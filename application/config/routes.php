<?php

use ionmvc\classes\app;
use ionmvc\classes\router;

//routes here
router::uri('app/css',function() {
	app::asset()->handle_css();
	return router::stop;
});
router::uri('app/js',function() {
	app::asset()->handle_js();
	return router::stop;
});
router::uri('app/image',function() {
	app::asset()->handle_image();
	return router::stop;
});

//user defined routes here

?>