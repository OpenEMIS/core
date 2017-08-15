<?php
namespace App\Model\Behavior;

use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use ArrayObject;

class ModificationBehavior extends Behavior
{
    public function implementedEvents() {
        $events =  [
            'Model.beforeSave' => ['callable' => 'beforeSave', 'priority' => 5]
        ];
        return $events;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
        $schema = $this->_table->schema();
        $columns = $schema->columns();

        $userId = null;
        if (isset($_SESSION['Auth']) && isset($_SESSION['Auth']['User']['id'])) {
            $userId = $_SESSION['Auth']['User']['id'];
        }
        if (!is_null($userId)) {
            if (!$entity->isNew()) {
                if (in_array('modified_user_id', $columns)) { // if the column exists in the schema
                    if ($entity->has('modified_user_id') && $entity->modified_user_id != $userId
                    || !$entity->has('modified_user_id')) { // if no value or value is different from current user
                        $entity->modified_user_id = $userId; // auto assign with the current user id
                    }
                }
            } else {
                if (in_array('created_user_id', $columns)) { // if the column exists in the schema
                    if (!$entity->has('created_user_id')) { // if the column is not assigned with a value
                        $entity->created_user_id = $userId; // auto assign with the current user id
                    }
                }
            }
        } else { // set default user id to administrator
            if (!$entity->isNew()) {
                if (in_array('modified_user_id', $columns) && !$entity->has('modified_user_id')) {
                    $entity->modified_user_id = 1;
                }
            } else {
                if (in_array('created_user_id', $columns) && !$entity->has('created_user_id')) {
                    $entity->created_user_id = 1;
                }
            }
        }
    }
}
