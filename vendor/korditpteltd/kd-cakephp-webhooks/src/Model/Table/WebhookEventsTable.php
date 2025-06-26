<?php
namespace Webhook\Model\Table;

use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Exception;
use Cake\Log\Log;

class WebhookEventsTable extends Table
{
    const ACTIVE = 1;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        // Define the association with the Webhook table
        $this->belongsTo('Webhooks', [
            'foreignKey' => 'webhook_id',
            'joinType' => 'INNER',
            'className' => 'Webhook.Webhooks'
        ]);
    }

    public function triggerShell($eventKey, $params = [], $body = [])
    {
        // $webhooks = $this->find()
        //     ->innerJoinWith('Webhooks')
        //     ->where([
        //         'WebhookEvents.event_key' => $eventKey
        //     ])
        //     ->toArray();
        
        // if(!empty($body)) { 
        //     $body = "'".json_encode($body)."'";
        // }

        // $username = isset($params['username']) ? $params['username'] : null;
        // foreach ($webhooks as $key => $value) {
        //     $webhooks[$key]->url = str_replace('{username}', $username, $value->url);
        // }
        // foreach ($webhooks as $webhook) {
        //     $cmd = ROOT . DS . 'bin' . DS . 'cake Webhook ' . $webhook->url . ' ' . $webhook->method . ' ' . $body ;
        //     $logs = ROOT . DS . 'logs' . DS . 'Webhook.log & echo $!';
        //     $shellCmd = $cmd . ' >> ' . $logs;
        //     try {
        //         $pid = exec($shellCmd);
        //     } catch (Exception $ex) {
        //         Log::write('error', __METHOD__ . ' exception when triggering : '. $ex);
        //     }
        // }
    }
}
