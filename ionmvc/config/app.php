<?php

namespace ionmvc;

const CLASS_TYPE_CONTROLLER = 'ionmvc.controller';
const CLASS_TYPE_COMMAND    = 'ionmvc.command';
const CLASS_TYPE_DEFAULT    = 'ionmvc.class';
const CLASS_TYPE_DRIVER     = 'ionmvc.driver';
const CLASS_TYPE_EXCEPTION  = 'ionmvc.exception';
const CLASS_TYPE_HELPER     = 'ionmvc.helper';
const CLASS_TYPE_LIBRARY    = 'ionmvc.library';
const CLASS_TYPE_MODEL      = 'ionmvc.model';
const CLASS_TYPE_TRAIT      = 'ionmvc.trait';

const ENV_PRODUCTION  = 'ionmvc.env.production';
const ENV_DEVELOPMENT = 'ionmvc.env.development';

$config = [
	'filesystem' => [
		'modes' => [
			'default' => [
				'file'      => 0644,
				'directory' => 0755
			],
			'writeable' => [
				'file'      => 0777,
				'directory' => 0777
			]
		]
	],
	'framework' => [
		'env'             => ENV_DEVELOPMENT,
		'error_backtrace' => true,
		'redirect'        => true,
		'ssl'             => false,
		'paths' => [
			'root'                 => rtrim( PATH_ROOT,'/' ),
			'config'               => '{root}/config',
			'public'               => '{root}/public',
			'ionmvc'               => '{root}/ionmvc',
			'ionmvc-class'         => '{ionmvc}/classes',
			'ionmvc-command'       => '{ionmvc}/commands',
			'ionmvc-exception'     => '{ionmvc}/exceptions',
			'ionmvc-template'      => '{ionmvc}/templates',
			'ionmvc-trait'         => '{ionmvc}/traits',
			'ionmvc-view'          => '{ionmvc}/views',
			'app'                  => '{root}/application',
			'app-config'           => '{app}/config',
			'app-controller'       => '{app}/controllers',
			'app-helper'           => '{app}/helpers',
			'app-library'          => '{app}/libraries',
			'app-model'            => '{app}/models',
			'app-package'          => '{app}/packages',
			'app-third-party'      => '{app}/third-party',
			'app-view'             => '{app}/views',
			'storage'              => '{root}/storage',
			'storage-app'          => '{storage}/app',
			'storage-cache'        => '{storage}/cache',
			'storage-cache-output' => '{storage-cache}/output',
			'storage-log'          => '{storage}/logs'
		],
		'path_groups' => [
			'template' => ['ionmvc-template'],
			'config'   => ['app-config','config'],
			'view'     => ['app-view']
		],
		'class_types' => [
			CLASS_TYPE_CONTROLLER => [
				'file_prefix' => 'controller'
			],
			CLASS_TYPE_COMMAND => [
				'file_prefix' => 'command'
			],
			CLASS_TYPE_DEFAULT => [
				'file_prefix' => 'class'
			],
			CLASS_TYPE_DRIVER => [
				'file_prefix' => 'driver'
			],
			CLASS_TYPE_EXCEPTION => [
				'file_prefix' => 'exception'
			],
			CLASS_TYPE_HELPER => [
				'file_prefix' => 'helper'
			],
			CLASS_TYPE_LIBRARY => [
				'file_prefix' => 'library'
			],
			CLASS_TYPE_MODEL => [
				'file_prefix' => 'model'
			],
			CLASS_TYPE_TRAIT => [
				'file_prefix' => 'trait'
			]
		],
		'namespaces' => [
			'ionmvc.classes' => [
				'type' => CLASS_TYPE_DEFAULT,
				'path' => 'ionmvc-class'
			],
			'ionmvc.exceptions' => [
				'type' => CLASS_TYPE_EXCEPTION,
				'path' => 'ionmvc-exception'
			],
			'ionmvc.commands' => [
				'type' => CLASS_TYPE_COMMAND,
				'path' => 'ionmvc-command'
			],
			'ionmvc.controllers' => [
				'type' => CLASS_TYPE_CONTROLLER,
				'path' => 'app-controller'
			],
			'ionmvc.libraries' => [
				'type' => CLASS_TYPE_LIBRARY,
				'path' => 'app-library'
			],
			'ionmvc.models' => [
				'type' => CLASS_TYPE_MODEL,
				'path' => 'app-model'
			],
			'ionmvc.helpers' => [
				'type' => CLASS_TYPE_HELPER,
				'path' => 'app-helper'
			],
			'ionmvc.traits' => [
				'type' => CLASS_TYPE_TRAIT,
				'path' => 'ionmvc-trait'
			]
		],
		'controller' => [
			'default'  => 'home',
			'reserved' => ['base']
		],
		'action' => [
			'default'  => 'index',
			'reserved' => []
		],
	],
	'package' => [
		'enabled'  => true
	],
	'output' => [
		'compression' => [
			'enabled' => true
		],
		'caching' => [
			'enabled' => true
		]
	],
	'url' => [
		'nonsecure'       => '',
		'secure'          => '',
		'extension'       => false,
		'append_index'    => true,
		'permitted_chars' => 'a-zA-Z 0-9~\.:_\-'
	],
	'log' => [
		'app' => 'app.log',
		'php' => 'php.log'	
	],
	'security' => [
		'salt' => ''
	]
];

?>