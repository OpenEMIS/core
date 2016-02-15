<?php
namespace User\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;

class SpecialNeedsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('user_special_needs');
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('SpecialNeedTypes', ['className' => 'FieldOption.SpecialNeedTypes']);
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [];
		$newEvent['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
		$events = array_merge($events, $newEvent);
		return $events;
	}

	public function beforeAction($event) {
		$this->fields['special_need_type_id']['type'] = 'select';
	}

	public function indexBeforeAction(Event $event) {
		$order = 0;
		$this->ControllerAction->setFieldOrder('special_need_date', $order++);
		$this->ControllerAction->setFieldOrder('special_need_type_id', $order++);
		$this->ControllerAction->setFieldOrder('comment', $order++);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		return $validator
			->allowEmpty('special_need_date')
			->add('comment', [])
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

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		$toolbarArray = $toolbarButtons->getArrayCopy();
		unset($toolbarArray['search']);
		$toolbarButtons->exchangeArray($toolbarArray);
	}
}
