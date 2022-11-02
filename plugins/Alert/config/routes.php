<?php
use Cake\Routing\Router;

Router::scope('/Alerts', ['plugin' => 'Alert'], function ($routes) {
	Router::connect('/Alerts', ['plugin' => 'Alert', 'controller' => 'Alerts']);
	Router::connect('/Alerts/:action/*', ['plugin' => 'Alert', 'controller' => 'Alerts']);
});
