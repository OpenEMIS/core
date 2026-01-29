<?php

namespace Configuration\Model\Behavior;

use ArrayObject;
use Cake\Log\Log;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Event\EventInterface;


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

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options): void
    {

        $this->triggerMyWebhook($entity, $this->getConfig('entity_delete'));
    }

    /**
     * Triggers a webhook for user-related changes.
     */
    private function triggerMyWebhook(Entity $entity, string $eventKey): void
    {
        $Webhooks = TableRegistry::getTableLocator()->get('Configuration.ConfigWebhooks');

        if (empty($user)) {
            $user = $Webhooks->resolveCurrentUser();
        }
//        Log::debug(print_r($entity, true));
        $contain = $this->getConfig('contain');
        if(!is_array($contain)){
            $contain = [];
        }
        $body = $Webhooks->prepareWebhookBody($this->getConfig('table_alias'), $entity, $contain);
//        Log::debug(print_r(['body' => $body], true));

        if ($eventKey === $this->getConfig('entity_delete')) {
            $body['deleted_at'] = date('Y-m-d H:i:s');
            $body['deleted_by'] = $user['openemis_no'] ?? $user['username'] ?? 'system';
        }

        try {
            $Webhooks->triggerCommand($eventKey, $body);
        } catch (\Throwable $e) {
            Log::warning("Webhook trigger failed in afterSave: " . $e->getMessage());
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
    public function afterFullSave(Event $event, Entity $entity, ArrayObject $options): void
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
