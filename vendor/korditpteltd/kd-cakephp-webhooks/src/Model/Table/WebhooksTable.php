<?php
namespace Webhook\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Exception;

class WebhooksTable extends Table
{
    const ACTIVE = 1;
    const INACTIVE = 0;

    public $supportedMethod = [
        'GET' => 'GET',
        'POST' => 'POST',
        'PUT' => 'PUT',
        'PATCH' => 'PATCH',
        'DELETE' => 'DELETE'
    ];

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->hasMany('WebhookEvents', ['className' => 'Webhook.WebhookEvents', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created' => 'new',
                    'modified' => 'existing'
                ]
            ]
        ]);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $userId = null;
        if (isset($options['extra']['user'])) {
            $userId = $options['extra']['user']['id'];
        } if (isset($_SESSION['Auth']) && isset($_SESSION['Auth']['User'])) {
            $userId = $_SESSION['Auth']['User']['id'];
        }
        if (is_null($userId)) {
            $userId = 0;
        }
        if (!$entity->isNew()) {
            $entity->modified_user_id = $userId;
        } else {
            $entity->created_user_id = $userId;
        }
    }

    public function findActiveWebhooks(Query $query, array $options)
    {
        $eventKey = $options['event_key'];

        return $query->innerJoinWith('WebhookEvents')
            ->where([
                'WebhookEvents.event_key' => $eventKey,
                $this->aliasField('status') => self::ACTIVE
            ])
            ->select([$this->aliasField('url'), $this->aliasField('method')]);
    }

    public function triggerShell($eventKey, $params = [], $body = [])
    { 
        $webhooks = $this->find()
            ->innerJoinWith('WebhookEvents')
            ->where([
                'WebhookEvents.event_key' => $eventKey,
                $this->aliasField('status') => self::ACTIVE
            ])
            ->toArray();
		
		if(!empty($body)) { 
            $body = "'".json_encode($body)."'";
        }
	
        $username = isset($params['username']) ? $params['username'] : null;
        foreach ($webhooks as $key => $value) {
            $webhooks[$key]->url = str_replace('{username}', $username, $value->url);
        }
        foreach ($webhooks as $webhook) {
            $cmd = ROOT . DS . 'bin' . DS . 'cake Webhook ' . $webhook->url . ' ' . $webhook->method . ' ' . $body ;
            $logs = ROOT . DS . 'logs' . DS . 'Webhook.log & echo $!';
            $shellCmd = $cmd . ' >> ' . $logs;
            try {
                $pid = exec($shellCmd);
            } catch (Exception $ex) {
                Log::write('error', __METHOD__ . ' exception when triggering : '. $ex);
            }
        }
    }
}
