<?php
namespace Security\Model\Behavior;

use ArrayObject;
use Exception;
use Cake\Log\Log;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

// POCOR-8683
// POCOR-8683

class UserWebhookBehavior extends Behavior {
	public function initialize(array $config): void {

	}

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options): void
    {

        $this->triggerSecurityUserWebhook($entity, 'security_user_delete');
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options): void
    {
        if (!empty($options['skip_callbacks'])) {
            return;
        }

        $eventKey = $entity->isNew()
            ? 'security_user_create'
            : 'security_user_update';

        $this->triggerSecurityUserWebhook($entity, $eventKey);

    }


    /**
     * Triggers a webhook for user-related changes.
     */
    private function triggerSecurityUserWebhook(Entity $entity, string $eventKey): void
    {
        $Webhooks = TableRegistry::getTableLocator()->get('Configuration.ConfigWebhooks');

        if(empty($user)){
            $user = $Webhooks->resolveCurrentUser();
        }
//        Log::debug(print_r($entity, true));
        $body = $Webhooks->prepareWebhookBody('User.Users', $entity, []);
        Log::debug(print_r(['body' => $body], true));

        if ($eventKey === 'security_user_delete') {
            $body['deleted_at'] = date('Y-m-d H:i:s');
            $body['deleted_by'] = $user['openemis_no'] ?? $user['username'] ?? 'system';
        }

        try {
            $Webhooks->triggerCommand($eventKey, $body);
        } catch (\Throwable $e) {
            Log::warning("Webhook trigger failed in afterSave: " . $e->getMessage());
        }
    }


}
