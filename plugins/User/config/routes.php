<?php
use Cake\Routing\Router;

Router::scope('/Users', ['plugin' => 'User'], function ($routes) {
	Router::connect('/Users', ['plugin' => 'User', 'controller' => 'Users']);
	Router::connect('/Users/:action/*', ['plugin' => 'User', 'controller' => 'Users']);
});
