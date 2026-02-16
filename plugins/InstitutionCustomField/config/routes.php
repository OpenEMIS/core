<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/InstitutionCustomFields', ['plugin' => 'InstitutionCustomField'], function (RouteBuilder $routes) {
        $routes->connect('/InstitutionCustomFields', ['plugin' => 'InstitutionCustomField', 'controller' => 'InstitutionCustomFields']);
        $routes->connect('/InstitutionCustomFields/:action/*', ['plugin' => 'InstitutionCustomField', 'controller' => 'InstitutionCustomFields']);
    });
};
