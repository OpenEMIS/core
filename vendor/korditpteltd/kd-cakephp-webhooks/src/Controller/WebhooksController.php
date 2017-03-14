<?php
namespace Webhook\Controller;
use Cake\ORM\TableRegistry;
use Cake\Controller\Controller;

class WebhooksController extends Controller
{
	public function initialize()
    {
		parent::initialize();
        $this->loadComponent('RequestHandler');
	}

    public function listWebhooks($eventKey)
    {
        $WebhooksTable = TableRegistry::get('Webhook.Webhooks');
        $webhooksList = $WebhooksTable
            ->find('activeWebhooks', ['event_key' => $eventKey])
            ->hydrate(false)
            ->toArray();
        $this->set(['data' => $webhooksList]);
        $this->set('_serialize', ['data']);
    }
}
