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
    public function implementedEvents(): array {
        $events = parent::implementedEvents();
        $newEvents = [
            'Model.custom.onUpdateActionButtons' =>  ['callable' => 'onUpdateActionButtons', 'priority' => 10],

        ];

        $events = array_merge($events,$newEvents);
        return $events;
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

        //POCOR-9257: start - checkbox column and bulk delete for webhook logs
        $this->field('_select', [
            'type'             => 'element',
            'element'          => 'Webhook/select_checkbox',
            'tableHeaderClass' => 'checkbox-column',
            'tableColumnClass' => 'checkbox-column',
            'label'            => '',
            'order'            => 0,
        ]);

        $deleteUrl = \Cake\Routing\Router::url([
            'plugin'     => false,
            'controller' => 'Webhook',
            'action'     => 'logsDeleteSelected',
        ]);

        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $toolbarButtonsArray['deleteSelected'] = [
            'type'    => 'element',
            'element' => 'Webhook/delete_selected_button', //POCOR-9257: 'element' key required by ControllerAction template.php:10
            'data'    => [],
            'options' => [],
        ];
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        $this->controller->set('toolbarButtons', $extra['toolbarButtons']);

        $extra['elements']['webhookBulkJs'] = [
            'name'    => 'Webhook/bulk_actions_js',
            'data'    => ['deleteUrl' => $deleteUrl],
            'options' => [],
            'order'   => 10,
        ];
        //POCOR-9257: end
    }

    public function onGetSuccess(EventInterface $event, Entity $entity): string
    {
        return $entity->success == 1 ? 'Success' : 'Failed';
    }

    //POCOR-9257: Fix view URL — ensure pass params are [0 => 'view', 1 => token]
    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {

        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $id = $this->getEncodedKeys($entity);
        if (isset($buttons['view'])) {
            unset($buttons['view']['url']['_method']);
            unset($buttons['view']['url']['_ext']);
            unset($buttons['view']['url']['?']);
            unset($buttons['view']['url']['plugin']);

            $buttons['view']['url']['2'] = $id;
            $buttons['view']['url']['1'] = 'view';

        }
        if (isset($buttons['remove'])) {
            unset($buttons['remove']['url']['_method']);
            unset($buttons['remove']['url']['_ext']);
            unset($buttons['remove']['url']['?']);
            unset($buttons['remove']['url']['plugin']);

            $buttons['remove']['url']['2'] = $id;
            $buttons['remove']['url']['1'] = 'view';

        }

        return $buttons;
    }
}
