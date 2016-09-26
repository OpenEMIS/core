<?php
use Cake\Routing\Router;

Router::scope('/Configurations', ['plugin' => 'Configuration'], function ($routes) {
	Router::connect('/Configurations', ['plugin' => 'Configuration', 'controller' => 'Configurations']);
	Router::connect('/Configurations/:action/*', ['plugin' => 'Configuration', 'controller' => 'Configurations']);
});
