<?php
use Cake\Routing\Router;

Router::scope('/Competencies', ['plugin' => 'Competency'], function ($routes) {
	Router::connect('/Competencies', ['plugin' => 'Competency', 'controller' => 'Competencies']);
	Router::connect('/Competencies/:action/*', ['plugin' => 'Competency', 'controller' => 'Competencies']);
});
