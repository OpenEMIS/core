<?php
use Cake\Routing\Router;

Router::scope('/Reports', ['plugin' => 'Report'], function ($routes) {
	Router::connect('/Reports', ['plugin' => 'Report', 'controller' => 'Reports']);
	Router::connect('/Reports/:action/*', ['plugin' => 'Report', 'controller' => 'Reports']);
});
