<?php
use Cake\Routing\Router;

Router::scope('/InstitutionCustomFields', ['plugin' => 'InstitutionCustomField'], function ($routes) {
	Router::connect('/InstitutionCustomFields', ['plugin' => 'InstitutionCustomField', 'controller' => 'InstitutionCustomFields']);
	Router::connect('/InstitutionCustomFields/:action/*', ['plugin' => 'InstitutionCustomField', 'controller' => 'InstitutionCustomFields']);
});
