<?php
namespace Rubric\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;

class RubricCriteriaOptionsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('RubricTemplateOptions', ['className' => 'Rubric.RubricTemplateOptions']);
		$this->belongsTo('RubricCriterias', ['className' => 'Rubric.RubricCriterias']);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		$validator
			->requirePresence('name')
			->notEmpty('name', 'Please enter a name.');

		return $validator;
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		//Unset rubric_template_option to avoid it is being saved unintentionally.
		unset($entity->rubric_template_option);
	}
}
