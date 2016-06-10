<?php
use Cake\Routing\Router;

Router::scope('/Caches', ['plugin' => 'Cache'], function ($routes) {
	Router::connect('/Caches', ['plugin' => 'Cache', 'controller' => 'Caches']);
	Router::connect('/Caches/:action/*', ['plugin' => 'Cache', 'controller' => 'Caches']);
});
