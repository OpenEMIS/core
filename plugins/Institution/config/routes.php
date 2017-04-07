<?php
use Cake\Routing\Router;

Router::scope('/Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions'], function ($routes) {
    $routes->connect('/');

    $routes->connect('/:action', [], ['action' => '[a-zA-Z]+']);

    $routes->connect('/:institutionId/:action/*',
        ['plugin' => 'Institution', 'controller' => 'Institutions'],
        ['institutionId' => '([\w]+[\.][\w]+)', 'action' => '[a-zA-Z]+']
    );

    // For controller action version 3
    $routes->connect('/:action/:subaction/*',
        [],
        ['action' => '[a-zA-Z]+', 'subaction' => '([a-zA-Z]+|[\w]+[\.][\w]+)', 'pass' => [0 => 'subaction']]
    );
});
