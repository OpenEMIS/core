<?php
use Cake\Routing\Router;

Router::scope('/StaffCustomFields', ['plugin' => 'StaffCustomField'], function ($routes) {
	Router::connect('/StaffCustomFields', ['plugin' => 'StaffCustomField', 'controller' => 'StaffCustomFields']);
	Router::connect('/StaffCustomFields/:action/*', ['plugin' => 'StaffCustomField', 'controller' => 'StaffCustomFields']);
});
