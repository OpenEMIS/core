<?php

namespace Configuration\Model\Behavior;

use ArrayObject;
use Cake\Log\Log;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface; //POCOR-9509: CakePHP 5 - replaced Cake\Event\Event


class CallWebhookBehavior extends Behavior
{
    protected $_defaultConfig = [
        'entity_create' => '',
        'entity_delete' => '',
        'entity_update' => '',
        'table_alias' => '',
        'contain' => [],
    ];

    public function initialize(array $config): void
    {
        $this->_table->getEventManager()->on('Model.afterFullSave', [$this, 'afterFullSave']);
    }

    public function afterDelete(EventInterface $event, Entity $entity, ArrayObject $options): void //POCOR-9509: CakePHP 5 - Event → EventInterface
    {

        $this->triggerMyWebhook($entity, $this->getConfig('entity_delete'));
    }

    /**
     * Triggers a webhook for user-related changes.
     */
    private function triggerMyWebhook(Entity $entity, string $eventKey): void
    {
        if (empty($eventKey)) {
            return; // Skip if no event key configured
        }

        $Webhooks = TableRegistry::getTableLocator()->get('Configuration.ConfigWebhooks');
        $user = $Webhooks->resolveCurrentUser();

        $contain = $this->getConfig('contain');
        if(!is_array($contain)){
            $contain = [];
        }
        $body = $Webhooks->prepareWebhookBody($this->getConfig('table_alias'), $entity, $contain);

        if ($eventKey === $this->getConfig('entity_delete')) {
            $body['deleted_at'] = date('Y-m-d H:i:s');
            $body['deleted_by'] = $user['openemis_no'] ?? $user['username'] ?? 'system';
        }

        // POCOR-9257: Queue webhook for async processing instead of direct fire
        try {
            $WebhookQueue = TableRegistry::getTableLocator()->get('Alert.WebhookQueue'); //POCOR-9257: moved to Alert plugin
            $result = $WebhookQueue->queueWebhook($eventKey, $body, $user);
            if ($result) {
                // Log::debug("[CallWebhookBehavior] ✓ Queued webhook for event: {$eventKey}, entity ID: {$entity->id}");
            } else {
                Log::warning("[CallWebhookBehavior] Failed to queue webhook for event: {$eventKey}");
            }
        } catch (\Throwable $e) {
            // POCOR-9257: Graceful degradation - queueing failures don't break parent process
            Log::error("[CallWebhookBehavior] Exception while queueing webhook: " . $e->getMessage());
        }
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options): void
    {
        if($this->_table->getAlias() == 'InstitutionClasses'){
            return;
        }
        if($this->_table->getAlias() == 'InstitutionSubjects'){
            return;
        }
        if (!empty($options['skip_callbacks'])) {
            return;
        }

        $eventKey = $entity->isNew()
            ? $this->getConfig('entity_create') // 'security_user_...
            : $this->getConfig('entity_update');

        $this->triggerMyWebhook($entity, $eventKey);

    }
    public function afterFullSave(EventInterface $event, Entity $entity, ArrayObject $options): void //POCOR-9509: CakePHP 5 - Event → EventInterface
    {
        if (!empty($options['skip_callbacks'])) {
            return;
        }

        $eventKey = $entity->isNew()
            ? $this->getConfig('entity_create') // 'security_user_...
            : $this->getConfig('entity_update');

        $this->triggerMyWebhook($entity, $eventKey);

    }


}
