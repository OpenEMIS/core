<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Appraisals', ['plugin' => 'StaffAppraisal'], function (RouteBuilder $routes) {
        $routes->connect('/:action/*', ['controller' => 'StaffAppraisals']);
    });
};