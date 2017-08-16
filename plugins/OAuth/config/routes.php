<?php

use Cake\Routing\Router;

Router::scope('/oauth', ['controller' => 'OAuth'], function ($r) {
    $r->extensions(['json']);
    $r->connect('/:action/*', ['_ext' => 'json']);
});
