<?php
use Cake\Routing\Router;

Router::scope('/Profiles', ['plugin' => 'Profile'], function ($routes) {
	Router::connect('/Profiles', ['plugin' => 'Profile', 'controller' => 'Profiles']);
	Router::connect('/Profiles/:action/*', ['plugin' => 'Profile', 'controller' => 'Profiles']);
});
