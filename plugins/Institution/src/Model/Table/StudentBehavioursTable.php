<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class StudentBehavioursTable extends AppTable {
	use OptionsTrait;

	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Students', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('StudentBehaviourCategories', ['className' => 'FieldOption.StudentBehaviourCategories']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);

		$this->addBehavior('AcademicPeriod.Period');
		$this->addBehavior('AcademicPeriod.AcademicPeriod');

	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'Model.custom.onUpdateToolbarButtons' => 'onUpdateToolbarButtons',
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}

	// Jeff: is this validation still necessary? perhaps it is already handled by onUpdateFieldAcademicPeriod date_options
	// public function validationDefault(Validator $validator) {
		// get start and end date of selected academic period 
		// $selectedPeriod = $this->request->query('period');
		// if($selectedPeriod) {
		// 	$selectedPeriodEntity = TableRegistry::get('AcademicPeriod.AcademicPeriods')->get($selectedPeriod);
		// 	$startDateFormatted = date_format($selectedPeriodEntity->start_date,'d-m-Y');
		// 	$endDateFormatted = date_format($selectedPeriodEntity->end_date,'d-m-Y');

		// 	$validator
		// 	->add('date_of_behaviour', 
		// 			'ruleCheckInputWithinRange', 
		// 				['rule' => ['checkInputWithinRange', 'date_of_behaviour', $startDateFormatted, $endDateFormatted]]
				
		// 		)
		// 	;
		// 	return $validator;
		// }
	// }

	public function onGetOpenemisNo(Event $event, Entity $entity) {
		return $entity->student->openemis_no;
	}

	public function beforeAction() {
		$this->ControllerAction->field('openemis_no');
		$this->ControllerAction->field('student_id');
		$this->ControllerAction->field('student_behaviour_category_id', ['type' => 'select']);
		
		if ($this->action == 'view' || $this->action = 'edit') {
			$this->ControllerAction->setFieldOrder(['openemis_no', 'student_id', 'date_of_behaviour', 'time_of_behaviour', 'title', 'student_behaviour_category_id']);
		}
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$this->ControllerAction->field('description', ['visible' => false]);
		$this->ControllerAction->field('action', ['visible' => false]);
		$this->ControllerAction->field('time_of_behaviour', ['visible' => false]);

		$this->ControllerAction->setFieldOrder(['openemis_no', 'student_id', 'date_of_behaviour', 'title', 'student_behaviour_category_id']);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$toolbarElements = [
			['name' => 'Institution.Behaviours/controls', 'data' => [], 'options' => []]
		];
		$this->controller->set('toolbarElements', $toolbarElements);

		// Setup period options
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		// $periodOptions = ['0' => __('All Periods')];
		$periodOptions = $AcademicPeriod->getList();
		if (empty($this->request->query['academic_period_id'])) {
			$this->request->query['academic_period_id'] = $AcademicPeriod->getCurrent();
		}

		$Classes = TableRegistry::get('Institution.InstitutionSiteSections');
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$selectedPeriod = $this->queryString('academic_period_id', $periodOptions);

		$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
			'callable' => function($id) use ($Classes, $institutionId) {
				$count = $Classes->find()
				->where([
					$Classes->aliasField('institution_site_id') => $institutionId,
					$Classes->aliasField('academic_period_id') => $id
				])
				->count();
				return $count;
			}
		]);

		// Setup class options
		$classOptions = ['0' => __('All Classes')];
		if (!empty($selectedPeriod)) {
			$classOptions = $classOptions + $Classes
			->find('list')
			->where([
				$Classes->aliasField('institution_site_id') => $institutionId, 
				$Classes->aliasField('academic_period_id') => $selectedPeriod
			])
			->toArray();
			
			$query->find('inPeriod', ['field' => 'date_of_behaviour', 'academic_period_id' => $selectedPeriod]);
		}

		$selectedClass = $this->queryString('class_id', $classOptions);
		$this->advancedSelectOptions($classOptions, $selectedClass);
		// End setup class

		$this->controller->set(compact('periodOptions', 'classOptions'));

		if ($selectedClass > 0) {
			$query->innerJoin(
				['class_student' => 'institution_site_section_students'],
				[
					'class_student.student_id = ' . $this->aliasField('student_id'),
					'class_student.institution_site_section_id = ' . $selectedClass
				]
			);
		}

		// will need to check for search by name: AdvancedNameSearchBehavior
	}

	public function addAfterAction(Event $event, Entity $entity) {
		$this->ControllerAction->field('academic_period_id');
		$this->ControllerAction->field('class');
		$this->ControllerAction->setFieldOrder(['academic_period_id', 'class', 'student_id', 'student_behaviour_category_id', 'date_of_behaviour', 'time_of_behaviour']);
	}

	// PHPOE-1916
	// public function viewAfterAction(Event $event, Entity $entity) {
	// 	$this->request->data[$this->alias()]['student_id'] = $entity->student_id;
	// 	$this->request->data[$this->alias()]['date_of_behaviour'] = $entity->date_of_behaviour;
	// }

	public function editBeforeQuery(Event $event, Query $query) {
		$query->contain(['Students']);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->fields['student_id']['attr']['value'] = $entity->student->name_with_id;

		// PHPOE-1916
		// Not yet implemented due to possible performance issue
		// $InstitutionClassStudentTable = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
		// $AcademicPeriodId = $InstitutionClassStudentTable->find()
		// 				->where([$InstitutionClassStudentTable->aliasField('student_id') => $entity->student_id])
		// 				->innerJoin(['InstitutionSiteClasses' => 'institution_site_sections'],[
		// 						'InstitutionSiteClasses.id = '.$InstitutionClassStudentTable->aliasField('institution_site_section_id'),
		// 						'InstitutionSiteClasses.institution_site_id' => $entity->institution_id
		// 					])
		// 				->innerJoin(['AcademicPeriods' => 'academic_periods'], [
		// 						'AcademicPeriods.id = InstitutionSiteClasses.academic_period_id',
		// 						'AcademicPeriods.start_date <= ' => $entity->date_of_behaviour->format('Y-m-d'), 
		// 						'AcademicPeriods.end_date >= ' => $entity->date_of_behaviour->format('Y-m-d')
		// 					])
		// 				->select(['id' => 'AcademicPeriods.id', 'editable' => 'AcademicPeriods.editable'])
		// 				->first()
		// 				->toArray();

		// if (! $AcademicPeriodId['editable']) {
		// 	$urlParams = $this->ControllerAction->url('view');
		// 	$event->stopPropagation();
		// 	return $this->controller->redirect($urlParams);
		// }
	}

	// PHPOE-1916
	// Not yet implemented due to possible performance issue
	// public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
	// 	if ($action == 'view') {
	// 		$institutionId = $this->Session->read('Institution.Institutions.id');
	// 		$studentId = $this->request->data[$this->alias()]['student_id'];
	// 		$dateOfBehaviour = $this->request->data[$this->alias()]['date_of_behaviour'];
	// 		$InstitutionClassStudentTable = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
	// 		$AcademicPeriodId = $InstitutionClassStudentTable->find()
	// 				->where([$InstitutionClassStudentTable->aliasField('student_id') => $studentId])
	// 				->innerJoin(['InstitutionSiteClasses' => 'institution_site_sections'],[
	// 						'InstitutionSiteClasses.id = '.$InstitutionClassStudentTable->aliasField('institution_site_section_id'),
	// 						'InstitutionSiteClasses.institution_site_id' => $institutionId
	// 					])
	// 				->innerJoin(['AcademicPeriods' => 'academic_periods'], [
	// 						'AcademicPeriods.id = InstitutionSiteClasses.academic_period_id',
	// 						'AcademicPeriods.start_date <= ' => $dateOfBehaviour->format('Y-m-d'), 
	// 						'AcademicPeriods.end_date >= ' => $dateOfBehaviour->format('Y-m-d')
	// 					])
	// 				->select(['id' => 'AcademicPeriods.id', 'editable' => 'AcademicPeriods.editable'])
	// 				->first()
	// 				->toArray();

	// 		if (! $AcademicPeriodId['editable']) {
	// 			if(isset($toolbarButtons['edit'])) {
	// 				unset($toolbarButtons['edit']);
	// 			}
	// 		}
	// 	}
	// }

	public function onUpdateFieldOpenemisNo(Event $event, array $attr, $action, $request) {
		if ($action == 'edit' || $action == 'add') {
			$attr['visible'] = false;
		}
		return $attr;
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request) {
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');

		$Classes = TableRegistry::get('Institution.InstitutionSiteSections');

		if ($action == 'add') {
			$periodOptions = ['0' => $this->selectEmpty('period')];
			$periodOptions = $periodOptions + $AcademicPeriod->getList();
			$selectedPeriod = 0;
			if ($request->is(['post', 'put'])) {
				$selectedPeriod = $request->data($this->aliasField('academic_period_id'));
			}
			$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
				'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
				'callable' => function($id) use ($Classes, $institutionId) {
					$count = $Classes->find()
					->where([
						$Classes->aliasField('institution_site_id') => $institutionId,
						$Classes->aliasField('academic_period_id') => $id
					])
					->count();
					return $count;
				}
			]);

			$attr['options'] = $periodOptions;
			$attr['onChangeReload'] = 'changePeriod';

			//set start and end dates for date of behaviour based on chosen academic period
			if (!empty($selectedPeriod)) {
				$periodEntity = $AcademicPeriod->get($selectedPeriod);
				$dateOptions = [
					'startDate' => $periodEntity->start_date->format('d-m-Y'), 
					'endDate' => $periodEntity->end_date->format('d-m-Y')
				];
				$this->fields['date_of_behaviour']['date_options'] = $dateOptions;
			}
		}
		return $attr;
	}

	public function addOnChangePeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$data[$this->alias()]['class'] = 0;
	}

	public function onUpdateFieldClass(Event $event, array $attr, $action, $request) {
		if ($action == 'add') {
			$institutionId = $this->Session->read('Institution.Institutions.id');
			$selectedPeriod = 0;
			if ($request->is(['post', 'put'])) {
				$selectedPeriod = $request->data($this->aliasField('academic_period_id'));
			}

			$classOptions = ['0' => $this->selectEmpty('class')];

			if ($selectedPeriod != 0) {
				$Classes = TableRegistry::get('Institution.InstitutionSiteSections');
				$Students = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
				$classOptions = $classOptions + $Classes
					->find('list')
					->where([
						$Classes->aliasField('institution_site_id') => $institutionId,
						$Classes->aliasField('academic_period_id') => $selectedPeriod
					])
					->order([$Classes->aliasField('section_number') => 'ASC'])
					->toArray();

				$selectedClass = 0;
				if ($request->is(['post', 'put'])) {
					$selectedClass = $request->data($this->aliasField('class'));
				}
				$this->advancedSelectOptions($classOptions, $selectedClass, [
					'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
					'callable' => function($id) use ($Students) {
						return $Students
							->find()
							->where([
								$Students->aliasField('institution_site_section_id') => $id,
								$Students->aliasField('status') => 1
							])
							->count();
					}
				]);
			}

			$attr['options'] = $classOptions;
			$attr['onChangeReload'] = 'changeClass';
		}
		return $attr;
	}

	// Start PHPOE-1897

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->request->data[$this->alias()]['student_id'] = $entity->student_id;
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		// $ClassStudents = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
		$studentId = $entity->student_id;
		$institutionId = $entity->institution_id;
		$StudentTable = TableRegistry::get('Institution.Students');

		if (! $StudentTable->checkEnrolledInInstitution($studentId, $institutionId)) {
			if (isset($buttons['edit'])) {
				unset($buttons['edit']);
			}
			if (isset($buttons['remove'])) {
				unset($buttons['remove']);
			}
		}
		return $buttons;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'view') {
			$institutionId = $this->Session->read('Institution.Institutions.id');
			$studentId = $this->request->data[$this->alias()]['student_id'];
			$StudentTable = TableRegistry::get('Institution.Students');
			if (! $StudentTable->checkEnrolledInInstitution($studentId, $institutionId)) {
				if (isset($toolbarButtons['edit'])) {
					unset($toolbarButtons['edit']);
				}
			}
		}
	}
	// End PHPOE-1897

	public function onUpdateFieldStudentId(Event $event, array $attr, $action, $request) {
		if ($action == 'add') {
			$studentOptions = ['' => $this->selectEmpty('student')];

			$selectedClass = 0;
			if ($request->is(['post', 'put'])) {
				$selectedClass = $request->data($this->aliasField('class'));
			}
			if (! $selectedClass==0	&& ! empty($selectedClass)) {
				$Students = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
				$studentOptions = $studentOptions + $Students
				->find('list', ['keyField' => 'student_id', 'valueField' => 'student_name'])
				->contain(['Users'])
				->where([$Students->aliasField('institution_site_section_id') => $selectedClass])
				->toArray();
			}
			
			$attr['options'] = $studentOptions;
		} else if ($action == 'edit') {
			$attr['type'] = 'readonly';
		}
		return $attr;
	}
}
