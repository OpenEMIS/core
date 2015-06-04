<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Validation\AppValidator;
use Cake\Utility\Inflector;
use ControllerAction\Model\Traits\ControllerActionTrait;

class AppTable extends Table {
	use ControllerActionTrait;

	public function initialize(array $config) {
		$schema = $this->schema();
		$columns = $schema->columns();

		if (in_array('modified', $columns) || in_array('created', $columns)) {
			$this->addBehavior('Timestamp');
		}

		if (in_array('modified_user_id', $columns)) {
			$this->belongsTo('ModifiedUser', [
				'className' => 'User.Users',
				'fields' => array('ModifiedUser.first_name', 'ModifiedUser.last_name'),
				'foreignKey' => 'modified_user_id'
			]);
		}

		if (in_array('created_user_id', $columns)) {
			$this->belongsTo('CreatedUser', [
				'className' => 'User.Users',
				'fields' => array('CreatedUser.first_name', 'CreatedUser.last_name'),
				'foreignKey' => 'created_user_id'
			]);
		}
	}

	public function onPopulateSelectOptions($event, $query) {
		$schema = $this->schema();
		$columns = $schema->columns();
		
		if ($this->hasBehavior('FieldOption')) {
			$query->innerJoin(
				['FieldOption' => 'field_options'],
				[
					'FieldOption.id = ' . $this->aliasField('field_option_id'),
					'FieldOption.code' => $this->alias()
				]
			)->find('order')->find('visible');
		} else {
			if (in_array('order', $columns)) {
				$query->find('order');
			}

			if (in_array('visible', $columns)) {
				$query->find('visible');
			}
		}
		return $query;
	}

	public function validationDefault(Validator $validator) {
		$validator = new AppValidator();
		$validator->provider('default', $validator);
		return $validator;
	}

	public function getAssociatedBelongsToModel($field) {
		$relatedModel = null;
		foreach ($this->associations() as $assoc) {
			if ($assoc->type() == 'manyToOne') { // belongsTo associations
				if ($field === $assoc->foreignKey()) {
					$relatedModel = $assoc;
					break;
				}
			}
		}
		return $relatedModel;
	}

	public function findVisible(Query $query, array $options) {
		return $query->where([$this->aliasField('visible') => 1]);
	}

	public function findOrder(Query $query, array $options) {
		return $query->order([$this->aliasField('order') => 'ASC']);
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
}
