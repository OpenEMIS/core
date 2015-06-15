<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;

class StudentBehavioursTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('StudentBehaviourCategories', ['className' => 'FieldOption.StudentBehaviourCategories']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
	}

	public function beforeAction() {
		$this->fields['student_behaviour_category_id']['type'] = 'select';
	}	

	public function indexBeforeAction(Event $event) {
		$this->fields['description']['visible'] = false;
		$this->fields['action']['visible'] = false;
		$this->fields['time_of_behaviour']['visible'] = false;

		$order = 0;
		$this->ControllerAction->setFieldOrder('date_of_behaviour', $order++);
		$this->ControllerAction->setFieldOrder('title', $order++);
		$this->ControllerAction->setFieldOrder('student_behaviour_category_id', $order++);
		$this->ControllerAction->setFieldOrder('institution_site_id', $order++);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}
}