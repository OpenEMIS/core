<?php
use Cake\Routing\Router;

Router::scope('/Attendances', ['plugin' => 'Attendance'], function ($routes) {
    Router::connect('/Attendances', ['plugin' => 'Attendance', 'controller' => 'Attendances']);
    Router::connect('/Attendances/:action/*', ['plugin' => 'Attendance', 'controller' => 'Attendances']);
});
