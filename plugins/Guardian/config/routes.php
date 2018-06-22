<?php
use Cake\Routing\Router;

Router::scope('/Guardians', ['plugin' => 'Guardian'], function ($routes) {
	Router::connect('/Guardians', ['plugin' => 'Guardian', 'controller' => 'Guardians']);
	Router::connect('/Guardians/:action/*', ['plugin' => 'Guardian', 'controller' => 'Guardians']);
});
