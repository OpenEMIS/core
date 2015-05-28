<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;

class AppTable extends Table {
	public function initialize(array $config) {
		$this->addBehavior('Timestamp');

		$schema = $this->schema();
		$columns = $schema->columns();

		if (in_array('modified_user_id', $columns)) {
			$this->belongsTo('ModifiedUser', [
				'className' => 'Security.SecurityUsers',
				'fields' => array('ModifiedUser.first_name', 'ModifiedUser.last_name'),
				'foreignKey' => 'modified_user_id'
			]);
		}

		if (in_array('created_user_id', $columns)) {
			$this->belongsTo('CreatedUser', [
				'className' => 'Security.SecurityUsers',
				'fields' => array('CreatedUser.first_name', 'CreatedUser.last_name'),
				'foreignKey' => 'created_user_id'
			]);
		}
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

	/**
	 * Converts the class alias to a label.
	 * 
	 * Usefull for class names or aliases that are more than a word.
	 * Converts the camelized word to a sentence.
	 * If null, this function will used the default alias of the current model.
	 * 
	 * @param  string $camelizedString the camelized string [optional]
	 * @return string                  the converted string
	 */
	public function getHeader($camelizedString = null) {
		if ($camelizedString) {
		    return Inflector::humanize(Inflector::underscore($camelizedString));
		} else {
	        return Inflector::humanize(Inflector::underscore($this->alias()));
		}
	}

	public function getList() {
		// need to fix
		return ['a'];
	}
}
