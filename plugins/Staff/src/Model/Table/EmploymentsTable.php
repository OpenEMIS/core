<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class EmploymentsTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->table('staff_employments');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('EmploymentTypes', ['className' => 'FieldOption.EmploymentTypes']);

		$this->behaviors()->get('ControllerAction')->config('actions.search', false);
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('employment_type_id', ['type' => 'select', 'before' => 'employment_date']);
		$this->setupTabElements();
	}

	private function setupTabElements() {
		$options['type'] = 'staff';
		$tabElements = $this->controller->getCareerTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}
}
