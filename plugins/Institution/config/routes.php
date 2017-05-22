<?php
use Cake\Routing\Router;

Router::scope('/Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions'], function ($routes) {

    $routes->connect('/',
        ['action' => 'Institutions']
    );

    $routes->connect('/:institutionId/:action/*',
        ['plugin' => 'Institution', 'controller' => 'Institutions'],
        ['institutionId' => '([\w]+[\.][\w]+)', 'action' => '[a-zA-Z]+']
    );

    // For the main model's action
    $routes->connect('/:subaction',
        ['action' => 'Institutions'],
        ['subaction' => '[a-zA-Z]+', 'pass' => [0 => 'subaction']]
    );

    // For controller action version 3
    $routes->connect('/:action/*',
        [],
        ['action' => '[a-zA-Z]+']
    );
});
