<?php
namespace App\Model\Behavior;

use Exception;

use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\Log\Log;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Network\Session;


class TrackDeleteBehavior extends Behavior {
/******************************************************************************************************************
**
** Link/Map ControllerActionComponent events
**
******************************************************************************************************************/
	public function implementedEvents() 
    {
		$events = parent::implementedEvents();
		$newEvent = [
			'Model.beforeDelete' => 'beforeDelete'
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}

    public function beforeDelete(Event $event, Entity $entity) 
    {
        try {
            $temp = $this->_table->alias();
            $DeletedRecords = TableRegistry::get('DeletedRecords');
            $entityTable = TableRegistry::get($entity->source());
            $session = new Session();
            $userId = $session->read('Auth.User.id');

            $newEntity = $DeletedRecords->newEntity([
                'reference_table' => $entity->source(),
                'reference_key' => $entity->{$entityTable->primaryKey()},
                'data' => json_encode($entity->toArray()),
                'created_user_id' => $userId,
                'created' => new Time('NOW')
            ]);
            $DeletedRecords->save($newEntity);
        } catch (Exception $e) {
            Log::write('error', __METHOD__ . ': ' . $e->getMessage());
        }
    }
	
}
