<?php
use Cake\Routing\Router;

Router::scope('/Students', ['plugin' => 'Student'], function ($routes) {
	Router::connect('/Students', ['plugin' => 'Student', 'controller' => 'Students']);
	Router::connect('/Students/:action/*', ['plugin' => 'Student', 'controller' => 'Students']);
});
