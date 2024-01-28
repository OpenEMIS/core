<?php
/*
 * Local configuration file to provide any overrides to your app.php configuration.
 * Copy and save this file as app_local.php and make changes as required.
 * Note: It is not recommended to commit files with credentials such as app_local.php
 * into source code version control.
 */

return [
    'App' => [
        'fullBaseUrl' => 'http://localhost:8182',
        'viewPaths' => ROOT . '/src/Template/Layout/',
        'defaultViewPaths' => ROOT . '/src/Template/Layout/',
    ],
    'Error' => [
        'errorLevel' => E_ERROR,
//        'exceptionRenderer' => 'App\Error\AppExceptionRenderer',
        'skipLog' => ['MissingControllerException'],
        'log' => true,
        'trace' => true,
    ],
    'Log' => [
        'debug' => [
            'className' => 'Cake\Log\Engine\FileLog',
            'path' => LOGS,
            'file' => 'hin-debug',
            'levels' => ['notice', 'info', 'debug'],
            'url' => env('LOG_DEBUG_URL', null),
        ],
        'error' => [
            'className' => 'Cake\Log\Engine\FileLog',
            'path' => LOGS,
            'file' => 'hin-error',
            'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
            'url' => env('LOG_ERROR_URL', null),
        ],
        ],
    /*
     * Debug Level:
     *
     * Production Mode:
     * false: No error messages, errors, or warnings shown.
     *
     * Development Mode:
     * true: Errors and warnings shown.
     */
    'debug' => filter_var(env('DEBUG',false), FILTER_VALIDATE_BOOLEAN),

    /*
     * Security and encryption configuration
     *
     * - salt - A random string used in security hashing methods.
     *   The salt value is also used as the encryption key.
     *   You should treat it as extremely sensitive data.
     */
    'Security' => [
        'salt' => env('SECURITY_SALT', '444db3ff8e6247fc30dd0d21414066d956d3f6340ff059927b40e4dddc1b880c'),
    ],

    /*
     * Connection information used by the ORM to connect
     * to your application's datastores.
     *
     * See app.php for more configuration options.
     */
    'Datasources' => [
        'default' => [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Mysql',
            'persistent' => false,

            'host' => 'poe-database',
            'port' => '3306',
            'username' => 'admin',
            'password' => 'demo',
            'database' => 'openemis_core',

            'encoding' => 'utf8mb4',
            'timezone' => 'UTC',
            'cacheMetadata' => true,
            'quoteIdentifiers' => true,
            //'init' => ['SET GLOBAL innodb_stats_on_metadata = 0'],
        ],
//        'prd_cor_arc' => [
//            'className' => 'Cake\Database\Connection',
//            'driver' => 'Cake\Database\Driver\Mysql',
//            'persistent' => false,
//            'host' => 'localhost',
//            'port' => '3306',
//            'username' => 'root',
//            'password' => 'vinove@123',
//            'database' => 'prd_cor_arc',
//            'encoding' => 'utf8mb4',
//            'timezone' => 'UTC',
//            'cacheMetadata' => true,
//            'quoteIdentifiers' => true,
//            //'init' => ['SET GLOBAL innodb_stats_on_metadata = 0'],
//        ],
    ],

    /*
     * Email configuration.
     *
     * Host and credential configuration in case you are using SmtpTransport
     *
     * See app.php for more configuration options.
     */
    'EmailTransport' => [
        'default' => [
            'host' => 'localhost',
            'port' => 25,
            'username' => null,
            'password' => null,
            'client' => null,
            'url' => env('EMAIL_TRANSPORT_DEFAULT_URL', null),
        ],
    ],
];
