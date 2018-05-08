<?php
use Cake\Routing\Router;

Router::scope('/Scholarship', ['plugin' => 'Scholarship'], function ($routes) {
    $routes->scope('/ScholarshipApplications', ['controller' => 'ScholarshipApplications'], function ($route){
        $route->connect('/',
            ['action' => 'ScholarshipApplications' ]
        );
    });
 
    $routes->scope('/:controller', [], function ($route) {
        $route->connect('/:action',
            [],
            ['action' => '[a-zA-Z]+']
        );

        $route->connect('/:action/*',
            [],
            ['action' => '[a-zA-Z]+']
        );
    });
});

Router::scope('/ScholarshipApplications', ['plugin' => 'Scholarship', 'controller' => 'ScholarshipApplications'], function ($routes) {
    $routes->connect('/',
        ['action' => 'ScholarshipApplications']
    );

    // For controller action version 3
    $routes->connect('/:action/*',
        [],
        ['action' => '[a-zA-Z]+']
    );
});