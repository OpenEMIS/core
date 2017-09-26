<?php
use Cake\Routing\Router;

Router::scope('/Directories', ['plugin' => 'Directory'], function ($routes) {
	// Router::connect('/Directories', ['plugin' => 'Directory', 'controller' => 'Directories']);
	// Router::connect('/Directories/:action/*', ['plugin' => 'Directory', 'controller' => 'Directories']);

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
