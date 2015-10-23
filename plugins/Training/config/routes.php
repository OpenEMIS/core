<?php
use Cake\Routing\Router;

Router::scope('/Trainings', ['plugin' => 'Training'], function ($routes) {
	Router::connect('/Trainings', ['plugin' => 'Training', 'controller' => 'Trainings']);
	Router::connect('/Trainings/:action/*', ['plugin' => 'Training', 'controller' => 'Trainings']);
});
