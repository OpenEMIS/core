<?php
use Cake\Routing\Router;

Router::scope('/Appraisals', ['plugin' => 'StaffAppraisal'], function ($routes) {
    $routes->connect('/:action/*', ['controller' => 'StaffAppraisals']);
});
