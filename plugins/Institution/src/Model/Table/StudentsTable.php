<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Security\Model\Table\SecurityUserTypesTable as UserTypes;

class StudentsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_students');
		parent::initialize($config);

		$this->belongsTo('Users',			['className' => 'Security.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('StudentStatuses',	['className' => 'Student.StudentStatuses']);
		$this->belongsTo('EducationGrades',	['className' => 'Education.EducationGrades']);
		$this->belongsTo('Institutions',	['className' => 'Institution.InstitutionSites', 'foreignKey' => 'institution_id']);
		$this->belongsTo('AcademicPeriods',	['className' => 'AcademicPeriod.AcademicPeriods']);

		$this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
		$this->addBehavior('AcademicPeriod.Period');
		// to handle field type (autocomplete)
		$this->addBehavior('OpenEmis.autocomplete');
		$this->addBehavior('User.User');
		$this->addBehavior('User.AdvancedNameSearch');

		// $this->addBehavior('Student.Student');
		// $this->addBehavior('User.Mandatory', ['userRole' => 'Student', 'roleFields' =>['Identities', 'Nationalities', 'Contacts', 'SpecialNeeds']]);
		// $this->addBehavior('Institution.User', ['associatedModel' => $this->InstitutionSiteStudents]);
		// $this->addBehavior('AdvanceSearch');
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

	public function beforeAction(Event $event) {
		$institutionId = $this->Session->read('Institutions.id');
		$this->ControllerAction->field('institution_id', ['type' => 'hidden', 'value' => $institutionId]);
		$this->ControllerAction->field('student_status_id', ['type' => 'select']);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		// Student Statuses
		$statusOptions = $this->StudentStatuses
			->find('list')
			->toArray();
		$selectedStatus = $this->queryString('status_id', $statusOptions);
		$this->advancedSelectOptions($statusOptions, $selectedStatus);

		$query->where([$this->aliasField('student_status_id') => $selectedStatus]);

		$search = $this->ControllerAction->getSearchKey();
		if (!empty($search)) {
			// function from AdvancedNameSearchBehavior
			$query = $this->addSearchConditions($query, ['searchTerm' => $search]);
		}

		if (!empty($statusOptions)) {
			$toolbarElements = [
				['name' => 'Institution.Students/controls', 'data' => [], 'options' => []]
			];
			$this->controller->set('toolbarElements', $toolbarElements);
			$this->controller->set('statusOptions', $statusOptions);
		}
		// End
	}

	public function addAfterPatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$dataArray = $data->getArrayCopy();

		// removing student_id on fail
		if (!empty($dataArray) && array_key_exists($this->alias(), $dataArray) && array_key_exists('student_id', $dataArray[$this->alias])) {
			unset($data[$this->alias()]['student_id']);
		}
	}

	// public function onGetStudentId(Event $event, Entity $entity) {
	// 	pr($entity);
	// }

	public function addAfterAction(Event $event) {
		list($periodOptions, $selectedPeriod, $gradeOptions, $selectedGrade, $sectionOptions, $selectedSection) = array_values($this->_getSelectOptions());

		$this->ControllerAction->field('academic_period_id', ['options' => $periodOptions]);
		$this->ControllerAction->field('education_grade_id', ['options' => $gradeOptions]);
		$this->ControllerAction->field('class', ['options' => $sectionOptions]);

		$period = $this->AcademicPeriods->get($selectedPeriod);

		$this->ControllerAction->field('id', ['value' => Text::uuid()]);
		$this->ControllerAction->field('start_date', ['period' => $period]);
		$this->ControllerAction->field('end_date', ['period' => $period]);
		$this->ControllerAction->field('student_id');

		$this->ControllerAction->setFieldOrder([
			'academic_period_id', 'education_grade_id', 'class', 'student_status_id', 'start_date', 'end_date', 'student_id'
		]);
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
		$attr['onChangeReload'] = 'changePeriod';
		return $attr;
	}

	public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request) {
		$attr['onChangeReload'] = 'changeEducationGrade';
		return $attr;
	}

	public function onUpdateFieldStartDate(Event $event, array $attr, $action, Request $request) {
		$period = $attr['period'];
		$endDate = $period->end_date->copy();
		$attr['date_options']['startDate'] = $period->start_date->format('d-m-Y');
		$attr['date_options']['endDate'] = $endDate->subDay()->format('d-m-Y');
		$attr['default_date'] = false;
		return $attr;
	}

	public function onUpdateFieldEndDate(Event $event, array $attr, $action, Request $request) {
		$period = $attr['period'];
		$attr['type'] = 'readonly';
		$attr['attr'] = ['value' => $period->end_date->format('d-m-Y')];
		$attr['value'] = $period->end_date->format('Y-m-d');
		return $attr;
	}

	public function onUpdateFieldStudentId(Event $event, array $attr, $action, Request $request) {
		$attr['type'] = 'autocomplete';
		$attr['target'] = ['key' => 'student_id', 'name' => $this->aliasField('student_id')];
		$attr['noResults'] = $this->getMessage($this->aliasField('noStudents'));
		$attr['attr'] = ['placeholder' => __('OpenEMIS ID or Name')];
		$attr['url'] = ['controller' => 'Institutions', 'action' => 'Students', 'ajaxUserAutocomplete'];
		return $attr;
	}

	public function addOnChangePeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['period']);
		unset($request->query['grade']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
					$request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
				}
			}
		}
	}

	public function addOnChangeEducationGrade(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['period']);
		unset($request->query['grade']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
					$request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
				}
				if (array_key_exists('education_grade_id', $request->data[$this->alias()])) {
					$request->query['grade'] = $request->data[$this->alias()]['education_grade_id'];
				}
			}
		}
	}

	public function ajaxUserAutocomplete() {
		$this->controller->autoRender = false;
		$this->ControllerAction->autoRender = false;

		if ($this->request->is(['ajax'])) {
			$term = $this->request->query['term'];
			$UserTypes = $this->Users->UserTypes;
			$query = $UserTypes->find()->contain(['Users']);

			$term = trim($term);
			if (!empty($term)) {
				$query = $this->addSearchConditions($query, ['searchTerm' => $term]);
			}
			
			// only search for students
			$query->where([$UserTypes->aliasField('user_type') => UserTypes::STUDENT]);
			$list = $query->all();

			$data = array();
			foreach($list as $obj) {
				$data[] = [
					'label' => sprintf('%s - %s', $obj->user->openemis_no, $obj->user->name),
					'value' => $obj->user->id
				];
			}

			echo json_encode($data);
			die;
		}
	}

	public function validationDefault(Validator $validator) {
		return $validator
			->add('start_date', 'ruleCompareDate', [
				'rule' => ['compareDate', 'end_date', false]
			])
			->add('end_date', [
			])
			->add('student_status_id', [
			])
			->add('academic_period_id', [
			])
			// ->allowEmpty('student_id') required for create new but disabling for now
			->add('student_id', 'ruleInstitutionStudentId', [
				'rule' => ['institutionStudentId'],
			])
		;
	}

	public function _getSelectOptions() {
		//Return all required options and their key
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$Grades = TableRegistry::get('Institution.InstitutionSiteGrades');
		$InstitutionSiteSections = TableRegistry::get('Institution.InstitutionSiteSections');
		$institutionId = $this->Session->read('Institutions.id');

		// Academic Period
		$periodOptions = $AcademicPeriod->getList();

		$selectedPeriod = !is_null($this->request->query('period')) ? $this->request->query('period') : key($periodOptions);
		// $this->advancedSelectOptions($periodOptions, $selectedPeriod, []);
		$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noGrades')),
			'callable' => function($id) use ($Grades, $institutionId) {
				return $Grades
					->find()
					->where([$Grades->aliasField('institution_site_id') => $institutionId])
					->find('academicPeriod', ['academic_period_id' => $id])
					->count();
			}
		]);
		// End


		// Grade
		$data = $Grades->find()
		->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
		->contain('EducationGrades.EducationProgrammes')
		->where([$Grades->aliasField('institution_site_id') => $institutionId])
		->all();

		$gradeOptions = [];
		foreach ($data as $entity) {
			$gradeOptions[$entity->education_grade->id] = $entity->education_grade->programme_grade_name;
		}

		$selectedGrade = !is_null($this->request->query('grade')) ? $this->request->query('grade') : key($gradeOptions);
		$this->advancedSelectOptions($gradeOptions, $selectedGrade, []);
		// End
		
		// section
		$Students = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
		$sectionOptions = $InstitutionSiteSections->getSectionOptions($selectedPeriod, $institutionId, $selectedGrade);
		// $selectedSection = !is_null($this->request->query('student')) ? $this->request->query('student') : key($studentOptions);// not needed
		// End

		return compact('periodOptions', 'selectedPeriod', 'gradeOptions', 'selectedGrade', 'sectionOptions', 'selectedSection');
	}


    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
    	if ($action == 'view') {
    		if ($this->AccessControl->check([$this->controller->name, 'TransferRequests', 'add'])) {
				$TransferRequests = TableRegistry::get('Institution.TransferRequests');
				$StudentPromotion = TableRegistry::get('Institution.StudentPromotion');
	    		$StudentStatuses = TableRegistry::get('Student.StudentStatuses');

				$id = $this->request->params['pass'][1];
				$selectedStudent = $this->get($id)->student_id;
				$selectedPeriod = $this->get($id)->academic_period_id;
				$selectedGrade = $this->get($id)->education_grade_id;
	    		$this->Session->write($TransferRequests->alias().'.id', $id);

	    		// Show Transfer button only if the Student Status is Current
				$institutionId = $this->Session->read('Institutions.id');
				$currentStatus = $StudentStatuses
					->find()
					->where([$StudentStatuses->aliasField('code') => 'CURRENT'])
					->first()
					->id;
				$pendingStatus = $StudentStatuses
					->find()
					->where([$StudentStatuses->aliasField('code') => 'PENDING_TRANSFER'])
					->first()
					->id;

				$student = $StudentPromotion
					->find()
					->where([
						$StudentPromotion->aliasField('institution_id') => $institutionId,
						$StudentPromotion->aliasField('student_id') => $selectedStudent,
						$StudentPromotion->aliasField('academic_period_id') => $selectedPeriod,
						$StudentPromotion->aliasField('education_grade_id') => $selectedGrade
					])
					->first();
				// End

				// Transfer button
				$transferButton = $buttons['back'];
				$transferButton['type'] = 'button';
				$transferButton['label'] = '<i class="fa kd-transfer"></i>';
				$transferButton['attr'] = $attr;
				$transferButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
				$transferButton['attr']['title'] = __('Transfer');
				//End

	    		if ($student->student_status_id == $currentStatus) {
	    			$transferButton['url'] = [
			    		'plugin' => $buttons['back']['url']['plugin'],
			    		'controller' => $buttons['back']['url']['controller'],
			    		'action' => 'TransferRequests',
			    		'add'
			    	];
					$toolbarButtons['transfer'] = $transferButton;
				} else if ($student->student_status_id == $pendingStatus) {
					$transferRequest = $TransferRequests
						->find()
						->where([
							$TransferRequests->aliasField('previous_institution_id') => $institutionId,
							$TransferRequests->aliasField('security_user_id') => $selectedStudent,
							$TransferRequests->aliasField('status') => 0
						])
						->first();

					$transferButton['url'] = [
						'plugin' => $buttons['back']['url']['plugin'],
			    		'controller' => $buttons['back']['url']['controller'],
			    		'action' => 'TransferRequests',
			    		'edit',
			    		$transferRequest->id
			    	];
			    	$toolbarButtons['transfer'] = $transferButton;
				}
			}
		}
	}
}
