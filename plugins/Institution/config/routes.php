<?php
use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;

$routes->scope('/Institution', ['plugin' => 'Institution'], function (RouteBuilder $routes) {
    $routes->scope('/Institutions', ['controller' => 'Institutions'], function (RouteBuilder $route) {
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

    $routes->scope('/:institutionId/:controller', [], function (RouteBuilder $route) {
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
$routes->scope('/Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions'], function (RouteBuilder $route) {
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

    // For controller action version
    $route->connect(
        '/:action/*',
        [],
        ['action' => '[a-zA-Z]+']
    );
});

$routes->scope('/Institutions', ['plugin' => 'Institution'], function ($routes) {
    $routes->scope('/', ['controller' => 'InstitutionHistories'], function ($routes) {
        // $routes->extensions(['json']);
        $routes->connect('/InstitutionHistories/:action/:key/*', ['action' => 'index', '_method' => 'GET'], ['pass' => ['key']]);
    });

    
});
