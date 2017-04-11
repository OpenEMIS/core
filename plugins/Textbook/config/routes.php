<?php
use Cake\Routing\Router;

Router::scope('/Textbooks', ['plugin' => 'Textbook'], function ($routes) {
	Router::connect('/Textbooks', ['plugin' => 'Textbook', 'controller' => 'Textbooks']);
	Router::connect('/Textbooks/:action/*', ['plugin' => 'Textbook', 'controller' => 'Textbooks']);
});
