<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use App\Model\Table\AppTable;

class StaffTrainingsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('StaffTrainingCategories', ['className' => 'FieldOption.StaffTrainingCategories']);
	}

	public function beforeAction() {
		$userId = $this->Session->read('Staff.Staff.id');
		$this->ControllerAction->field('staff_id', ['type' => 'hidden', 'value' => $userId]);
		$this->ControllerAction->field('staff_training_category_id', ['type' => 'select']);
		$this->ControllerAction->field('completed_date', ['default_date' => true]);
	}

	private function setupTabElements() {
		$tabElements = $this->controller->getProfessionalDevelopmentTabElements();
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', 'Trainings');
	}

	public function afterAction(Event $event) {
		$this->setupTabElements();
	}
}
