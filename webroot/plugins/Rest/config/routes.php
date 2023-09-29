<?php
use Cake\Routing\Router;

Router::scope('/Rest', ['plugin' => 'Rest'], function ($routes) {
	Router::connect('/Rest', ['plugin' => 'Rest', 'controller' => 'Rest']);
	Router::connect('/Rest/:action/*', ['plugin' => 'Rest', 'controller' => 'Rest']);
});
