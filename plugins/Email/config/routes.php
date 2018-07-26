<?php
use Cake\Routing\Router;

Router::scope('/Email', ['plugin' => 'Email'], function ($routes) {
	Router::connect('/Email', ['plugin' => 'Email', 'controller' => 'Email']);
	Router::connect('/Email/:action/*', ['plugin' => 'Email', 'controller' => 'Email']);
});
