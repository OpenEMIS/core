<?php
use Cake\Routing\Router;

Router::scope('/CustomFields', ['plugin' => 'CustomField'], function ($routes) {
	Router::connect('/CustomFields', ['plugin' => 'CustomField', 'controller' => 'CustomFields']);
	Router::connect('/CustomFields/:action/*', ['plugin' => 'CustomField', 'controller' => 'CustomFields']);
});
