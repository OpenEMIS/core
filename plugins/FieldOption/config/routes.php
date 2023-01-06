<?php
use Cake\Routing\Router;

Router::scope('/FieldOptions', ['plugin' => 'FieldOption'], function ($routes) {
	Router::connect('/FieldOptions', ['plugin' => 'FieldOption', 'controller' => 'FieldOptions']);
	Router::connect('/FieldOptions/:action/*', ['plugin' => 'FieldOption', 'controller' => 'FieldOptions']);
});
