<?php
use Cake\Routing\Router;

Router::scope('/SpecialNeeds', ['plugin' => 'SpecialNeeds'], function ($routes) {
	Router::connect('/SpecialNeeds', ['plugin' => 'SpecialNeeds', 'controller' => 'SpecialNeeds']);
	Router::connect('/SpecialNeeds/:action/*', ['plugin' => 'SpecialNeeds', 'controller' => 'SpecialNeeds']);
});
