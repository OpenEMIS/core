<?php
use Cake\Routing\Router;

Router::scope('/Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions'], function ($routes) {

    $routes->connect('/',
        ['action' => 'Institutions']
    );

    // For the main model's action
    $routes->connect('/:indexAction',
        ['action' => 'Institutions'],
        ['indexAction' => 'index','pass' => [0 => 'indexAction']]
    );

    $routes->connect('/:institutionId/:action/*',
        ['plugin' => 'Institution', 'controller' => 'Institutions'],
        ['institutionId' => '([\w]+[\.][\w]+)', 'action' => '[a-zA-Z]+']
    );

    // For controller action version 3
    $routes->connect('/:action/*',
        [],
        ['action' => '[a-zA-Z]+']
    );
});
