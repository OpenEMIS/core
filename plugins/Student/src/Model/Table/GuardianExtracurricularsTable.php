<?php
namespace Student\Model\Table;
use Cake\ORM\Query;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\ORM\TableRegistry;

class GuardianExtracurricularsTable extends AppTable {
	public function initialize(array $config) {
	
		$this->table('student_extracurriculars');
		parent::initialize($config);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('ExtracurricularTypes', ['className' => 'FieldOption.ExtracurricularTypes']);
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
		$this->ControllerAction->setFieldOrder('position', $order++);
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
		$options['type'] = 'student';
		$tabElements = $this->controller->getAcademicTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function afterAction(Event $event, $data) {
		$this->setupTabElements();
	}

	public function beforeFind( Event $event, Query $query )
	{   
		$session = $this->request->session();
		$userData = $this->Session->read();
		$studentId = $session->read('Student.Students.id');
		if ($this->alias() == 'Extracurriculars') {
			$periodId = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent();
			$conditions[$this->aliasField('academic_period_id')] = $periodId;
			if ($this->controller->name == 'Profiles') {
				if ($this->Session->read('Auth.User.is_guardian') == 1) {
					$sId = $this->Session->read('Student.ExaminationResults.student_id');
					
					if ( is_int($sId) ) {
						$studentId = $sId;
					} else if ($sId == null || empty($sId) || $sId == '') {
						if ($studentId == null || $studentId == '' || empty($studentId)) {
							$studentId = $userData['Auth']['User']['id'];
						} else {
							$studentId = $studentId;
						}
					} else {
					$studentId = $this->ControllerAction->paramsDecode($sId)['id'];
					}
				} else {
					$studentId = $this->Session->read('Auth.User.id');
				}
			} 
			if ($this->controller->name == 'GuardianNavs') {
				$session = $this->request->session();
				$studentId = $session->read('Student.Students.id');
			}
			
			$conditions[$this->aliasField('security_user_id')] = $studentId;
			if ($this->action == 'view' || $this->action == 'edit') {
				$id = $this->ControllerAction->paramsDecode($this->request->params['pass'][1])['id'];
    			$conditions[$this->aliasField('id')] = $id;
				$query->where($conditions, [], true);
			} else {
			    $query->where($conditions, [], true);
			}
		}
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $conditions[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;

        $extra['elements']['controls'] = ['name' => 'Student.Extracurriculars/controls', 'data' => [], 'options' => [], 'order' => 1];
    }
}
