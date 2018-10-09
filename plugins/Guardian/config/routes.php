<?php
use Cake\Routing\Router;

Router::scope('/Guardian', ['plugin' => 'Guardian'], function ($routes) {

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

	Router::connect('/Guardians', ['plugin' => 'Guardian', 'controller' => 'Guardians']);
	Router::connect('/Guardians/:action/*', ['plugin' => 'Guardian', 'controller' => 'Guardians']);
});
