<?php
return [
        'Datasources' => [
        'test' => [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Mysql',
            'persistent' => false,
            'host' => 'dev-rds-001.cylegaoegjx0.ap-southeast-1.rds.amazonaws.com',
            //'port' => 'nonstandard_port_number',
            'username' => 'dev_cor_phpu',
            'password' => '5dVm8rKs8d5UU',
            'database' => 'dev_cor_phpu',
            'encoding' => 'utf8',
            'timezone' => 'UTC',
            'cacheMetadata' => true,
            'quoteIdentifiers' => true,
            //'init' => ['SET GLOBAL innodb_stats_on_metadata = 0'],
        ]
    ]
];
