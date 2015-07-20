<?php
use Cake\Routing\Router;

Router::scope('/Translations', ['plugin' => 'Localization'], function ($routes) {
	Router::connect('/Translations', ['plugin' => 'Localization', 'controller' => 'Translations']);
	Router::connect('/Translations/:action/*', ['plugin' => 'Localization', 'controller' => 'Translations']);
});
