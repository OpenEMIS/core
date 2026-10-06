<?php

namespace Webhook\Controller;

use Cake\Controller\Controller;
use Cake\ORM\Locator\LocatorAwareTrait;

class WebhooksController extends Controller
{
    use LocatorAwareTrait;

    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Auth');
        $this->loadComponent('RequestHandler');
    }

    public function listWebhooks($eventKey)
    {
        $webhooksTable = $this->fetchTable('Webhook.Webhooks');

        $webhooksList = $webhooksTable
            ->find('activeWebhooks', ['event_key' => $eventKey])
            ->disableHydration()
            ->toArray();

        $user = $this->Auth->user();
        $username = $user['username'] ?? '';

        foreach ($webhooksList as $key => $value) {
            $webhooksList[$key] = str_replace(
                '{username}',
                $username,
                $value
            );
        }

        $this->set('data', $webhooksList);
        $this->viewBuilder()->setOption('serialize', ['data']);
    }
}