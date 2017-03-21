<?php
namespace Webhook\Controller;
use Cake\ORM\TableRegistry;
use Cake\Controller\Controller;

class WebhooksController extends Controller
{
	public function initialize()
    {
		parent::initialize();
        $this->loadComponent('Auth');
        $this->loadComponent('RequestHandler');
	}

    public function listWebhooks($eventKey)
    {
        $WebhooksTable = TableRegistry::get('Webhook.Webhooks');
        $webhooksList = $WebhooksTable
            ->find('activeWebhooks', ['event_key' => $eventKey])
            ->hydrate(false)
            ->toArray();

        $username = $this->Auth->user()['username'];
        foreach ($webhooksList as $key => $value) {
            $webhooksList[$key] = str_replace('{username}', $username, $value);
        }

        $this->set(['data' => $webhooksList]);
        $this->set('_serialize', ['data']);
    }
}
