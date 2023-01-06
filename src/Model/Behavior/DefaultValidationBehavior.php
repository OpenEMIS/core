<?php
namespace App\Model\Behavior;

use DateTime;
use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\ORM\Behavior;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\Network\Session;
use App\Model\Traits\MessagesTrait;

class DefaultValidationBehavior extends Behavior {
	private $importValidationFailed = NULL;

	public function buildValidator(Event $event, Validator $validator, $name) {

		$this->_attachDefaultValidation($validator);
	}

	public function beforeSave()
	{
		if ($this->importValidationFailed) {
			return false;
		}
	}

	public function setImportValidationFailed()
	{
		$this->importValidationFailed = true;
	}
        
        public function setImportValidationPassed()
	{
		$this->importValidationFailed = false;
	}

	private function _attachDefaultValidation($validator) {
		$schema = $this->_table->schema();
		$columns = $schema->columns();

		// added this temporary, will need to revisit this code
		$ignoreFields = ['modified_user_id', 'created_user_id', 'modified', 'created', 'order'];

		foreach ($columns as $col) {
			$columnInfo = $schema->column($col);
			if ($validator->hasField($col)) {
				$set = $validator->field($col);

				if (!$set->isEmptyAllowed()) {
					$set->add('notBlank', ['rule' => 'notBlank']);
				}
				if (!$set->isPresenceRequired()) {
					if ($this->_isForeignKey($col)) {
						$validator->requirePresence($col);
					}
				}
			} else { // field not presence in validator
				if (array_key_exists('null', $columnInfo)) {
					if ($columnInfo['null'] === false && $col !== 'id' && !in_array($col, $ignoreFields)) {
						$validator->add($col, 'notBlank', ['rule' => 'notBlank']);
						if ($this->_isForeignKey($col)) {
							$validator->requirePresence($col);
						}
					}
				}
			}
		}
	}

	private function _isForeignKey($field) {
		$model = $this->_table;
		foreach ($model->associations() as $assoc) {
			if ($assoc->type() == 'manyToOne') { // belongsTo associations
				if ($field === $assoc->foreignKey()) {
					return true;
				}
			}
		}
		return false;
	}
}
