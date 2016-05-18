<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;

class SpecialNeedsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('user_special_needs');
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('SpecialNeedTypes', ['className' => 'FieldOption.SpecialNeedTypes']);
		$this->belongsTo('SpecialNeedDifficulties', ['className' => 'FieldOption.SpecialNeedDifficulties']);
	}

	public function beforeAction($event) {
		$this->fields['special_need_type_id']['type'] = 'select';
		$this->fields['special_need_difficulty_id']['type'] = 'select';
	}

	public function indexBeforeAction(Event $event) {
		$order = 0;
		$this->ControllerAction->setFieldOrder('special_need_date', $order++);
		$this->ControllerAction->setFieldOrder('special_need_type_id', $order++);
		$this->ControllerAction->setFieldOrder('special_need_difficulty_id', $order++);
		$this->ControllerAction->setFieldOrder('comment', $order++);
	}

	public function addEditBeforeAction(Event $event) {
		$order = 0;
		$this->ControllerAction->setFieldOrder('special_need_type_id', $order++);
		$this->ControllerAction->setFieldOrder('special_need_date', $order++);
		$this->ControllerAction->setFieldOrder('special_need_difficulty_id', $order++);
		$this->ControllerAction->setFieldOrder('comment', $order++);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		return $validator
			->allowEmpty('special_need_date')
		;
	}

	public function validationNonMandatory(Validator $validator) {
		$this->validationDefault($validator);
		return $validator->allowEmpty('comment');
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
		if ($this->controller->name == 'Directories') {
			$type = $this->request->query('type');
			$options['type'] = $type;
			$tabElements = $this->controller->getUserTabElements($options);
		} else {
			$tabElements = $this->controller->getUserTabElements($options);
		}
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function afterAction(Event $event, $data) {
		$this->setupTabElements();
	}
}
