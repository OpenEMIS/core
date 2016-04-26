<?php
use Cake\Core\Plugin;

Plugin::load('DebugKit', ['bootstrap' => true]);

$remote = 0;

if ($remote) {
    $default = [
        'className' => 'Cake\Database\Connection',
        'driver' => 'Cake\Database\Driver\Mysql',
        'persistent' => false,
        'host' => '216.12.214.10',
        'username' => 'dev_hahmat',
        'password' => '!Sygkuphp1',
        'database' => 'dev_openemis_blz',
        'encoding' => 'utf8',
        'timezone' => 'UTC',
        'cacheMetadata' => true,
        'quoteIdentifiers' => true,
    ];
} else {
    $default = [
        'className' => 'Cake\Database\Connection',
        'driver' => 'Cake\Database\Driver\Mysql',
        'persistent' => false,
        'host' => 'localhost',
        //'port' => 'nonstandard_port_number',
        'username' => 'phpoe',
        'password' => 'phpoe',
        'database' => 'CoreV3',
        // 'database' => 'CoreV3_Blz',
        // 'database' => 'CoreV3_dmo_tst',
        // 'database' => 'CoreV3_Jor_3_4_14',
        'unix_socket' => '/tmp/mysql.sock',
        'encoding' => 'utf8',
        'timezone' => 'UTC',
        'cacheMetadata' => true,
        'quoteIdentifiers' => true,
        //'init' => ['SET GLOBAL innodb_stats_on_metadata = 0'],
    ];
}

return [

    'Error' => [
        'errorLevel' => E_ALL & ~E_DEPRECATED,
        //'exceptionRenderer' => 'App\Error\AppExceptionRenderer',
        'exceptionRenderer' => 'Cake\Error\ExceptionRenderer',
        'skipLog' => [],
        'log' => true,
        'trace' => true,
    ],
    // This is to enable debug kit as specified in the default app
    'debug' => true,

    'Datasources' => [
        'default' => $default,
        /**
         * The test connection is used during the test suite.
         */
        'test' => [
            'unix_socket' => '/tmp/mysql.sock',
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Mysql',
            'persistent' => false,
            'host' => 'localhost',
            //'port' => 'nonstandard_port_number',
            'username' => 'phpoe',
            'password' => 'phpoe',
            'database' => 'OpenEmisCoreV3Test',
            'encoding' => 'utf8',
            'timezone' => 'UTC',
            'cacheMetadata' => true,
            'quoteIdentifiers' => true,
            //'init' => ['SET GLOBAL innodb_stats_on_metadata = 0'],
        ],
    ]
];

