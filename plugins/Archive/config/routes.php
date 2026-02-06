<?php
use Cake\Routing\RouteBuilder;

/*Router::plugin(
    'Archive',
    ['path' => '/Archive'],
    function (RouteBuilder $routes) {
        $routes->fallbacks(DashedRoute::class);
    }
);*/

return function (RouteBuilder $routes) {
    $routes->scope('/Archive', ['plugin' => 'Archive'], function (RouteBuilder $routes) {
        // Router::connect('/Profiles', ['plugin' => 'Profile', 'controller' => 'Profiles']);
        // Router::connect('/Profiles/:action/*', ['plugin' => 'Profile', 'controller' => 'Profiles']);

        $routes->scope('/:controller', [], function (RouteBuilder $route) {
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
};
