<?php
use Cake\Routing\Router;

Router::scope('/InstitutionRepeaters', ['plugin' => 'InstitutionRepeater'], function ($routes) {
	Router::connect('/InstitutionRepeaters', ['plugin' => 'InstitutionRepeater', 'controller' => 'InstitutionRepeaters']);
	Router::connect('/InstitutionRepeaters/:action/*', ['plugin' => 'InstitutionRepeater', 'controller' => 'InstitutionRepeaters']);
});
