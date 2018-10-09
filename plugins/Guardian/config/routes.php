<?php
use Cake\Routing\Router;

Router::scope('/Guardians', ['plugin' => 'Guardian'], function ($routes) {

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
