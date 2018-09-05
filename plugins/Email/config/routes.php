<?php
use Cake\Routing\Router;

Router::scope('/Emails', ['plugin' => 'Email'], function ($routes) {
	Router::connect('/Emails', ['plugin' => 'Email', 'controller' => 'Emails']);
	Router::connect('/Emails/:action/*', ['plugin' => 'Email', 'controller' => 'Emails']);
});
