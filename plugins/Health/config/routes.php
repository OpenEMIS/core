<?php
use Cake\Routing\Router;

Router::scope('/Healths', ['plugin' => 'Health'], function ($routes) {
	Router::connect('/Healths', ['plugin' => 'Health', 'controller' => 'Healths']);
	Router::connect('/Healths/:action/*', ['plugin' => 'Health', 'controller' => 'Healths']);
});
