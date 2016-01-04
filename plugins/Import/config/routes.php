<?php
use Cake\Routing\Router;

Router::scope('/Imports', ['plugin' => 'Import'], function ($routes) {
	Router::connect('/Imports', ['plugin' => 'Import', 'controller' => 'Imports']);
	Router::connect('/Imports/:action/*', ['plugin' => 'Import', 'controller' => 'Imports']);
});
