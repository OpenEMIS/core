<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/GuardianNavs', ['plugin' => 'GuardianNav'], function (RouteBuilder $routes) {
        $routes->connect('/GuardianNavs', ['plugin' => 'GuardianNav', 'controller' => 'GuardianNavs']);
        $routes->connect('/GuardianNavs/:action/*', ['plugin' => 'GuardianNav', 'controller' => 'GuardianNavs']);
    });
};
