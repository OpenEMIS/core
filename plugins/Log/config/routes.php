<?php
use Cake\Routing\Router;

Router::scope('/Logs', ['plugin' => 'Log'], function ($routes) {
	Router::connect('/Logs', ['plugin' => 'Log', 'controller' => 'Logs']);
	Router::connect('/Logs/:action/*', ['plugin' => 'Log', 'controller' => 'Logs']);
});
