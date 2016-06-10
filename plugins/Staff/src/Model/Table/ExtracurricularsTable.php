<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;

class ExtracurricularsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('staff_extracurriculars');
		parent::initialize($config);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('ExtracurricularTypes', ['className' => 'FieldOption.ExtracurricularTypes']);
	}

	public function beforeAction() {
		$this->fields['academic_period_id']['type'] = 'select';
		$this->fields['extracurricular_type_id']['type'] = 'select';
	}

	public function indexBeforeAction(Event $event) {
		$this->fields['end_date']['visible'] = false;
		$this->fields['hours']['visible'] = false;
		$this->fields['points']['visible'] = false;
		$this->fields['location']['visible'] = false;
		$this->fields['comment']['visible'] = false;

		$order = 0;
		$this->ControllerAction->setFieldOrder('academic_period_id', $order++);
		$this->ControllerAction->setFieldOrder('start_date', $order++);
		$this->ControllerAction->setFieldOrder('extracurricular_type_id', $order++);
		$this->ControllerAction->setFieldOrder('name', $order++);
	}

	public function addEditBeforeAction(Event $event) {
		$order = 0;
		$this->ControllerAction->setFieldOrder('academic_period_id', $order++);
		$this->ControllerAction->setFieldOrder('extracurricular_type_id', $order++);
		$this->ControllerAction->setFieldOrder('name', $order++);
		$this->ControllerAction->setFieldOrder('start_date', $order++);
		$this->ControllerAction->setFieldOrder('end_date', $order++);
		$this->ControllerAction->setFieldOrder('hours', $order++);
		$this->ControllerAction->setFieldOrder('points', $order++);
		$this->ControllerAction->setFieldOrder('location', $order++);
		$this->ControllerAction->setFieldOrder('comment', $order++);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		return $validator
			->add('start_date', 'ruleCompareDate', [
				'rule' => ['compareDate', 'end_date', false]
			])
		;
	}
	private function setupTabElements() {
		$tabElements = $this->controller->getProfessionalDevelopmentTabElements();
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function afterAction(Event $event, $data) {
		$this->setupTabElements();
	}
}
