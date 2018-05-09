<?php
use Cake\Routing\Router;

Router::scope('/Scholarship', ['plugin' => 'Scholarship'], function ($routes) {
    $routes->scope('/Scholarships', ['controller' => 'Scholarships'], function ($route){
        $route->connect('/',
            ['action' => 'Scholarships' ]
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

Router::scope('/Scholarships', ['plugin' => 'Scholarship', 'controller' => 'Scholarships'], function ($routes) {
    $routes->connect('/',
        ['action' => 'Scholarships']
    );

    // For controller action version 3
    $routes->connect('/:action/*',
        [],
        ['action' => '[a-zA-Z]+']
    );
});