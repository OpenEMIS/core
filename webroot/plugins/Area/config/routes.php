<?php
use Cake\Routing\Router;

Router::scope('/Areas', ['plugin' => 'Area'], function ($routes) {
	Router::connect('/Areas', ['plugin' => 'Area', 'controller' => 'Areas']);
	Router::connect('/Areas/:action/*', ['plugin' => 'Area', 'controller' => 'Areas']);
});
