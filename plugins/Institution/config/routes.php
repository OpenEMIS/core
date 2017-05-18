<?php
use Cake\Routing\Router;

Router::scope('/Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions'], function ($routes) {

    // Will have to modify this to point to institution action when institutions table is migrated to CAv4

    $routes->connect('/:institutionId/:action/*',
        ['plugin' => 'Institution', 'controller' => 'Institutions'],
        ['institutionId' => '([\w]+[\.][\w]+)', 'action' => '[a-zA-Z]+']
    );

    // // For the main model's action
    // $routes->connect('/:subaction',
    //     ['action' => 'Institutions'],
    //     ['subaction' => '[a-zA-Z]+', 'pass' => [0 => 'subaction']]
    // );

    // For controller action version 3
    $routes->connect('/:action/*',
        [],
        ['action' => '[a-zA-Z]+']
    );
});
