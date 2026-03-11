<?php
// POCOR-9257: Webhook logs table (read-only)
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use App\Model\Table\ControllerActionTable;

class WebhookLogsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('webhook_logs');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
        parent::initialize($config);

        //POCOR-9257: Disable add and edit actions; this is a read-only operational log
        $this->toggle('add', false);
        $this->toggle('edit', false);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra): void
    {
        //POCOR-9257: Hide large/technical fields from the index view
        $this->field('payload', ['visible' => false]);
        $this->field('headers', ['visible' => false]);
        $this->field('response_body', ['visible' => false]);
        $this->field('response_headers', ['visible' => false]);
        $this->field('error_message', ['visible' => false]);
        $this->field('checksum', ['visible' => false]);
        $this->field('webhook_id', ['visible' => false]);
        $this->field('webhook_queue_id', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('success',['visible' => false]);

        // Order fields for better readability
        $this->field('event_key', ['after' => 'id']);
        $this->field('target_url', ['after' => 'event_key']);
        $this->field('http_method', ['after' => 'target_url']);
        $this->field('response_status', ['after' => 'http_method']);
        $this->field('duration_ms', ['after' => 'response_status']);
        $this->field('retry_attempt', ['after' => 'duration_ms']);
        $this->field('created', ['after' => 'retry_attempt']);
    }

    public function onGetSuccess(EventInterface $event, Entity $entity): string
    {
        return $entity->success == 1 ? 'Success' : 'Failed';
    }
}
