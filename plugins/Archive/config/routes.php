<?php
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;

/*Router::plugin(
    'Archive',
    ['path' => '/Archive'],
    function (RouteBuilder $routes) {
        $routes->fallbacks(DashedRoute::class);
    }
);*/

Router::scope('/Archive', ['plugin' => 'Archive'], function ($routes) {
	// Router::connect('/Profiles', ['plugin' => 'Profile', 'controller' => 'Profiles']);
	// Router::connect('/Profiles/:action/*', ['plugin' => 'Profile', 'controller' => 'Profiles']);

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
