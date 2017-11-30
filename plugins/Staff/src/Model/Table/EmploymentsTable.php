<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class EmploymentsTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->table('staff_employments');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
        $this->setupTabElements();
	}

	private function setupTabElements() {
		$options['type'] = 'staff';
		$tabElements = $this->controller->getProfessionalTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}
}
