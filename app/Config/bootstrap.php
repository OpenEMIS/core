<?php
error_reporting(0);

/**
 * This file is loaded automatically by the app/webroot/index.php file after core.php
 *
 * This file should load/create any application wide configuration settings, such as 
 * Caching, Logging, loading additional configuration files.
 *
 * You should also use this file to include any files that provide global functions/constants
 * that your application uses.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Config
 * @since         CakePHP(tm) v 0.10.8.2117
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

// Enable the Dispatcher filters for plugin assets, and
// CacheHelper.

Configure::write('Dispatcher.filters', array(
    'AssetDispatcher',
    'CacheDispatcher'
));

/**
 * Cache Engine Configuration
 * Default settings provided below
 *
 * File storage engine.
 *
 * 	 Cache::config('default', array(
 *		'engine' => 'File', //[required]
 *		'duration'=> 3600, //[optional]
 *		'probability'=> 100, //[optional]
 * 		'path' => CACHE, //[optional] use system tmp directory - remember to use absolute path
 * 		'prefix' => 'cake_', //[optional]  prefix every cache file with this string
 * 		'lock' => false, //[optional]  use file locking
 * 		'serialize' => true, // [optional]
 * 		'mask' => 0666, // [optional] permission mask to use when creating cache files
 *	));
 *
 * APC (http://pecl.php.net/package/APC)
 *
 * 	 Cache::config('default', array(
 *		'engine' => 'Apc', //[required]
 *		'duration'=> 3600, //[optional]
 *		'probability'=> 100, //[optional]
 * 		'prefix' => Inflector::slug(APP_DIR) . '_', //[optional]  prefix every cache file with this string
 *	));
 *
 * Xcache (http://xcache.lighttpd.net/)
 *
 * 	 Cache::config('default', array(
 *		'engine' => 'Xcache', //[required]
 *		'duration'=> 3600, //[optional]
 *		'probability'=> 100, //[optional]
 *		'prefix' => Inflector::slug(APP_DIR) . '_', //[optional] prefix every cache file with this string
 *		'user' => 'user', //user from xcache.admin.user settings
 *		'password' => 'password', //plaintext password (xcache.admin.pass)
 *	));
 *
 * Memcache (http://memcached.org/)
 *
 * 	 Cache::config('default', array(
 *		'engine' => 'Memcache', //[required]
 *		'duration'=> 3600, //[optional]
 *		'probability'=> 100, //[optional]
 * 		'prefix' => Inflector::slug(APP_DIR) . '_', //[optional]  prefix every cache file with this string
 * 		'servers' => array(
 * 			'127.0.0.1:11211' // localhost, default port 11211
 * 		), //[optional]
 * 		'persistent' => true, // [optional] set this to false for non-persistent connections
 * 		'compress' => false, // [optional] compress data in Memcache (slower, but uses less memory)
 *	));
 *
 *  Wincache (http://php.net/wincache)
 *
 * 	 Cache::config('default', array(
 *		'engine' => 'Wincache', //[required]
 *		'duration'=> 3600, //[optional]
 *		'probability'=> 100, //[optional]
 *		'prefix' => Inflector::slug(APP_DIR) . '_', //[optional]  prefix every cache file with this string
 *	));
 */
Cache::config('default', array('engine' => 'File'));

/**
 * The settings below can be used to set additional paths to models, views and controllers.
 *
 * App::build(array(
 *     'Model'                     => array('/path/to/models', '/next/path/to/models'),
 *     'Model/Behavior'            => array('/path/to/behaviors', '/next/path/to/behaviors'),
 *     'Model/Datasource'          => array('/path/to/datasources', '/next/path/to/datasources'),
 *     'Model/Datasource/Database' => array('/path/to/databases', '/next/path/to/database'),
 *     'Model/Datasource/Session'  => array('/path/to/sessions', '/next/path/to/sessions'),
 *     'Controller'                => array('/path/to/controllers', '/next/path/to/controllers'),
 *     'Controller/Component'      => array('/path/to/components', '/next/path/to/components'),
 *     'Controller/Component/Auth' => array('/path/to/auths', '/next/path/to/auths'),
 *     'Controller/Component/Acl'  => array('/path/to/acls', '/next/path/to/acls'),
 *     'View'                      => array('/path/to/views', '/next/path/to/views'),
 *     'View/Helper'               => array('/path/to/helpers', '/next/path/to/helpers'),
 *     'Console'                   => array('/path/to/consoles', '/next/path/to/consoles'),
 *     'Console/Command'           => array('/path/to/commands', '/next/path/to/commands'),
 *     'Console/Command/Task'      => array('/path/to/tasks', '/next/path/to/tasks'),
 *     'Lib'                       => array('/path/to/libs', '/next/path/to/libs'),
 *     'Locale'                    => array('/path/to/locales', '/next/path/to/locales'),
 *     'Vendor'                    => array('/path/to/vendors', '/next/path/to/vendors'),
 *     'Plugin'                    => array('/path/to/plugins', '/next/path/to/plugins'),
 * ));
 *
 * 
App::build(array(
      'Plugin' => array(ROOT . '/Plugin',ROOT . '/Plugin/Setup'),
      'Controller' => array(ROOT . '/Controller',ROOT . '/Controller/Setup'),
      'Model' => array(ROOT . '/Model',ROOT . '/Model/Setup'),
      'Model/Behavior' => array(ROOT . '/Model/Behaviour',ROOT . '/Model/Behaviour/Setup'),
      'View' => array(ROOT . '/View',ROOT . '/View/Setup'),
      'View/Helper' => array(ROOT . '/View/Helper',ROOT . '/View/Helper/Setup')
  )); 
 */

/**
 * Custom Inflector rules, can be set to correctly pluralize or singularize table, model, controller names or whatever other
 * string is passed to the inflection functions
 *
 * Inflector::rules('singular', array('rules' => array(), 'irregular' => array(), 'uninflected' => array()));
 * Inflector::rules('plural', array('rules' => array(), 'irregular' => array(), 'uninflected' => array()));
 *
 */

/**
 * Plugins need to be loaded manually, you can either load them one by one or all of them in a single call
 * Uncomment one of the lines below, as you need. make sure you read the documentation on CakePlugin to use more
 * advanced ways of loading plugins
 *
 * CakePlugin::loadAll(); // Loads all plugins at once
 * CakePlugin::load('DebugKit'); //Loads a single plugin named DebugKit
 *
 */
//CakePlugin::load(array('AutoAppBuild' => array('bootstrap' => true)));

CakePlugin::load(array('DataProcessing' => array('routes' => true, 'bootstrap'=>array('olap'))));
CakePlugin::load(array('Students' => array('routes' => true)));
CakePlugin::load(array('Staff' => array('routes' => true)));
CakePlugin::load(array('Reports' => array('routes' => true)));
CakePlugin::load(array('Database' => array('routes' => true)));
CakePlugin::load(array('Survey' => array('routes' => true)));
CakePlugin::load(array('Sms' => array('routes' => true)));
CakePlugin::load(array('Quality' => array('routes' => true)));
CakePlugin::load(array('Training' => array('routes' => true)));
CakePlugin::load(array('OlapCube' => array('routes' => true)));
CakePlugin::load(array('Dashboards' => array('routes' => true)));
CakePlugin::load(array('Translations' => array('routes' => true)));
CakePlugin::load(array('Visualizer' => array('routes' => true)));
CakePlugin::load(array('FusionCharts' => array('routes' => true)));
CakePlugin::load(array('HighCharts' => array('routes' => true)));
CakePlugin::load('DevInfo6');
CakePlugin::load(array('Datawarehouse' => array('routes' => true)));
CakePlugin::load(array('Alerts' => array('routes' => true)));

// Custom Reports
Configure::write('ReportManager.displayForeignKeys', 0);
Configure::write('ReportManager.globalFieldIgnoreList', array(
    'id',
    'created',
    'modified'
));
Configure::write('ReportManager.modelIgnoreList',array(
    'AppModel'
));
Configure::write('ReportManager.modelFieldIgnoreList',array(
    'MyModel' => array(
        'field1'=>'field1',
        'field2'=>'field2',
        'field3'=>'field3'
    )
));
Configure::write('ReportManager.reportPath', 'tmp'.DS.'reports'.DS);
Configure::write('ReportManager.labelFieldList',array(
    '*' => array(
        'field1'=>'my field 1 label description',
        'field2'=>'my field 2 label description',
        'field3'=>'my field 3 label description'
    ),
    'MyModel' => array(
        'field1' => 'my MyModel field 1 label description'
    )
));
// End

if (!function_exists('T')) {
   function T($string, $escape = FALSE) {
      return ($escape == FALSE)?__($string):addslashes(__($string));
   }
}
