<?php
use Cake\Routing\Router;

Router::scope('/GuardianNavs', ['plugin' => 'GuardianNav'], function ($routes) {
    Router::connect('/GuardianNavs', ['plugin' => 'GuardianNav', 'controller' => 'GuardianNavs']);
    Router::connect('/GuardianNavs/:action/*', ['plugin' => 'GuardianNav', 'controller' => 'GuardianNavs']);
});