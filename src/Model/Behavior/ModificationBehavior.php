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
        if (isset($_SESSION['Auth']) && isset($_SESSION['Auth']['User'])) {
            $userId = $_SESSION['Auth']['User']['id'];
        }
        if (!is_null($userId)) {
            if (!$entity->isNew()) {
                if (in_array('modified_user_id', $columns)) {
                    $entity->modified_user_id = $userId;
                }
            } else {
                if (in_array('created_user_id', $columns)) {
                    $entity->created_user_id = $userId;
                }
            }
        }
    } 
}
