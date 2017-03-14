<?php
use Cake\Routing\Router;

Router::scope('/Webhooks', ['plugin' => 'Webhook'], function ($routes) {
	Router::connect('/Webhooks', ['plugin' => 'Webhook', 'controller' => 'Webhooks']);
    Router::connect('/Webhooks/listWebhooks/*', ['plugin' => 'Webhook', 'controller' => 'Webhooks', 'action' => 'listWebhooks', '_ext' => 'json']);
	Router::connect('/Webhooks/:action/*', ['plugin' => 'Webhook', 'controller' => 'Webhooks']);
});

