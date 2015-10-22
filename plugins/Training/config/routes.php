<?php
use Cake\Routing\Router;

Router::scope('/TrainingCourses', ['plugin' => 'Training'], function ($routes) {
	Router::connect('/TrainingCourses', ['plugin' => 'Training', 'controller' => 'TrainingCourses']);
	Router::connect('/TrainingCourses/:action/*', ['plugin' => 'Training', 'controller' => 'TrainingCourses']);
});

Router::scope('/TrainingSessions', ['plugin' => 'Training'], function ($routes) {
	Router::connect('/TrainingSessions', ['plugin' => 'Training', 'controller' => 'TrainingSessions']);
	Router::connect('/TrainingSessions/:action/*', ['plugin' => 'Training', 'controller' => 'TrainingSessions']);
});
