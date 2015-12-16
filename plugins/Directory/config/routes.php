<?php
use Cake\Routing\Router;

Router::scope('/Directories', ['plugin' => 'Directory'], function ($routes) {
	Router::connect('/Directories', ['plugin' => 'Directory', 'controller' => 'Directories']);
	Router::connect('/Directories/:action/*', ['plugin' => 'Directory', 'controller' => 'Directories']);
});
