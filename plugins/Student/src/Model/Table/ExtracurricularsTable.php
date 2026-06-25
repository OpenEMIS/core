<?php
namespace Student\Model\Table;
use Cake\ORM\Query;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\EventInterface;
use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\ORM\TableRegistry;

class ExtracurricularsTable extends ControllerActionTable {
	public function initialize(array $config): void {

		$this->setTable('student_extracurriculars');
		parent::initialize($config);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('ExtracurricularTypes', ['className' => 'FieldOption.ExtracurricularTypes']);
		//POCOR-6673 start
		$this->toggle('view', false);
        $this->toggle('edit', false);
        $this->toggle('search', true);
        $this->toggle('add', false);
        $this->toggle('remove', false);
        //POCOR-6673 end
	}

	public function beforeAction() {

		$this->fields['academic_period_id']['type'] = 'select';
		$this->fields['extracurricular_type_id']['type'] = 'select';

		if (strtolower($this->action) != 'index') {
			$this->Navigation->addCrumb($this->getHeader($this->action));
		}
	}

	public function indexBeforeAction(EventInterface $event) {

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

	public function addEditBeforeAction(EventInterface $event) {
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

	public function validationDefault(Validator $validator): Validator {
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
		$this->controller->set('selectedAction', $this->getAlias());
	}

	public function afterAction(EventInterface $event, $data) {
		$this->setupTabElements();
	}

	//POCOR-8795  refactored code
	/*POCOR-6474 - commenting function because this function was enabling users to edit and view correct record*/
	// public function beforeFind( EventInterface $event, Query $query )
	// {
	// 	//if ($this->controller->getName() == 'Profiles' && $this->request->query['type'] == 'student') {
	// 	$session = $this->request->getSession();
	// 	$userData = $this->Session->read(); //# [POCOR-6548] Check if user data not found then add current login user data
	// 	$studentId = $session->read('Student.Students.id');
	// 	if ($this->getAlias() == 'Extracurriculars') {
	// 		/*POCOR-6700 starts - added academic period condition into index query*/
	// 		$periodId = !is_null($this->request->getQuery('academic_period_id')) ? $this->request->getQuery('academic_period_id') : $this->AcademicPeriods->getCurrent();
	// 		$conditions[$this->aliasField('academic_period_id')] = $periodId;
	// 		/*POCOR-6700 ends*/
	// 		if ($this->controller->getName() == 'Profiles') {
	// 			if ($this->Session->read('Auth.User.is_guardian') == 1) {
	// 				$sId = $this->Session->read('Student.ExaminationResults.student_id');
	// 				/**
	// 				 * Need to add current login id as param when no data found in existing variable
	// 				 * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
	// 				 * @ticket POCOR-6548
	// 				 */
	// 				//# START: [POCOR-6548] Check if user data not found then add current login user data
	// 				if ( is_int($sId) ) {
	// 					$studentId = $sId;
	// 				} else if ($sId == null || empty($sId) || $sId == '') {
	// 					if ($studentId == null || $studentId == '' || empty($studentId)) {
	// 						$studentId = $userData['Auth']['User']['id'];
	// 					} else {
	// 						$studentId = $studentId;
	// 					}
	// 				} else {
	// 				$studentId = $this->ControllerAction->paramsDecode($sId)['id'];
	// 				}
	// 				//# END: [POCOR-6548] Check if user data not found then add current login user data
	// 			} else {
	// 				$studentId = $this->Session->read('Auth.User.id');
	// 			}
	// 		}
	// 		/*POCOR-6267 starts*/
	// 		if ($this->controller->getName() == 'GuardianNavs') {
	// 			$session = $this->request->getSession();//POCOR-6267
	// 			$studentId = $session->read('Student.Students.id');
	// 		}
	// 		/*POCOR-6267 ends*/
	// 		$conditions[$this->aliasField('security_user_id')] = $studentId;
	// 		/*POCOR-6474 starts*/
	// 		if ($this->action == 'view' || $this->action == 'edit') {
	// 			$id = $this->ControllerAction->paramsDecode($this->request->getAttribute('params')['pass'][1])['id'];
    // 			$conditions[$this->aliasField('id')] = $id;
	// 			$query->where($conditions, [], true);
	// 		} else {
	// 		    $query->where($conditions, [], true);
	// 		}
	// 		/*POCOR-6474 ends*/
	// 	}
	// }

	public function beforeFind(EventInterface $event, Query $query, \ArrayObject $options, $primary)
	{
		if ($this->getAlias() == 'Extracurriculars') {
			$conditions = [];

			$academicPeriodId = !empty($options['academic_period_id']) ? $options['academic_period_id'] : $this->AcademicPeriods->getCurrent();
			$conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;

			if (!empty($options['student_id'])) {
				$conditions[$this->aliasField('security_user_id')] = $options['student_id'];
			}

			if (in_array($this->action, ['view', 'edit']) && !empty($options['id'])) {
				$conditions[$this->aliasField('id')] = $options['id'];
			}

			$query->where($conditions, [], true);
		}
	}
    //POCOR-8795 end
	/**
     * Added academic period filter into extracurricular index page
     * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
     * @return array
     * @ticket POCOR-6700
    */
	public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
    	//academic period filter
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->getQuery('academic_period_id')) ? $this->request->getQuery('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $conditions[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        //end

        $extra['elements']['controls'] = ['name' => 'Student.Extracurriculars/controls', 'data' => [], 'options' => [], 'order' => 1];

		// Start POCOR-5188
		if($this->request->getParam('controller') == 'Students'){
			$is_manual_exist = $this->getManualUrl('Institutions','Extracurricular','Students - Academic');
			if(!empty($is_manual_exist)){
				$btnAttr = [
					'class' => 'btn btn-xs btn-default icon-big',
					'data-toggle' => 'tooltip',
					'data-placement' => 'bottom',
					'escape' => false,
					'target'=>'_blank'
				];
				$helpBtn['url'] = $is_manual_exist['url'];
				$helpBtn['type'] = 'button';
				$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
				$helpBtn['attr'] = $btnAttr;
				$helpBtn['attr']['title'] = __('Help');
				$extra['toolbarButtons']['help'] = $helpBtn;
			}
		}elseif($this->request->getParam('controller') == 'Directories'){
			$is_manual_exist = $this->getManualUrl('Directory','Extracurriculars','Students - Academic');
			if(!empty($is_manual_exist)){
				$btnAttr = [
					'class' => 'btn btn-xs btn-default icon-big',
					'data-toggle' => 'tooltip',
					'data-placement' => 'bottom',
					'escape' => false,
					'target'=>'_blank'
				];

				$helpBtn['url'] = $is_manual_exist['url'];
				$helpBtn['type'] = 'button';
				$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
				$helpBtn['attr'] = $btnAttr;
				$helpBtn['attr']['title'] = __('Help');
				$extra['toolbarButtons']['help'] = $helpBtn;
			}

		}
		// End POCOR-5188
    }
}
