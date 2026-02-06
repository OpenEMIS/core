<?php
use Cake\Routing\RouteBuilder;
return function (RouteBuilder $routes) {
    $routes->scope('/Trainings', ['plugin' => 'Training', 'controller' => 'Trainings'], function (RouteBuilder $routes) {
    
        $routes->connect('/',
            ['action' => 'Trainings']
        );
    
        // For the main model's action
        $routes->connect('/:indexAction',
            ['action' => 'Trainings'],
            ['indexAction' => 'index','pass' => [0 => 'indexAction']]
        );
    
        $routes->connect('/:trainingId/:action/*',
            ['plugin' => 'Training', 'controller' => 'Trainings'],
            ['trainingId' => '([\w]+[\.][\w]+)', 'action' => '[a-zA-Z]+']
        );
    
        // For controller action version 3
        $routes->connect('/:action/*',
            [],
            ['action' => '[a-zA-Z]+']
        );
    });
};
