<?php
// POCOR-9257: Async webhook queue behavior - moved to Alert plugin
declare(strict_types=1);

namespace Alert\Model\Behavior; //POCOR-9257: moved from App\Model\Behavior

use ArrayObject;
use Cake\Log\Log;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;

/**
 * WebhookQueueBehavior
 *
 * Replaces CallWebhookBehavior (POCOR-9403) with async queue processing.
 * Queues webhook requests to webhook_queue table instead of blocking exec() calls.
 *
 * Usage:
 * $this->addBehavior('Alert.WebhookQueue', [
 *     'entity_create' => 'student_create',
 *     'entity_update' => 'student_update',
 *     'entity_delete' => 'student_delete',
 *     'table_alias' => 'Institution.Students',
 *     'contain' => []
 * ]);
 */
class WebhookQueueBehavior extends Behavior
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
        // Listen to Model.afterFullSave for comprehensive save operations
        $this->_table->getEventManager()->on('Model.afterFullSave', [$this, 'afterFullSave']);
    }

    /**
     * Triggered after entity delete
     */
    public function afterDelete(EventInterface $event, Entity $entity, ArrayObject $options): void
    {
        $this->queueWebhook($entity, $this->getConfig('entity_delete'));
    }

    /**
     * Triggered after entity save (create/update)
     */
    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options): void
    {
        // Skip for specific tables (legacy compatibility)
        if ($this->_table->getAlias() === 'InstitutionClasses' ||
            $this->_table->getAlias() === 'InstitutionSubjects') {
            return;
        }

        if (!empty($options['skip_callbacks'])) {
            return;
        }

        $eventKey = $entity->isNew()
            ? $this->getConfig('entity_create')
            : $this->getConfig('entity_update');

        $this->queueWebhook($entity, $eventKey);
    }

    /**
     * Triggered after full save operation
     */
    public function afterFullSave(EventInterface $event, Entity $entity, ArrayObject $options): void
    {
        if (!empty($options['skip_callbacks'])) {
            return;
        }

        $eventKey = $entity->isNew()
            ? $this->getConfig('entity_create')
            : $this->getConfig('entity_update');

        $this->queueWebhook($entity, $eventKey);
    }

    /**
     * Queue webhook request to webhook_queue table
     *
     * @param Entity $entity The entity that triggered the webhook
     * @param string $eventKey The webhook event key (e.g., 'student_create')
     */
    private function queueWebhook(Entity $entity, string $eventKey): void
    {
        if (empty($eventKey)) {
            return;
        }

        try {
            $WebhookQueue = TableRegistry::getTableLocator()->get('Alert.WebhookQueue'); //POCOR-9257: use plugin-prefixed alias
            $ConfigWebhooks = TableRegistry::getTableLocator()->get('Configuration.ConfigWebhooks');

            // Resolve current user for audit trail
            $user = $ConfigWebhooks->resolveCurrentUser();

            // Prepare entity data with associations
            $contain = $this->getConfig('contain');
            if (!is_array($contain)) {
                $contain = [];
            }
            $body = $ConfigWebhooks->prepareWebhookBody(
                $this->getConfig('table_alias'),
                $entity,
                $contain
            );

            // Add delete metadata if this is a delete event
            if ($eventKey === $this->getConfig('entity_delete')) {
                $body['deleted_at'] = date('Y-m-d H:i:s');
                $body['deleted_by'] = $user['openemis_no'] ?? $user['username'] ?? 'system';
            }

            // Queue the webhook (non-blocking)
            $result = $WebhookQueue->queueWebhook($eventKey, $body, $user);

            if (!$result) {
                Log::error("[WebhookQueue] Failed to queue webhook for event: $eventKey");
            }

        } catch (\Throwable $e) {
            // POCOR-9257: Graceful degradation - queueing failures don't break parent process
            Log::error("[WebhookQueue] Exception while queueing webhook: " . $e->getMessage());
        }
    }
}
