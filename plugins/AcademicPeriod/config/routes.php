<?php
use Cake\Routing\Router;

Router::scope('/AcademicPeriods', ['plugin' => 'AcademicPeriod'], function ($routes) {
	Router::connect('/AcademicPeriods', ['plugin' => 'AcademicPeriod', 'controller' => 'AcademicPeriods']);
	Router::connect('/AcademicPeriods/:action/*', ['plugin' => 'AcademicPeriod', 'controller' => 'AcademicPeriods']);
});
