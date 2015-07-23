<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;

class StaffBehavioursTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('StaffBehaviourCategories', ['className' => 'FieldOption.StaffBehaviourCategories']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		$this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
	}

	public function beforeAction() {
		$this->fields['staff_behaviour_category_id']['type'] = 'select';
	}

	public function indexBeforeAction() {
		$this->fields['description']['visible'] = false;
		$this->fields['action']['visible'] = false;
		$this->fields['time_of_behaviour']['visible'] = false;

		$order = 0;
		$this->ControllerAction->setFieldOrder('date_of_behaviour', $order++);
		$this->ControllerAction->setFieldOrder('title', $order++);
		$this->ControllerAction->setFieldOrder('staff_behaviour_category_id', $order++);
		$this->ControllerAction->setFieldOrder('institution_site_id', $order++);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}
}