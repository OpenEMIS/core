<?php
use Cake\Routing\Router;

Router::scope('/Educations', ['plugin' => 'Education'], function ($routes) {
	Router::connect('/Educations', ['plugin' => 'Education', 'controller' => 'Educations']);
	Router::connect('/Educations/:action/*', ['plugin' => 'Education', 'controller' => 'Educations']);
});
