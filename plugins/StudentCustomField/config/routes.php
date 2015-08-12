<?php
use Cake\Routing\Router;

Router::scope('/StudentCustomFields', ['plugin' => 'StudentCustomField'], function ($routes) {
	Router::connect('/StudentCustomFields', ['plugin' => 'StudentCustomField', 'controller' => 'StudentCustomFields']);
	Router::connect('/StudentCustomFields/:action/*', ['plugin' => 'StudentCustomField', 'controller' => 'StudentCustomFields']);
});
