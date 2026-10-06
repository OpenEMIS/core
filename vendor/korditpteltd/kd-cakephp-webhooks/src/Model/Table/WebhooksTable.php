<?php

namespace Webhook\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Log\Log;
use Cake\ORM\Entity;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Exception;

class WebhooksTable extends Table
{
    public const ACTIVE = 1;
    public const INACTIVE = 0;

    public array $supportedMethod = [
        'GET' => 'GET',
        'POST' => 'POST',
        'PUT' => 'PUT',
        'PATCH' => 'PATCH',
        'DELETE' => 'DELETE',
    ];

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->hasMany('WebhookEvents', [
            'className' => 'Webhook.WebhookEvents',
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created' => 'new',
                    'modified' => 'existing',
                ],
            ],
        ]);
    }

    public function beforeSave(
        EventInterface $event,
        Entity $entity,
        ArrayObject $options
    ): void {
        $userId = null;

        if (isset($options['extra']['user'])) {
            $userId = $options['extra']['user']['id'] ?? null;
        }

        if (isset($_SESSION['Auth']['User'])) {
            $userId = $_SESSION['Auth']['User']['id'] ?? null;
        }

        if ($userId === null) {
            $userId = 0;
        }

        if (!$entity->isNew()) {
            $entity->modified_user_id = $userId;
        } else {
            $entity->created_user_id = $userId;
        }
    }

    public function findActiveWebhooks(
        SelectQuery $query,
        array $options
    ): SelectQuery {
        $eventKey = $options['event_key'] ?? null;

        return $query
            ->innerJoinWith('WebhookEvents')
            ->where([
                'WebhookEvents.event_key' => $eventKey,
                $this->aliasField('status') => self::ACTIVE,
            ])
            ->select([
                $this->aliasField('url'),
                $this->aliasField('method'),
            ]);
    }

    public function triggerShell(
        $eventKey,
        array $params = [],
        array $body = []
    ): void {
        $webhooks = $this->find()
            ->innerJoinWith('WebhookEvents')
            ->where([
                'WebhookEvents.event_key' => trim($eventKey),
                $this->aliasField('status') => self::ACTIVE,
            ])
            ->toArray();

        $bodyArgument = '';

        if (!empty($body)) {
            $bodyArgument = escapeshellarg(json_encode($body));
        }

        $username = $params['username'] ?? null;

        foreach ($webhooks as $key => $value) {
            $webhooks[$key]->url = str_replace(
                '{username}',
                $username,
                $value->url
            );
        }

        foreach ($webhooks as $webhook) {
            $url = escapeshellarg($webhook->url);
            $method = escapeshellarg($webhook->method);

            $cmd = ROOT
                . DS . 'bin'
                . DS . 'cake Webhook '
                . $url
                . ' '
                . $method;

            if ($bodyArgument !== '') {
                $cmd .= ' ' . $bodyArgument;
            }

            $logs = ROOT
                . DS . 'logs'
                . DS . 'Webhook.log';

            $shellCmd = $cmd
                . ' >> '
                . escapeshellarg($logs)
                . ' 2>&1 & echo $!';

            try {
                $pid = exec($shellCmd);
            } catch (Exception $ex) {
                Log::write(
                    'error',
                    __METHOD__ . ' exception when triggering: ' . $ex->getMessage()
                );
            }
        }
    }
}