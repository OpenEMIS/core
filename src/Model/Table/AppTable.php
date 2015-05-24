<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;

class AppTable extends Table {
	public function initialize(array $config) {
		$this->addBehavior('Timestamp');
	}
	
	public function beforeSave(Event $event, Entity $entity) {
		$schema = $this->schema();
		$columns = $schema->columns();

		if (in_array('modified_user_id', $columns)) {
			$entity->modified_user_id = 1;
		}
		if (in_array('created_user_id', $columns)) {
			$entity->created_user_id = 1;
		}
	}
}
