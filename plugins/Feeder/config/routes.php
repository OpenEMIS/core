<<?php
use Cake\Routing\Router;

Router::scope('/Feeders', ['plugin' => 'Feeder'], function ($routes) {
	Router::connect('/Feeders', ['plugin' => 'Feeder', 'controller' => 'Feeders']);
	Router::connect('/Feeders/:action/*', ['plugin' => 'Feeder', 'controller' => 'Feeders']);
});
