<?php
// POCOR-9257: Webhook logs table (read-only) - moved to Alert plugin
declare(strict_types=1);

namespace Alert\Model\Table; //POCOR-9257: moved from App\Model\Table

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Log\Log;
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

        //POCOR-9694: cross-link to Async Services → Webhook Failures dashboard.
        $checkFailures = [
            'type' => 'button',
            'label' => '<i class="fa fa-exclamation-triangle"></i>',
            'attr' => [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'title' => __('Check failures'),
            ],
            'url' => [
                'plugin' => 'System', 'controller' => 'Systems',
                'action' => 'WebhookFailures',
            ],
        ];
        $toolbarButtons = $extra['toolbarButtons']->getArrayCopy();
        $toolbarButtons['checkFailures'] = $checkFailures;
        $extra['toolbarButtons']->exchangeArray($toolbarButtons);
    }

    public function onGetSuccess(EventInterface $event, Entity $entity): string
    {
        return $entity->success == 1 ? 'Success' : 'Failed';
    }

    //POCOR-9257: Explicitly build view/remove URLs with encoded ID (same pattern as WebhookQueueTable)
    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons): array
    {
        $id = $this->getEncodedKeys($entity); //POCOR-9257: get encoded primary key

        if (isset($buttons['view'])) {
            $buttons['view']['url'] = [
                'plugin' => 'Alert', //POCOR-9257: moved to Alert plugin
                'controller' => 'Webhook',
                'action' => 'WebhookLogs',
                0 => 'view',
                1 => $id,
            ];
        }

        if (isset($buttons['remove'])) {
            //POCOR-9257: trigger delete modal (same as view-page toolbar button)
            $buttons['remove']['attr']['data-toggle'] = 'modal';
            $buttons['remove']['attr']['data-target'] = '#delete-modal';
            $buttons['remove']['attr']['field-target'] = '#recordId';
            $buttons['remove']['attr']['onclick'] = 'ControllerAction.fieldMapping(this)';
            $buttons['remove']['attr']['field-value'] = $id;
        }

        return $buttons;
    }
}
