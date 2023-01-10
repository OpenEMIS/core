<?php
use Cake\Routing\Router;

Router::scope('/Securities', ['plugin' => 'Security'], function ($routes) {
	Router::connect('/Securities', ['plugin' => 'Security', 'controller' => 'Securities']);
	Router::connect('/Securities/:action/*', ['plugin' => 'Security', 'controller' => 'Securities']);
});
