<?php
use Cake\Routing\Router;

Router::scope('/Angular', ['plugin' => 'Angular'], function ($routes) {
	Router::connect('/Angular', ['plugin' => 'Angular', 'controller' => 'Angular']);
	Router::connect('/Angular/:action/*', ['plugin' => 'Angular', 'controller' => 'Angular']);
});
