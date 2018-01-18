<?php
use Cake\Routing\Router;

Router::scope('/Institution', ['plugin' => 'Institution'], function ($routes) {
    $routes->scope('/Institutions', ['controller' => 'Institutions'], function ($route) {
        $route->connect(
            '/',
            ['action' => 'Institutions', ]
        );

        // For the main model's action
        $route->connect(
            '/:indexAction',
            [],
            ['indexAction' => 'index','pass' => [0 => 'indexAction']]
        );

        $route->connect(
            '/:institutionId/:action/*',
            [],
            ['institutionId' => '([\w]+[\.][\w]+)', 'action' => '[a-zA-Z]+']
        );

        // For controller action version 3
        $route->connect(
            '/:action/*',
            [],
            ['action' => '[a-zA-Z]+']
        );
    });

    $routes->scope('/:institutionId/:controller', [], function ($route) {
        $route->connect(
            '/:action',
            [],
            ['institutionId' => '([\w]+[\.][\w]+)', 'action' => '[a-zA-Z]+']
        );

        $route->connect(
            '/:action/*',
            [],
            ['institutionId' => '([\w]+[\.][\w]+)', 'action' => '[a-zA-Z]+']
        );
    });
});


// Fall back route, to be deleted after the URL is fixed. (affected staffTransferRequest, staffTransferApproval)
Router::scope('/Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions'], function ($route) {
    $route->connect(
        '/',
        ['action' => 'Institutions', ]
    );

    // For the main model's action
    $route->connect(
        '/:indexAction',
        [],
        ['indexAction' => 'index','pass' => [0 => 'indexAction']]
    );

    $route->connect('/Institutions/*', [
        'action' => 'Institutions'
    ]);

    $route->connect(
        '/:institutionId/:action/*',
        [],
        ['institutionId' => '([\w]+[\.][\w]+)', 'action' => '[a-zA-Z]+']
    );

    // For controller action version 3
    $route->connect(
        '/:action/*',
        [],
        ['action' => '[a-zA-Z]+']
    );
});
