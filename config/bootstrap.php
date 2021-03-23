<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         0.10.8
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

// You can remove this if you are confident that your PHP version is sufficient.
if (version_compare(PHP_VERSION, '5.6.0') < 0) {
    trigger_error('Your PHP version must be equal or higher than 5.6.0 to use CakePHP.', E_USER_ERROR);
}

/*
 *  You can remove this if you are confident you have intl installed.
 */
if (!extension_loaded('intl')) {
    trigger_error('You must enable the intl extension to use CakePHP.', E_USER_ERROR);
}

/*
 * You can remove this if you are confident you have mbstring installed.
 */
if (!extension_loaded('mbstring')) {
    trigger_error('You must enable the mbstring extension to use CakePHP.', E_USER_ERROR);
}

/*
 * Configure paths required to find CakePHP + general filepath
 * constants
 */
require __DIR__ . '/paths.php';

/*
 * Bootstrap CakePHP.
 *
 * Does the various bits of setup that CakePHP needs to do.
 * This includes:
 *
 * - Registering the CakePHP autoloader.
 * - Setting the default application paths.
 */
require CORE_PATH . 'config' . DS . 'bootstrap.php';

use Cake\Cache\Cache;
use Cake\Console\ConsoleErrorHandler;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Core\Plugin;
use Cake\Database\Type;
use Cake\Datasource\ConnectionManager;
use Cake\Error\ErrorHandler;
use Cake\Log\Log;
use Cake\Mailer\Email;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Utility\Security;
use App\Error\AppError;
use Cake\Core\Exception\Exception;

/**
 * Read configuration file and inject configuration into various
 * CakePHP classes.
 *
 * By default there is only one configuration file. It is often a good
 * idea to create multiple configuration files, and separate the configuration
 * that changes from configuration that does not. This makes deployment simpler.
 */
try {
    Configure::config('default', new PhpConfig());
    Configure::load('app', 'default', false);
} catch (\Exception $e) {
    exit($e->getMessage() . "\n");
}

try {
    Configure::load('datasource', 'default');
    Configure::load('app_extra', 'default');
    ConnectionManager::config(Configure::consume('Datasources'));
    // if (!Configure::read('Application.private.key') || !Configure::read('Application.public.key')) {
    //     throw new Exception('Could not load application key, please contact administrator to have the key set up for your application.');
    // }
} catch (\Exception $e) {
}



// Load an environment local configuration file.
// You can use a file like app_local.php to provide local overrides to your
// shared configuration.
//Configure::load('app_local', 'default');

// When debug = false the metadata cache should last
// for a very very long time, as we don't want
// to refresh the cache while users are doing requests.
if (Configure::read('debug')) {
    // For unit testing
    try {
        Configure::load('test_datasource', 'default');
    } catch (\Exception $e) {
        // do nothing if test_datasource.php is not found
    }
} else {
    Configure::write('Cache._cake_model_.duration', '+1 year');
    Configure::write('Cache._cake_core_.duration', '+1 year');
}

/**
 * Set server timezone to UTC. You can change it to another timezone of your
 * choice but using UTC makes time calculations / conversions easier.
 */
// Default time zone is set in datasource.php, as timezone is set to the client's timezone
// date_default_timezone_set('UTC');

/**
 * Configure the mbstring extension to use the correct encoding.
 */
mb_internal_encoding(Configure::read('App.encoding'));

/**
 * Set the default locale. This controls how dates, number and currency is
 * formatted and sets the default language to use for translations.
 */
ini_set('intl.default_locale', Configure::read('App.defaultLocale'));

/**
 * Register application error and exception handlers.
 */

$defaultErrorConfig = [
        'errorLevel' => E_ALL,
        'exceptionRenderer' => 'Cake\Error\ExceptionRenderer',
        'skipLog' => [],
        'log' => true,
        'trace' => true,
    ];
$isCli = PHP_SAPI === 'cli';
if ($isCli) {
    (new ConsoleErrorHandler($defaultErrorConfig))->register();
} else {
    if (Configure::read('debug')) {
        (new ErrorHandler($defaultErrorConfig))->register();
    } else {
        $defaultErrorConfig['exceptionRenderer'] = 'App\Error\AppExceptionRenderer';
        $errorHandler = new AppError(Configure::read('Error'));
        $errorHandler->register();
    }
}

// Include the CLI bootstrap overrides.
if ($isCli) {
    require __DIR__ . '/bootstrap_cli.php';
}

/**
 * Set the full base URL.
 * This URL is used as the base of all absolute links.
 *
 * If you define fullBaseUrl in your config file you can remove this.
 */
if (!Configure::read('App.fullBaseUrl')) {
    $s = null;
    if (env('HTTPS')) {
        $s = 's';
    }

    $httpHost = env('HTTP_HOST');
    if (isset($httpHost)) {
        Configure::write('App.fullBaseUrl', 'http' . $s . '://' . $httpHost);
    }
    unset($httpHost, $s);
}

Cache::config(Configure::consume('Cache'));
Email::configTransport(Configure::consume('EmailTransport'));
Email::config(Configure::consume('Email'));
Log::config(Configure::consume('Log'));
Security::salt(Configure::consume('Security.salt'));

/*
 * The default crypto extension in 3.0 is OpenSSL.
 * If you are migrating from 2.x uncomment this code to
 * use a more compatible Mcrypt based implementation
 */
//Security::engine(new \Cake\Utility\Crypto\Mcrypt());

/*
 * Setup detectors for mobile and tablet.
 */
Request::addDetector('mobile', function ($request) {
    $detector = new \Detection\MobileDetect();

    return $detector->isMobile();
});
Request::addDetector('tablet', function ($request) {
    $detector = new \Detection\MobileDetect();

    return $detector->isTablet();
});

/*
 * Enable immutable time objects in the ORM.
 *
 * You can enable default locale format parsing by adding calls
 * to `useLocaleParser()`. This enables the automatic conversion of
 * locale specific date formats. For details see
 * @link http://book.cakephp.org/3.0/en/core-libraries/internationalization-and-localization.html#parsing-localized-datetime-data
 */
// Commented out as there is issue when saving RTL date
// Type::build('time')
//     ->useLocaleParser();
// Type::build('date')
//     ->useLocaleParser();
// Type::build('datetime')
//     ->useLocaleParser();
// Type::build('timestamp')
//     ->useLocaleParser();

/**
 * Custom Inflector rules, can be set to correctly pluralize or singularize
 * table, model, controller names or whatever other string is passed to the
 * inflection functions.
 *
 * Inflector::rules('plural', ['/^(inflect)or$/i' => '\1ables']);
 * Inflector::rules('irregular', ['red' => 'redlings']);
 * Inflector::rules('uninflected', ['dontinflectme']);
 * Inflector::rules('transliteration', ['/å/' => 'aa']);
 */

// For Staff Module
 Inflector::rules('plural', ['/(S|s)taff$/i' => '\1taff']);
 Inflector::rules('plural', ['/(T|t)ransport$/i' => '\1ransport']);
 Inflector::rules('plural', ['/(T|t)raining$/i' => '\1raining']);
 Inflector::rules('plural', ['/(C|c)ounselling$/i' => '\1ounselling']);
 Inflector::rules('plural', ['/SSO$/i' => 'Sso']);

/**
 * Plugins need to be loaded manually, you can either load them one by one or all of them in a single call
 * Uncomment one of the lines below, as you need. make sure you read the documentation on Plugin to use more
 * advanced ways of loading plugins
 *
 * Plugin::loadAll(); // Loads all plugins at once
 * Plugin::load('Migrations'); //Loads a single plugin named Migrations
 *
 */

Plugin::load('Migrations');

// Custom Plugins

// Essential Plugins
Plugin::load('OpenEmis', ['autoload' => true]);
Plugin::load('ControllerAction', ['autoload' => true]);
Plugin::load('Angular', ['routes' => true, 'autoload' => true]);
Plugin::load('Page');

// Localizations
Plugin::load('Localization', ['routes' => true, 'autoload' => true]);

// Main Modules
Plugin::load('Area', ['routes' => true, 'autoload' => true]);
Plugin::load('Alert', ['routes' => true, 'autoload' => true]);
Plugin::load('AcademicPeriod', ['routes' => true, 'autoload' => true]);
Plugin::load('Directory', ['routes' => true, 'autoload' => true]);
Plugin::load('FieldOption', ['routes' => true, 'autoload' => true]);
Plugin::load('Institution', ['routes' => true, 'autoload' => true]);
Plugin::load('User', ['routes' => true, 'autoload' => true]);
Plugin::load('Student', ['routes' => true, 'autoload' => true]);
Plugin::load('Staff', ['routes' => true, 'autoload' => true]);
Plugin::load('Education', ['routes' => true, 'autoload' => true]);
Plugin::load('Assessment', ['routes' => true, 'autoload' => true]);
Plugin::load('Textbook', ['routes' => true, 'autoload' => true]);
Plugin::load('Security', ['routes' => true, 'autoload' => true]);
Plugin::load('Survey', ['routes' => true, 'autoload' => true]);
Plugin::load('Rest', ['routes' => true, 'autoload' => true]);
Plugin::load('Report', ['routes' => true, 'autoload' => true]);
Plugin::load('Rubric', ['routes' => true, 'autoload' => true]);
Plugin::load('Workflow', ['routes' => true, 'autoload' => true]);
Plugin::load('CustomField', ['routes' => true, 'autoload' => true]);
Plugin::load('Risk', ['routes' => true, 'autoload' => true]);
Plugin::load('InstitutionCustomField', ['routes' => true, 'autoload' => true]);
Plugin::load('StudentCustomField', ['routes' => true, 'autoload' => true]);
Plugin::load('StaffCustomField', ['routes' => true, 'autoload' => true]);
Plugin::load('Infrastructure', ['routes' => true, 'autoload' => true]);
Plugin::load('Error', ['routes' => true, 'autoload' => true]);
Plugin::load('Import', ['routes' => true, 'autoload' => true]);
Plugin::load('API', ['routes' => true, 'autoload' => true]);
Plugin::load('Log', ['routes' => true, 'autoload' => true]);
Plugin::load('Training', ['routes' => true, 'autoload' => true]);
Plugin::load('Map', ['routes' => true, 'autoload' => true]);
Plugin::load('Health', ['routes' => true, 'autoload' => true]);
Plugin::load('Cache', ['routes' => true, 'autoload' => true]);
Plugin::load('Restful');
Plugin::load('ADmad/JwtAuth');
Plugin::load('SSO');
Plugin::load('Webhook', ['routes' => true, 'autoload' => true]);
Plugin::load('System', ['routes' => true, 'autoload' => true]);
Plugin::load('InstitutionRepeater', ['routes' => true, 'autoload' => true]);
Plugin::load('Examination', ['routes' => true, 'autoload' => true]);
Plugin::load('Configuration', ['routes' => true, 'autoload' => true]);
Plugin::load('CustomExcel', ['routes' => true, 'autoload' => true]);
Plugin::load('Competency', ['routes' => true, 'autoload' => true]);
Plugin::load('ReportCard', ['routes' => true, 'autoload' => true]);
Plugin::load('Profile', ['routes' => true, 'autoload' => true]);
Plugin::load('Transport', ['routes' => true, 'autoload' => true]);
Plugin::load('Installer', ['routes' => true, 'autoload' => true]);
Plugin::load('Quality', ['autoload' => true]);
Plugin::load('Cases', ['autoload' => true]);
Plugin::load('Counselling', ['autoload' => true]);
Plugin::load('Outcome', ['routes' => true, 'autoload' => true]);
Plugin::load('Theme', ['routes' => true, 'autoload' => true]);
Plugin::load('StaffAppraisal', ['routes' => true, 'autoload' => true]);
Plugin::load('Scholarship', ['routes' => true, 'autoload' => true]);
Plugin::load('Attendance', ['routes' => true, 'autoload' => true]);
Plugin::load('Guardian', ['routes' => true, 'autoload' => true]);
Plugin::load('Email', ['routes' => true, 'autoload' => true]);
Plugin::load('SpecialNeeds', ['routes' => true, 'autoload' => true]);
Plugin::load('MoodleApi', ['routes' => true, 'autoload' => true]);
Plugin::load('Historical', ['autoload' => true]);
Plugin::load('Schedule', ['autoload' => true]);
Plugin::load('ProfileTemplate', ['routes' => true, 'autoload' => true]);
Plugin::load('Meal', ['routes' => true, 'autoload' => true]);


$pluginPath = Configure::read('plugins');
foreach ($pluginPath as $key => $path) {
    if (!file_exists($path)) {
        Plugin::unload($key);
        Configure::write('School.excludedPlugins.' . $key, Inflector::humanize(Inflector::underscore(Inflector::pluralize($key))));
    }
}

// Only try to load DebugKit in development mode
// Debug Kit should not be installed on a production system
if (Configure::read('debug')) {
    // Plugin::load('DebugKit', ['bootstrap' => true]);
}

Plugin::load('OAuth', ['routes' => true]);

Plugin::load('Archive', ['bootstrap' => false, 'routes' => true]);
