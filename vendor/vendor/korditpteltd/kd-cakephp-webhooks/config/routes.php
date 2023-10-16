<?php
use Cake\Routing\Router;

Router::scope('/Webhooks', ['plugin' => 'Webhook'], function ($routes) {
    $routes->connect('/Webhooks', ['plugin' => 'Webhook', 'controller' => 'Webhooks']);
    $routes->connect('/Webhooks/listWebhooks/*', ['plugin' => 'Webhook', 'controller' => 'Webhooks', 'action' => 'listWebhooks', '_ext' => 'json']);
    $routes->connect('/Webhooks/:action/*', ['plugin' => 'Webhook', 'controller' => 'Webhooks']);
});
