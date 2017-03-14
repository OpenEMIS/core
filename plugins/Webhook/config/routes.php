<?php
use Cake\Routing\Router;

Router::scope('/Webhooks', ['plugin' => 'Webhook'], function ($routes) {
	Router::connect('/Webhooks', ['plugin' => 'Webhook', 'controller' => 'Webhooks']);
	Router::connect('/Webhooks/:action/*', ['plugin' => 'Webhook', 'controller' => 'Webhooks', '_ext' => 'json']);
});
