<?php
namespace CustomField\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;

class CustomTableRowsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'CustomField.CustomFields']);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->requirePresence('name')
			->notEmpty('name', 'Please enter a name.');

		return $validator;
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		if (!$entity->isNew()) {
			// Always mark visible to dirty to handle retain Table Rows when update all visible to 0
			$entity->dirty('visible', true);
		}
	}
}
