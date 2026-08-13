<?php
namespace User\Model\Behavior;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\I18n\Time;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

/**
 * PasswordRotationBehavior
 *
 * This behavior maintains a history of user passwords whenever a password is
 * updated. Before a password change is saved, the existing hashed password is
 * captured and, after a successful save, stored in the
 * `security_user_passwords` table.
 *
 * The behavior is executed only when the `password_rotation` configuration
 * setting is enabled.
 *
 * Features:
 * - Records the previous hashed password before it is updated.
 * - Saves password history after a successful password change.
 * - Prevents password history from being created for new users.
 * - Resolves the user performing the update using the authenticated user,
 *   `modified_user_id`, or `created_user_id`.
 *
 * Events Implemented:
 * - Model.beforeSave
 * - Model.afterSave
 *
 * @package User\Model\Behavior
 * @author Ehteram Ahmad
 */
class PasswordRotationBehavior extends Behavior
{
    private ?array $passwordHistoryData = null;

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Model.beforeSave'] = 'beforeSave';
        $events['Model.afterSave'] = 'afterSave';

        return $events;
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options): void
    {
        $this->passwordHistoryData = null;

        if (!$this->isPasswordRotationEnabled()) {
            return;
        }

        if (empty($entity->id) || !$entity->isDirty('password') || $entity->password === '' || $entity->password === null) {
            return;
        }

        $originalPassword = $entity->getOriginal('password');
        if (empty($originalPassword)) {
            return;
        }

        $this->passwordHistoryData = [
            'security_user_id' => $entity->id,
            'old_password' => $originalPassword,
            'created_user_id' => $this->resolveCreatedUserId($entity),
            'created' => Time::now(),
        ];
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options): void
    {
        if (empty($this->passwordHistoryData)) {
            return;
        }

        $SecurityUserPasswords = TableRegistry::getTableLocator()->get('User.SecurityUserPasswords');
        $historyEntity = $SecurityUserPasswords->newEntity($this->passwordHistoryData);
        $SecurityUserPasswords->save($historyEntity);

        $this->passwordHistoryData = null;
    }

    private function isPasswordRotationEnabled(): bool
    {
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');

        return (bool)$ConfigItems->value('password_rotation');
    }

    private function resolveCreatedUserId(Entity $entity): int
    {
        $table = $this->_table;
        if (isset($table->Auth) && $table->Auth->user('id')) {
            return (int)$table->Auth->user('id');
        }

        if ($entity->has('modified_user_id') && !empty($entity->modified_user_id)) {
            return (int)$entity->modified_user_id;
        }

        if ($entity->has('created_user_id') && !empty($entity->created_user_id)) {
            return (int)$entity->created_user_id;
        }

        return 1;
    }
}
