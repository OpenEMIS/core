<?php
use Cake\Routing\Router;

Router::scope('/Rest', ['plugin' => 'Restful'], function ($routes) {
	Router::connect('/Rest', ['plugin' => 'Restful', 'controller' => 'Rest']);
	Router::connect('/Rest/:action/*', ['plugin' => 'Restful', 'controller' => 'Rest']);
});
