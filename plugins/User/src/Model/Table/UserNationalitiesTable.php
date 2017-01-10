<?php
namespace User\Model\Table;

use ArrayObject;

use Cake\Validation\Validator;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class UserNationalitiesTable extends ControllerActionTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('NationalitiesLookUp', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index', 'add']
        ]);
	}

	public function beforeAction(Event $event) {
		$this->fields['nationality_id']['type'] = 'select';
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator->add('nationality_id', 'notBlank', ['rule' => 'notBlank']);
	}

	public function validationNonMandatory(Validator $validator) {
		$validator = $this->validationDefault($validator);
		return $validator->allowEmpty('nationality_id');
	}

	public function validationAddByAssociation(Validator $validator)
	{
		$validator = $this->validationDefault($validator);
		return $validator->requirePresence('security_user_id', false);
	}

	private function setupTabElements() {
		$options = [
			'userRole' => '',
		];

		switch ($this->controller->name) {
			case 'Students':
				$options['userRole'] = 'Students';
				break;
			case 'Staff':
				$options['userRole'] = 'Staff';
				break;
		}

		$tabElements = $this->controller->getUserTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function afterAction(Event $event) {
		$this->setupTabElements();
	}

}
