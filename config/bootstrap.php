<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.8
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/*
 * Configure paths required to find CakePHP + general filepath constants
 */
require __DIR__ . DIRECTORY_SEPARATOR . 'paths.php';

/*
 * Compatibility function for CakePHP 5
 * The env() helper function was removed in CakePHP 5, so we provide a compatibility wrapper
 */
if (!function_exists('env')) {
    /**
     * Gets an environment variable from available sources.
     *
     * @param string $key Environment variable name.
     * @param mixed $default Default value to return if the environment variable is not set.
     * @return mixed Environment variable value or default value.
     */
    function env($key, $default = null)
    {
        if ($key === false) {
            return $default;
        }

        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false) {
            return $default;
        }

        return $value;
    }
}

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

/*
 * Load global functions including i18n functions (__(), __d(), etc.)
 * These are needed in template files which don't have namespaces.
 * In CakePHP 5, functions.php defines them in the Cake\I18n namespace,
 * but we need global versions. The functions.php file checks if they exist
 * before defining them, so we can require it directly.
 */
if (defined('CAKE') && file_exists(CAKE . 'I18n' . DS . 'functions.php')) {
    require CAKE . 'I18n' . DS . 'functions.php';
}

/*
 * Compatibility extension for CakePHP 5
 * TableRegistry exists in CakePHP 5 but is missing the get() method
 */
require __DIR__ . DS . 'table_registry_compat.php';

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Database\Type\StringType;
use Cake\Database\TypeFactory;
use Cake\Datasource\ConnectionManager;
use Cake\Error\ErrorTrap;
use Cake\Error\ExceptionTrap;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\Mailer\TransportFactory;
use Cake\Routing\Router;
use Cake\Utility\Security;

/*
 * See https://github.com/josegonzalez/php-dotenv for API details.
 *
 * Uncomment block of code below if you want to use `.env` file during development.
 * You should copy `config/.env.example` to `config/.env` and set/modify the
 * variables as required.
 *
 * The purpose of the .env file is to emulate the presence of the environment
 * variables like they would be present in production.
 *
 * If you use .env files, be careful to not commit them to source control to avoid
 * security risks. See https://github.com/josegonzalez/php-dotenv#general-security-information
 * for more information for recommended practices.
*/
// if (!env('APP_NAME') && file_exists(CONFIG . '.env')) {
//     $dotenv = new \josegonzalez\Dotenv\Loader([CONFIG . '.env']);
//     $dotenv->parse()
//         ->putenv()
//         ->toEnv()
//         ->toServer();
// }

/*
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

/*
 * Load an environment local configuration file to provide overrides to your configuration.
 * Notice: For security reasons app_local.php **should not** be included in your git repo.
 */
if (file_exists(CONFIG . 'app_local.php')) {
    Configure::load('app_local', 'default');
}

/*
 * When debug = true the metadata cache should only last
 * for a short time.
 */
if (Configure::read('debug')) {
    Configure::write('Cache._cake_model_.duration', '+2 minutes');
    Configure::write('Cache._cake_core_.duration', '+2 minutes');
    // disable router cache during development
    Configure::write('Cache._cake_routes_.duration', '+2 seconds');
}

//POCOR-9565[START]
/*
 * Internal timezone: UTC. Display timezone (e.g. Asia/Singapore) is loaded from config_items
 * via App\Utility\ApplicationTimezone (cached) into Configure::read('App.displayTimezone').
 */
date_default_timezone_set(Configure::read('App.defaultTimezone') ?: 'UTC');
//POCOR-9565[END]

/*
 * Configure the mbstring extension to use the correct encoding.
 */
mb_internal_encoding(Configure::read('App.encoding'));

/*
 * Set the default locale. This controls how dates, number and currency is
 * formatted and sets the default language to use for translations.
 */
ini_set('intl.default_locale', Configure::read('App.defaultLocale'));

/*
 * Register application error and exception handlers.
 */
(new ErrorTrap(Configure::read('Error')))->register();
(new ExceptionTrap(Configure::read('Error')))->register();

/*
 * Include the CLI bootstrap overrides.
 */
if (PHP_SAPI === 'cli') {
    require CONFIG . 'bootstrap_cli.php';
}

/*
 * Set the full base URL.
 * This URL is used as the base of all absolute links.
 */

// Read the configured fullBaseUrl (can be set in app_local.php)
$fullBaseUrl = Configure::read('App.fullBaseUrl');

if (!$fullBaseUrl) {
    // POCOR-9127 start – Determine protocol and build URL dynamically
    $trustProxy = env('TRUST_PROXY', false);
    $https = env('HTTPS', false);
    $httpXForwardedProto = env('HTTP_X_FORWARDED_PROTO', false);

    $s = '';
    if ($https) {
        $s = 's';
    } elseif ($trustProxy && $httpXForwardedProto) {
        $proto = strtolower(trim(explode(',', $httpXForwardedProto)[0]));
        if ($proto === 'https') {
            $s = 's';
        }
    }

    $host = env('HTTP_HOST', 'localhost');
    $fullBaseUrl = 'http' . $s . '://' . $host;
    Configure::write('App.fullBaseUrl', $fullBaseUrl);
    // POCOR-9127 end

    unset($host, $s); // clean up
}

if ($fullBaseUrl) {
    Router::fullBaseUrl($fullBaseUrl);
}

unset($fullBaseUrl);

Cache::setConfig(Configure::consume('Cache'));
ConnectionManager::setConfig(Configure::consume('Datasources'));

//POCOR-9719: unify PHP, CakePHP App.defaultTimezone, and every MySQL
//connection's session timezone on config_items.time_zone. Shared source with
//Laravel (api/app/Providers/AppServiceProvider::applySystemTimezone). Env
//vars APP_TIMEZONE / APP_DEFAULT_TIMEZONE removed — cron and queue workers
//don't inherit FPM env, the root cause of the Tonga +13h alert drift.
\App\Utility\ApplicationTimezone::applyToSystem();

TransportFactory::setConfig(Configure::consume('EmailTransport'));
Mailer::setConfig(Configure::consume('Email'));
Log::setConfig(Configure::consume('Log'));
Security::setSalt(Configure::consume('Security.salt'));

/*
 * Setup detectors for mobile and tablet.
 * If you don't use these checks you can safely remove this code
 * and the mobiledetect package from composer.json.
 */
ServerRequest::addDetector('mobile', function ($request) {
    $detector = new \Detection\MobileDetect();

    return $detector->isMobile();
});
ServerRequest::addDetector('tablet', function ($request) {
    $detector = new \Detection\MobileDetect();

    return $detector->isTablet();
});

/*
 * You can enable default locale format parsing by adding calls
 * to `useLocaleParser()`. This enables the automatic conversion of
 * locale specific date formats. For details see
 * @link https://book.cakephp.org/4/en/core-libraries/internationalization-and-localization.html#parsing-localized-datetime-data
 */
// \Cake\Database\TypeFactory::build('time')
//    ->useLocaleParser();
// \Cake\Database\TypeFactory::build('date')
//    ->useLocaleParser();
// \Cake\Database\TypeFactory::build('datetime')
//    ->useLocaleParser();
// \Cake\Database\TypeFactory::build('timestamp')
//    ->useLocaleParser();
// \Cake\Database\TypeFactory::build('datetimefractional')
//    ->useLocaleParser();
// \Cake\Database\TypeFactory::build('timestampfractional')
//    ->useLocaleParser();
// \Cake\Database\TypeFactory::build('datetimetimezone')
//    ->useLocaleParser();
// \Cake\Database\TypeFactory::build('timestamptimezone')
//    ->useLocaleParser();

// There is no time-specific type in Cake
TypeFactory::map('time', StringType::class);

/*
 * Custom Inflector rules, can be set to correctly pluralize or singularize
 * table, model, controller names or whatever other string is passed to the
 * inflection functions.
 */
//Inflector::rules('plural', ['/^(inflect)or$/i' => '\1ables']);
//Inflector::rules('irregular', ['red' => 'redlings']);
//Inflector::rules('uninflected', ['dontinflectme']);

//POCOR-9269 start
$projectRoot = dirname(__DIR__);
$logPath = $projectRoot . DIRECTORY_SEPARATOR . 'logs';

if (!file_exists($logPath)) {
    mkdir($logPath, 0777, true);
}

if (!is_writable($logPath)) {
    chmod($logPath, 0777);
} //POCOR-9269 end
