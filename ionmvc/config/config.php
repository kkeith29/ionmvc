<?php

namespace ionmvc;

const CLASS_TYPE_CONTROLLER = 'ionmvc.controller';
const CLASS_TYPE_DEFAULT    = 'ionmvc.class';
const CLASS_TYPE_DRIVER     = 'ionmvc.driver';
const CLASS_TYPE_EXCEPTION  = 'ionmvc.exception';
const CLASS_TYPE_HELPER     = 'ionmvc.helper';
const CLASS_TYPE_LIBRARY    = 'ionmvc.library';
const CLASS_TYPE_MODEL      = 'ionmvc.model';

$config = array(
	'framework' => array(
		'production' => false,
		'mvc_load' => true,
		'error_backtrace' => true,
		'redirect' => true,
		'ssl' => false,
		'paths' => array(
			'root'                         => rtrim( PATH_ROOT,'/' ),
			'public'                       => '{root}/public',
			'asset'                        => '{public}/assets',
			'asset-css'                    => '{asset}/css',
			'asset-js'                     => '{asset}/javascript',
			'asset-image'                  => '{asset}/images',
			'asset-third-party'            => '{asset}/third-party',
			'ionmvc'                       => '{root}/ionmvc',
			'ionmvc-class'                 => '{ionmvc}/classes',
			'ionmvc-exception'             => '{ionmvc}/exceptions',
			'ionmvc-view'                  => '{ionmvc}/views',
			'app'                          => '{root}/application',
			'app-config'                   => '{app}/config',
			'app-controller'               => '{app}/controllers',
			'app-helper'                   => '{app}/helpers',
			'app-library'                  => '{app}/libraries',
			'app-model'                    => '{app}/models',
			'app-package'                  => '{app}/packages',
			'app-third-party'              => '{app}/third-party',
			'app-view'                     => '{app}/views',
			'storage'                      => '{root}/storage',
			'storage-cache'                => '{storage}/cache',
			'storage-cache-css'            => '{storage-cache}/css',
			'storage-cache-output'         => '{storage-cache}/output',
			'storage-cache-image'          => '{storage-cache}/images',
			'storage-cache-javascript'     => '{storage-cache}/javascript',
			'storage-log'                  => '{storage}/logs',
			'storage-file'                 => '{storage}/files',
			'storage-file-temp'            => '{storage-file}/temp'
		),
		'path_groups' => array(
			'config' => array('app-config'),
			'css'    => array('asset-css','asset-third-party'),
			'image'  => array('asset-image','asset-third-party'),
			'js'     => array('asset-js','asset-third-party'),
			'view'   => array('app-view'),
			'file'   => array('storage-file')
		),
		'class_types' => array(
			CLASS_TYPE_CONTROLLER => array(
				'file_prefix' => 'controller'
			),
			CLASS_TYPE_DEFAULT => array(
				'file_prefix' => 'class'
			),
			CLASS_TYPE_DRIVER => array(
				'file_prefix' => 'driver'
			),
			CLASS_TYPE_EXCEPTION => array(
				'file_prefix' => 'exception'
			),
			CLASS_TYPE_HELPER => array(
				'file_prefix' => 'helper'
			),
			CLASS_TYPE_LIBRARY => array(
				'file_prefix' => 'library'
			),
			CLASS_TYPE_MODEL => array(
				'file_prefix' => 'model'
			)
		),
		'namespaces' => array(
			'ionmvc.classes' => array(
				'type' => CLASS_TYPE_DEFAULT,
				'path' => 'ionmvc-class'
			),
			'ionmvc.exceptions' => array(
				'type' => CLASS_TYPE_EXCEPTION,
				'path' => 'ionmvc-exception'
			),
			'ionmvc.controllers' => array(
				'type' => CLASS_TYPE_CONTROLLER,
				'path' => 'app-controller'
			),
			'ionmvc.libraries' => array(
				'type' => CLASS_TYPE_LIBRARY,
				'path' => 'app-library'
			),
			'ionmvc.models' => array(
				'type' => CLASS_TYPE_MODEL,
				'path' => 'app-model'
			),
			'ionmvc.helpers' => array(
				'type' => CLASS_TYPE_HELPER,
				'path' => 'app-helper'
			)
		),
		'controller' => array(
			'default'  => 'home',
			'reserved' => array('base')
		),
		'action' => array(
			'default'  => 'index',
			'reserved' => array()
		),
	),
	'cookie' => array(
		'prefix' => '',
		'salt'   => ''
	),
	'asset' => array(
		'jquery' => array(
			'path'    => 'jquery.js',
			'ui_path' => 'jquery-ui'
		),
		'css' => array(
			'charset' => 'UTF-8',
			'caching' => array(
				'enabled' => true,
				'days'    => 5
			),
			'minify' => array(
				'enabled'               => true,
				'remove_last_semicolon' => true,
				'preserve_urls'         => false
			)
		),
		'js' => array(
			'charset' => 'UTF-8',
			'caching' => array(
				'enabled' => true,
				'days'    => 5
			)
		)
	),
	'package' => array(
		'enabled'  => true
	),
	'output' => array(
		'compression' => array(
			'enabled' => true
		),
		'caching' => array(
			'enabled' => true
		)
	),
	'html' => array(
		'charset' => 'UTF-8'
	),
	'url' => array(
		'nonsecure'       => '',
		'secure'          => '',
		'extension'       => false,
		'append_index'    => false,
		'permitted_chars' => 'a-zA-Z 0-9~\.:_\-'
	),
	'log' => array(
		'app' => 'app.log',
		'php' => 'php.log'	
	),
	'security' => array(
		'salt' => ''
	)
);

?>