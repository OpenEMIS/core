<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\MessagesTrait;
use Cake\Validation\Validator;

class InstitutionAssessmentsTable extends AppTable {
	use OptionsTrait;
	use MessagesTrait;

	// Assessments Status
	const EXPIRED = -1;
	const NEW_STATUS = 0;
	const DRAFT = 1;
	const COMPLETED = 2;

	private $Classes = null;
	private $ClassGrades = null;
	private $ClassSubjects = null;

	private $Subjects = null;
	private $SubjectStaff = null;
	private $SubjectStudents = null;

	private $AssessmentItems = null;
	private $AssessmentItemResults = null;

	private $educationSubjectIds = [];
	private $userId = null;

	private $classOptions = [];
	private $subjectOptions = [];
	private $dataNamedGroup = [];
	private $baseUrl = null;

	private $institutionId;

	public function initialize(array $config) {
		$this->table('institution_assessments');
		parent::initialize($config);
		
		$this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->addBehavior('AcademicPeriod.AcademicPeriod');
		$this->addBehavior('Excel', ['excludes' => ['status', 'education_grade_id', 'institution_section_id']]);
	}

	public function validationDefault(Validator $validator) {
		return $validator
			->requirePresence('class')
			->notEmpty('class', 'This field is required.')
			->requirePresence('subject')
			->notEmpty('subject', 'This field is required.')
			;
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets) {
    	$institutionId = 0;
    	$session = $this->controller->request->session();
		if ($session->check('Institution.Institutions.id')) {
			$institutionId = $session->read('Institution.Institutions.id');
		}
		$academicPeriodId = $this->AcademicPeriods->getCurrent();

    	// Get list of classes in the institution
		$classOptions = $this->Classes->getSectionOptions($academicPeriodId, $institutionId);
		// Class assessments
		$classAssessments = $this->getClassAssessments($institutionId, $academicPeriodId);
		$sheetTable = TableRegistry::get('Institution.InstitutionSectionStudents');

		foreach($classAssessments as $classId => $assessments) {
	    	// Main sheet table
	    	$sheets[] = [
				'name' => $classOptions[$classId],
				'table' => $sheetTable,
				'query' => $sheetTable->find()->where([$sheetTable->aliasField('institution_section_id') => $classId]),
				'orientation' => 'landscape',
				'institutionId' => $institutionId,
				'academicPeriodId' => $academicPeriodId,
				'classId' => $classId,
				'assessments' => $assessments,
			];
		}

		if (empty($classAssessments)) {
			$academicPeriodName = TableRegistry::get('AcademicPeriods.AcademicPeriods')->get($academicPeriodId)->name;
			$sheets[] = [
				'name' => __('No Classes for '.$academicPeriodName),
				'table' => $sheetTable,
				'query' => $sheetTable->find()->where([$sheetTable->aliasField('institution_section_id') => 0]),
				'orientation' => 'landscape',
				'institutionId' => $institutionId,
				'academicPeriodId' => $academicPeriodId,
				'classId' => 0,
				'assessments' => [],
			];
		}
    }

    // Function to get the list of assessments to a class
    public function getClassAssessments($institutionId, $academicPeriodId, $classId=null) {
    	$condition = [];
    	if (!(is_null($classId))) {
    		$condition = ['InstitutionSections.id' => $classId];
    	}

    	$results = $this
    		->find()
    		->matching('Assessments.EducationGrades.InstitutionSectionStudents.InstitutionSections')
    		->where([
    			$this->aliasField('institution_id') => $institutionId, 
    			$this->aliasField('academic_period_id') => $academicPeriodId,
    			'Assessments.education_grade_id = InstitutionSectionStudents.education_grade_id',
    			'InstitutionSections.institution_id' => $institutionId, 
    			'InstitutionSections.academic_period_id' => $academicPeriodId, 
    			$this->aliasField('status') => self::COMPLETED,
    		])
    		->where($condition)
    		->select(['class' => 'InstitutionSections.id', 'assessment' => 'Assessments.id'])
    		->group(['class', 'assessment'])
    		->hydrate(false)
    		->toArray();

    	$returnArray = [];
    	foreach ($results as $item) {
    		$returnArray[$item['class']][] = $item['assessment'];
    	}
    	return $returnArray;
    }

	public function onGetStatus(Event $event, Entity $entity) {
		$statusOptions = $this->getSelectOptions('Assessments.status');
		return $statusOptions[$entity->status];
	}

	public function onGetAssessmentId(Event $event, Entity $entity) {
		$currentAction = $this->ControllerAction->action();
		if ($currentAction == 'index') {
			if ($entity->status == self::NEW_STATUS || $entity->status == self::DRAFT) {
				$url = $this->ControllerAction->url('edit');
			} else if ($entity->status == self::COMPLETED) {
				$url = $this->ControllerAction->url('view');
			}
			$url[1] = $entity->id;

			return $event->subject()->Html->link($entity->assessment->code_name, $url);
		} else if ($currentAction == 'view') {
			return $entity->assessment->code_name;
		}
	}

	public function onGetDescription(Event $event, Entity $entity) {
		$entity = $this->get($entity->id);
		$value = '';

		$results = $this->Assessments
			->find()
			->where([
				$this->Assessments->aliasField('id') => $entity->assessment_id
			])
			->all();

		if (!$results->isEmpty()) {
			$value = $results->first()->description;
		}

		return $value;
	}

	public function onGetClass(Event $event, Entity $entity) {
		$html = '';

		$Form = $event->subject()->Form;
		$url = [
			'plugin' => $this->request->params['plugin'],
		    'controller' => $this->request->params['controller'],
		    'action' => $this->request->params['action']
		];
		if (!empty($this->request->pass)) {
			$url = array_merge($url, $this->request->pass);
		}
		$this->dataNamedGroup = [];
		if (!empty($this->request->query)) {
			foreach ($this->request->query as $key => $value) {
				if (in_array($key, ['class', 'subject'])) continue;
				echo $Form->hidden($key, [
					'value' => $value,
					'data-named-key' => $key
				]);
				$this->dataNamedGroup[] = $key;
			}
		}
		$this->baseUrl = $event->subject()->Url->build($url);

		$inputOptions = [
			'class' => 'form-control',
			'label' => false,
			'options' => $this->classOptions,
			'url' => $this->baseUrl,
			'data-named-key' => 'class',
			'escape' => false
		];
		if (!empty($this->dataNamedGroup)) {
			$inputOptions['data-named-group'] = implode(',', $this->dataNamedGroup);
			$this->dataNamedGroup[] = 'class';
		}

		$fieldPrefix = $this->alias();
        $html = $Form->input($fieldPrefix.".class", $inputOptions);

		return $html;
	}

	public function onGetSubject(Event $event, Entity $entity) {
		$html = '';

		$Form = $event->subject()->Form;
		$inputOptions = [
			'class' => 'form-control',
			'label' => false,
			'options' => $this->subjectOptions,
			'url' => $this->baseUrl,
			'data-named-key' => 'subject',
			'escape' => false
		];
		if (!empty($this->dataNamedGroup)) {
			$inputOptions['data-named-group'] = implode(',', $this->dataNamedGroup);
			$this->dataNamedGroup[] = 'subject';
		}

		$fieldPrefix = $this->alias();
        $html = $Form->input($fieldPrefix.".subject", $inputOptions);

		return $html;
	}

	public function onGetToBeCompletedBy(Event $event, Entity $entity) {
		$entity = $this->get($entity->id);
		$value = '<i class="fa fa-minus"></i>';

		$AssessmentStatuses = TableRegistry::get('Assessment.AssessmentStatuses');
		$AssessmentStatusPeriods = TableRegistry::get('Assessment.AssessmentStatusPeriods');

		$results = $AssessmentStatuses
			->find()
			->select([
				$AssessmentStatuses->aliasField('date_disabled')
			])
			->where([$AssessmentStatuses->aliasField('assessment_id') => $entity->assessment_id])
			->innerJoin(
				[$AssessmentStatusPeriods->alias() => $AssessmentStatusPeriods->table()],
				[
					$AssessmentStatusPeriods->aliasField('assessment_status_id = ') . $AssessmentStatuses->aliasField('id'),
					$AssessmentStatusPeriods->aliasField('academic_period_id') => $entity->academic_period_id
				]
			)
			->all();

		if (!$results->isEmpty()) {
			$dateDisabled = $results->first()->date_disabled;
			$value = $this->formatDate($dateDisabled);
		}

		return $value;
	}

	public function onGetLastModified(Event $event, Entity $entity) {
		return $this->formatDateTime($entity->modified);
	}

	public function onGetCompletedOn(Event $event, Entity $entity) {
		return $this->formatDateTime($entity->modified);
	}

	public function beforeAction(Event $event) {
		$this->Classes = TableRegistry::get('Institution.InstitutionSections');
		$this->ClassGrades = TableRegistry::get('Institution.InstitutionSectionGrades');
		$this->ClassSubjects = TableRegistry::get('Institution.InstitutionSectionClasses');

		$this->Subjects = TableRegistry::get('Institution.InstitutionClasses');
		$this->SubjectStaff = TableRegistry::get('Institution.InstitutionClassStaff');
		$this->SubjectStudents = TableRegistry::get('Institution.InstitutionClassStudents');

		$this->AssessmentItems = TableRegistry::get('Assessment.AssessmentItems');
		$this->AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');

		$this->institutionId = $this->Session->read('Institution.Institutions.id');
		$this->userId = $this->Auth->user('id');
    }

	public function indexBeforeAction(Event $event) {
		list($statusOptions, $selectedStatus) = array_values($this->_getSelectOptions());

		// tabElements
		$plugin = $this->controller->plugin;
		$controller = $this->controller->name;
		$action = $this->alias;

		$tabElements = [];
		if ($this->AccessControl->check([$this->controller->name, 'Assessments', 'edit'])) {
			$tabElements['New'] = [
				'url' => ['plugin' => $plugin, 'controller' => $controller, 'action' => $action.'?status='.self::NEW_STATUS],
				'text' => __('New')
			];
			$tabElements['Draft'] = [
				'url' => ['plugin' => $plugin, 'controller' => $controller, 'action' => $action.'?status='.self::DRAFT],
				'text' => __('Draft')
			];
		}
		if ($this->AccessControl->check([$this->controller->name, 'Assessments', 'index'])) {
			$tabElements['Completed'] = [
				'url' => ['plugin' => $plugin, 'controller' => $controller, 'action' => $action.'?status='.self::COMPLETED],
				'text' => __('Completed')
			];
		}

		$this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $statusOptions[$selectedStatus]);
        // End

        $this->ControllerAction->field('status', ['visible' => false]);
        $this->ControllerAction->field('description');

        // Set field order
        $fieldOrder = ['assessment_id', 'description', 'academic_period_id'];
        if ($selectedStatus == self::NEW_STATUS) {	// New
			$this->ControllerAction->field('to_be_completed_by');
			$fieldOrder[] = 'to_be_completed_by';
			$this->_buildRecords();
        } else if ($selectedStatus == self::DRAFT) {	// Draft
			$this->ControllerAction->field('last_modified');
			$this->ControllerAction->field('to_be_completed_by');
			$fieldOrder[] = 'last_modified';
			$fieldOrder[] = 'to_be_completed_by';
        } else if ($selectedStatus == self::COMPLETED) {	// Completed
			$this->ControllerAction->field('completed_on');
			$fieldOrder[] = 'completed_on';
        }

        $this->ControllerAction->setFieldOrder($fieldOrder);
        // End
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		list(, $selectedStatus) = array_values($this->_getSelectOptions());

		$options['auto_contain'] = false;
		$query
			->contain(['Assessments', 'AcademicPeriods'])
			->where([$this->aliasField('status') => $selectedStatus])
			->order([$this->AcademicPeriods->aliasField('order')]);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->request->query['status'] = $entity->status;
		$this->request->query['academic_period_id'] = $entity->academic_period_id;
		$this->request->query['assessment_id'] = $entity->assessment_id;

		$this->_setupFields($entity);
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$this->request->query['status'] = $entity->status;
		$this->request->query['academic_period_id'] = $entity->academic_period_id;
		$this->request->query['assessment_id'] = $entity->assessment_id;
	}

	public function editBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
		$InstitutionAssessments = $this;
		$AssessmentItems = $this->AssessmentItems;
		$AssessmentItemResults = $this->AssessmentItemResults;

		$process = function($model, $entity) use ($data, $InstitutionAssessments, $AssessmentItems, $AssessmentItemResults) {
			$errors = $entity->errors();

			if (empty($errors)) {
				$institutionId = $data[$InstitutionAssessments->alias()]['institution_id'];
				$periodId = $data[$InstitutionAssessments->alias()]['academic_period_id'];

				$students = [];
				foreach ($data[$InstitutionAssessments->alias()]['students'] as $key => $obj) {
					$students[$key] = [
						'student_id' => $obj['student_id'],
						// 'marks' => $obj['marks'],
						'assessment_grading_option_id' => $obj['assessment_grading_option_id'],
						'institution_id' => $institutionId,
						'academic_period_id' => $periodId
					];

					// If grading result type is not MARKS, then marks field will not be sent
					$insertRecord = true;
					if (isset($obj['marks'])) {	// MARKS
						$students[$key]['marks'] = $obj['marks'];
						if (strlen($obj['marks']) == 0 && $obj['assessment_grading_option_id'] == 0) {
							$insertRecord = false;
						}
					} else {	// GRADES
						if ($obj['assessment_grading_option_id'] == 0) {
							$insertRecord = false;
						}
					}

					if (isset($obj['id'])) {
						$students[$key]['id'] = $obj['id'];
						if (!$insertRecord) {
							$this->AssessmentItemResults->deleteAll([
								$this->AssessmentItemResults->aliasField('id') => $obj['id']
							]);
						}
					}

					if (!$insertRecord) {
						unset($students[$key]);
					}
				}

				$assessmentData = [
					$AssessmentItems->alias() => [
						'id' => $data[$InstitutionAssessments->alias()]['assessment_item'],
						$AssessmentItemResults->table() => $students
					]
				];
				$assessmentEntity = $AssessmentItems->newEntity($assessmentData, ['validate' => false]);

				if( $AssessmentItems->save($assessmentEntity) ){
					$InstitutionAssessments->updateAll(
						['status' => $data[$InstitutionAssessments->alias()]['status']],
						['id' => $data[$InstitutionAssessments->alias()]['id']]
					);

					return true;
				} else {
					$AssessmentItems->log($assessmentEntity->errors(), 'debug');
					return false;
				}
			} else {
				return false;
			}
		};

		return $process;
	}

	public function editAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		if ($entity->status == self::DRAFT) {
			$url = $this->ControllerAction->url('edit');
		} else if ($entity->status == self::COMPLETED) {
			$indexUrl = $this->ControllerAction->url('index');
			$url = [
				'plugin' => $indexUrl['plugin'],
				'controller' => $indexUrl['controller'],
				'action' => $indexUrl['action'],
				0 => 'index',
				'status' => $indexUrl['status']
			];
		}

		$event->stopPropagation();
		return $this->controller->redirect($url);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->_setupFields($entity);
	}

	public function onBeforeDelete(Event $event, ArrayObject $options, $id) {
		$assessmentRecord = $this->get($id);

		if ($assessmentRecord->status == self::DRAFT) {
			$entity = $this->newEntity(['id' => $id, 'status' => self::NEW_STATUS], ['validate' => false]);

			if ($this->save($entity)) {
				// To clear all records in assessment_item_results when delete from draft
				$AssessmentItems = $this->Assessments->AssessmentItems;
				$itemIds = $AssessmentItems
					->find('list', ['keyField' => 'id', 'valueField' => 'id'])
					->where([$AssessmentItems->aliasField('assessment_id') => $assessmentRecord->assessment_id])
					->toArray();

				$Results = TableRegistry::get('Assessment.AssessmentItemResults');
				$Results->deleteAll([
					$Results->aliasField('institution_id') => $assessmentRecord->institution_id,
					$Results->aliasField('academic_period_id') => $assessmentRecord->academic_period_id,
					$Results->aliasField('assessment_item_id IN') => $itemIds
				]);

				$this->Alert->success('general.delete.success');
			} else {
				$this->Alert->success('general.delete.failed');
				$this->log($entity->errors(), 'debug');
			}

			$event->stopPropagation();
			$url = $this->ControllerAction->url('index'); //$this->ControllerAction->buttons['index']['url']
			$url['status'] = self::DRAFT;

			return $this->controller->redirect($url);
		} else if ($assessmentRecord->status == self::COMPLETED) {
			$entity = $this->newEntity(['id' => $id, 'status' => self::DRAFT], ['validate' => false]);

			if ($this->save($entity)) {
				$this->Alert->success('InstitutionAssessments.reject.success');
			} else {
				$this->Alert->success('InstitutionAssessments.reject.failed');
				$this->log($entity->errors(), 'debug');
			}

			$event->stopPropagation();
			$url = $this->ControllerAction->url('index'); //$this->ControllerAction->buttons['index']['url']
			$url['status'] = self::COMPLETED;

			return $this->controller->redirect($url);
		}
	}

	public function onUpdateFieldStatus(Event $event, array $attr, $action, Request $request) {
		if ($action == 'edit') {
			list($statusOptions, $selectedStatus) = array_values($this->_getSelectOptions());

			$attr['type'] = 'readonly';
			$attr['attr']['value'] = __($statusOptions[$selectedStatus]);
			$attr['attr']['assessment-status'] = 1;
		}

    	return $attr;
    }

    public function onUpdateFieldAssessmentId(Event $event, array $attr, $action, Request $request) {
		$selectedAssessment = $request->query('assessment_id');
		$assessment = $this->Assessments->get($selectedAssessment);
		$request->query['education_grade_id'] = $assessment->education_grade_id;

		$AssessmentItems = TableRegistry::get('Assessment.AssessmentItems');
		$this->educationSubjectIds = $AssessmentItems
			->find('list', ['keyField' => 'education_subject_id', 'valueField' => 'education_subject_id'])
			->find('visible')
			->where([
				$AssessmentItems->aliasField('assessment_id') => $selectedAssessment
			])
			->toArray();

		if (empty($this->educationSubjectIds)) {
			$this->Alert->warning($this->aliasField('noSubjects'));
		}

		if ($action == 'edit') {
    		$attr['type'] = 'readonly';
    		$attr['attr']['value'] = $assessment->code_name;
    	}

    	return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'edit') {
    		$selectedPeriod = $request->query('academic_period_id');

    		$attr['type'] = 'readonly';
    		$attr['attr']['value'] = $this->AcademicPeriods->get($selectedPeriod)->name;
    	}

    	return $attr;
    }

    public function onUpdateFieldClass(Event $event, array $attr, $action, Request $request) {
		$institutionId = $this->institutionId;
    	$selectedPeriod = $request->query('academic_period_id');
    	$selectedAssessment = $request->query('assessment_id');
    	$selectedGrade = $request->query('education_grade_id');

		$classOptions = [];
		$_conditions = [
			$this->Classes->aliasField('institution_id') => $institutionId,
			$this->Classes->aliasField('academic_period_id') => $selectedPeriod
		];

		$query = $this->Classes
			->find('list')
			->innerJoin(
				[$this->ClassGrades->alias() => $this->ClassGrades->table()],
				[
					$this->ClassGrades->aliasField('institution_section_id = ') . $this->Classes->aliasField('id'),
					$this->ClassGrades->aliasField('education_grade_id') => $selectedGrade
				]
			)
			->innerJoin(
				[$this->ClassSubjects->alias() => $this->ClassSubjects->table()],
				[
					$this->ClassSubjects->aliasField('institution_section_id = ') . $this->Classes->aliasField('id')
				]
			)
			->innerJoin(
				[$this->Subjects->alias() => $this->Subjects->table()],
				[
					$this->Subjects->aliasField('id = ') . $this->ClassSubjects->aliasField('institution_class_id'),
					$this->Subjects->aliasField('education_subject_id IN') => $this->educationSubjectIds
				]
			)
			->where($_conditions);

		if ($this->AccessControl->isAdmin()) {
			// Superadmin should see all Classes
			$classOptions = $query->toArray();
		} else {
			if ($this->AccessControl->check(['Institutions', 'Sections', 'index'])) {
				$conditions = array_merge($_conditions, [
					$this->Classes->aliasField('staff_id') => $this->userId // homeroom teacher
				]);

				$query->where($conditions, [], true);

				if ($this->AccessControl->check(['Institutions', 'Classes', 'index'])) {
					// User has access to My Classes and My Subjects
					$classOptions = $query->toArray();

					$attr['homeroom_classes'] = $classOptions;

					// Find Classes where the user is the subject teacher
					$query->where($_conditions, [], true);
					$query->innerJoin(
						[$this->SubjectStaff->alias() => $this->SubjectStaff->table()],
						[
							$this->SubjectStaff->aliasField('institution_class_id = ') . $this->Subjects->aliasField('id'),
							$this->SubjectStaff->aliasField('staff_id') => $this->userId, // subject teacher
							$this->SubjectStaff->aliasField('status') => 1
						]
					);
					$subjectClassOptions = $query->toArray();
					// End

					$classOptions = $classOptions + $subjectClassOptions;
				} else {
					// User has access to My Classes but don't have access to My Subjects
					$classOptions = $query->toArray();
				}
			} else {
				if ($this->AccessControl->check(['Institutions', 'Classes', 'index'])) {
					// User don't have access to My Classes but has access to My Subjects
					$query->innerJoin(
						[$this->SubjectStaff->alias() => $this->SubjectStaff->table()],
						[
							$this->SubjectStaff->aliasField('institution_class_id = ') . $this->Subjects->aliasField('id'),
							$this->SubjectStaff->aliasField('staff_id') => $this->userId, // subject teacher
							$this->SubjectStaff->aliasField('status') => 1
						]
					);

					$classOptions = $query->toArray();
				} else {
					// User don't have access to My Classes and My Subjects
					$classOptions = $query->toArray();
				}
			}
		}

		if (empty($classOptions )) {
	  		$this->Alert->warning($this->aliasField('noSections'));
	  	} else {
	  		$selectedClass = $this->queryString('class', $classOptions);
			$this->advancedSelectOptions($classOptions, $selectedClass);
	  	}

    	if ($action == 'view') {
    		$this->classOptions = $classOptions;
    		$attr['valueClass'] = 'table-full-width';
    	} else if ($action == 'edit') {
    		$attr['type'] = 'select';
    		$attr['attr']['options'] = $classOptions;
    		$attr['onChangeReload'] = 'changeClass';
    	}

    	return $attr;
    }

    public function onUpdateFieldSubject(Event $event, array $attr, $action, Request $request) {
    	$institutionId = $this->institutionId;
    	$selectedPeriod = $request->query('academic_period_id');
    	$selectedAssessment = $request->query('assessment_id');
    	$selectedGrade = $request->query('education_grade_id');
    	$selectedClass = $request->query('class');

		$subjectOptions = [];
		$query = $this->Subjects
			->find('list')
			->innerJoin(
				[$this->ClassSubjects->alias() => $this->ClassSubjects->table()],
				[
					$this->ClassSubjects->aliasField('institution_class_id = ') . $this->Subjects->aliasField('id'),
					$this->ClassSubjects->aliasField('institution_section_id') => $selectedClass
				]
			)
			->where([
				$this->Subjects->aliasField('institution_id') => $institutionId,
				$this->Subjects->aliasField('academic_period_id') => $selectedPeriod,
				$this->Subjects->aliasField('education_subject_id IN') => $this->educationSubjectIds
			]);

		if ($this->AccessControl->isAdmin()) {
			// Superadmin should see all Subjects
			$subjectOptions = $query->toArray();
		} else {
			if ($this->AccessControl->check(['Institutions', 'Sections', 'index'])) {
				if ($this->AccessControl->check(['Institutions', 'Classes', 'index'])) {
					// User has access to My Classes and My Subjects
					// Check if the login user is not the Homeroom teacher then filter by Subject teacher

					$homeroomClasses = $this->fields['class']['homeroom_classes'];
					if (!array_key_exists($selectedClass, $homeroomClasses)) {
						$query->innerJoin(
							[$this->SubjectStaff->alias() => $this->SubjectStaff->table()],
							[
								$this->SubjectStaff->aliasField('institution_site_class_id = ') . $this->Subjects->aliasField('id'),
								$this->SubjectStaff->aliasField('staff_id') => $this->userId, // subject teacher
								$this->SubjectStaff->aliasField('status') => 1
							]
						);
					}

					$subjectOptions = $query->toArray();
				} else {
					// User has access to My Classes but don't have access to My Subjects
					$subjectOptions = $query->toArray();
				}
			} else {
				if ($this->AccessControl->check(['Institutions', 'Classes', 'index'])) {
					// User don't have access to My Classes but has access to My Subjects
					$query->innerJoin(
						[$this->SubjectStaff->alias() => $this->SubjectStaff->table()],
						[
							$this->SubjectStaff->aliasField('institution_class_id = ') . $this->Subjects->aliasField('id'),
							$this->SubjectStaff->aliasField('staff_id') => $this->userId, // subject teacher
							$this->SubjectStaff->aliasField('status') => 1
						]
					);

					$subjectOptions = $query->toArray();
				} else {
					// User don't have access to My Classes and My Subjects
					$subjectOptions = $query->toArray();
				}
			}
		}

		if (empty($subjectOptions)) {
	  		$this->Alert->warning($this->aliasField('noClasses'));
	  	} else {
	  		$SubjectStudents = $this->SubjectStudents;
	  		$selectedSubject = $this->queryString('subject', $subjectOptions);
			$this->advancedSelectOptions($subjectOptions, $selectedSubject, [
				'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
				'callable' => function($id) use ($SubjectStudents) {
					return $SubjectStudents
						->find()
						->where([
							$SubjectStudents->aliasField('institution_class_id') => $id,
							$SubjectStudents->aliasField('status') => 1
						])
						->count();
				}
			]);
	  	}

		if ($action == 'view') {
			$this->subjectOptions = $subjectOptions;
    		$attr['valueClass'] = 'table-full-width';
    	} else if ($action == 'edit') {
    		$attr['type'] = 'select';
    		$attr['attr']['options'] = $subjectOptions;
    		$attr['onChangeReload'] = 'changeSubject';
    	}

    	return $attr;
    }

    public function onUpdateFieldAssessmentItem(Event $event, array $attr, $action, Request $request) {
    	if ($action == 'view' || $action == 'edit') {
    		$institutionId = $this->institutionId;
	    	$selectedPeriod = $request->query('academic_period_id');
	    	$selectedAssessment = $request->query('assessment_id');
	    	$selectedGrade = $request->query('education_grade_id');
	    	$selectedClass = $request->query('class');
	    	$selectedSubject = $request->query('subject');

	    	$assessmentItemId = null;
	    	if (!is_null($selectedSubject)) {
	    		$subject = $this->Subjects->get($selectedSubject);
	    		$educationSubjectId = $subject->education_subject_id;

	    		$results = $this->AssessmentItems
	    			->find()
	    			->where([
						$this->AssessmentItems->aliasField('assessment_id') => $selectedAssessment,
						$this->AssessmentItems->aliasField('education_subject_id') => $educationSubjectId
	    			])
	    			->all();

	    		if (!$results->isEmpty()) {
	    			$assessmentItemId = $results->first()->id;
	    			$request->query['assessment_item_id'] = $assessmentItemId;
	    		}
	    	}

	    	$attr['type'] = 'hidden';
	    	$attr['attr']['value'] = $assessmentItemId;
	    }

		return $attr;
    }

    public function onUpdateFieldStudents(Event $event, array $attr, $action, Request $request) {
    	$institutionId = $this->institutionId;
    	$selectedPeriod = $request->query('academic_period_id');
    	$selectedAssessment = $request->query('assessment_id');
    	$selectedGrade = $request->query('education_grade_id');
    	$selectedClass = $request->query('class');
    	$selectedSubject = $request->query('subject');
    	$assessmentItemId = $request->query('assessment_item_id');

		$students = [];
		$assessmentItemObj = [];
		if (!is_null($assessmentItemId)) {
			// Students
			$query = $this->SubjectStudents
				->find()
				->matching('Users')
				->select([
					$this->AssessmentItemResults->aliasField('id'),
					$this->AssessmentItemResults->aliasField('marks'),
					$this->AssessmentItemResults->aliasField('assessment_grading_option_id')
				])
				->leftJoin(
					[$this->AssessmentItemResults->alias() => $this->AssessmentItemResults->table()],
					[
						$this->AssessmentItemResults->aliasField('student_id = ') . $this->SubjectStudents->aliasField('student_id'),
						$this->AssessmentItemResults->aliasField('institution_id') => $institutionId,
						$this->AssessmentItemResults->aliasField('academic_period_id') => $selectedPeriod,
						$this->AssessmentItemResults->aliasField('assessment_item_id') => $assessmentItemId
					]
				)
				->where([
					$this->SubjectStudents->aliasField('institution_class_id') => $selectedSubject,
					$this->SubjectStudents->aliasField('status') => 1
				])
				->autoFields(true);

			$students = $query->toArray();
			// End

			// Grading Options
			$gradingOptions = [];
			$results = $this->AssessmentItems
				->find()
				->where([
					$this->AssessmentItems->aliasField('id') => $assessmentItemId
				])
				->contain([
					'GradingTypes' => function ($q) {
						return $q->find('visible');
					},
					'GradingTypes.GradingOptions' => function ($q) {
						return $q
							->find('visible')
							->find('order');
					}
				])
				->first();

			if ($action == 'view') {
				// List only
				foreach ($results->grading_type->grading_options as $grading) {
			  		$gradingName = !empty($grading->code) ? $grading->code . ' - ' . $grading->name : $grading->name;
			  		$gradingOptions[$grading->id] = $gradingName;
			  	}
			} else if ($action == 'edit') {
				$gradingOptions[0] = [
					'value' => 0,
					'text' => __('-- Select Grading --')
				];
			  	foreach ($results->grading_type->grading_options as $grading) {
			  		$gradingName = !empty($grading->code) ? $grading->code . ' - ' . $grading->name : $grading->name;
			  		$gradingOptions[$grading->id] = [
			  			'value' => $grading->id,
			  			'text' => $gradingName,
			  			'data-grading-type-id' => $grading->assessment_grading_type_id,
			  			'data-min' => $grading->min,
			  			'data-max' => $grading->max
			  		];
			  	}
			}
		  	// End

		  	$assessmentItemObj = [
		  		'result_type' => $results->result_type,
		  		'max' => $results->max,
		  		'options' => $gradingOptions
		  	];
		}

	  	if (empty($students)) {
	  		$this->Alert->warning($this->aliasField('noStudents'));
	  	}

		if ($action == 'view' || $action == 'edit') {
		  	$attr['type'] = 'element';
			$attr['element'] = 'Institution.Assessment/students';
			$attr['valueClass'] = 'table-full-width';
			$attr['data'] = $students;
			$attr['attr']['assessmentItemObj'] = $assessmentItemObj;
		}

    	return $attr;
    }

    public function viewOnChangeClass(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
    	$request = $this->request;
		unset($request->query['class']);
		unset($request->query['subject']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('class', $request->data[$this->alias()])) {
					$request->query['class'] = $request->data[$this->alias()]['class'];
				}
			}
		}
    }

    public function viewOnChangeSubject(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
    	$request = $this->request;
		unset($request->query['subject']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('subject', $request->data[$this->alias()])) {
					$request->query['subject'] = $request->data[$this->alias()]['subject'];
				}
			}
		}
    }

    public function editOnChangeClass(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
    	$request = $this->request;
		unset($request->query['class']);
		unset($request->query['subject']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('class', $request->data[$this->alias()])) {
					$request->query['class'] = $request->data[$this->alias()]['class'];
				}
			}
		}
    }

    public function editOnChangeSubject(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
    	$request = $this->request;
		unset($request->query['subject']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('subject', $request->data[$this->alias()])) {
					$request->query['subject'] = $request->data[$this->alias()]['subject'];
				}
				if (array_key_exists('students', $request->data[$this->alias()])) {
					$this->request->data[$this->alias()]['students'] = [];
				}
			}
		}
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
    	list(, $selectedStatus) = array_values($this->_getSelectOptions());

    	if ($action == 'view') {
    		if (isset($toolbarButtons['export'])) {
				unset($toolbarButtons['export']);
			}
    	}
    	if ($selectedStatus == self::COMPLETED) {	//Completed
			if ($action == 'view') {
				if (isset($toolbarButtons['edit'])) {
					unset($toolbarButtons['edit']);
				}
			}
		} else {
			if ($action == 'index') {
				if (isset($toolbarButtons['export'])) {
					unset($toolbarButtons['export']);
				}
			}
		}
    }

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		list(, $selectedStatus) = array_values($this->_getSelectOptions());

		if ($selectedStatus == self::NEW_STATUS) {	// New
			unset($buttons['remove']);
		} else if ($selectedStatus == self::COMPLETED) {	// Completed
			unset($buttons['edit']);
		}

		return $buttons;
	}

    public function onUpdateIncludes(Event $event, ArrayObject $includes, $action) {
		$includes['results'] = [
			'include' => true,
			'js' => 'Institution.../js/results'
		];
	}

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		$currentAction = $this->ControllerAction->action();
		if ($currentAction == 'view') {
			unset($buttons[0]);
			unset($buttons[1]);
		} else if ($currentAction == 'edit') {
			$cancelButton = $buttons[1];
			$buttons[0] = [
				'name' => '<i class="fa fa-check"></i> ' . __('Save As Draft'),
				'attr' => ['class' => 'btn btn-default', 'div' => false, 'style' => 'margin-right:10px', 'name' => 'submit', 'value' => 'save', 'onClick' => '$(\'input:hidden[assessment-status=1]\').val(1);']
			];
			$buttons[1] = [
				'name' => '<i class="fa fa-check"></i> ' . __('Submit'),
				'attr' => ['class' => 'btn btn-default', 'div' => false, 'name' => 'submit', 'value' => 'save', 'onClick' => '$(\'input:hidden[assessment-status=1]\').val(2);']
			];
			$buttons[2] = $cancelButton;
		}
	}

	public function _buildRecords($institutionId=null) {
		if (is_null($institutionId)) {
			$session = $this->controller->request->session();
			if ($session->check('Institution.Institutions.id')) {
				$institutionId = $session->read('Institution.Institutions.id');
			}
		}

		// Update all New Assessment to Expired by Institution Id
		$this->updateAll(['status' => self::EXPIRED],
			[
				'institution_id' => $institutionId,
				'status' => self::NEW_STATUS
			]
		);

		$assessments = $this->Assessments
			->find()
			->find('visible')
			->find('order')
			->all();
		$todayDate = date("Y-m-d");

		$AssessmentStatuses = TableRegistry::get('Assessment.AssessmentStatuses');
		$Grades = TableRegistry::get('Institution.InstitutionGrades');

		foreach ($assessments as $assessment) {
			$assessmentId = $assessment->id;
			$gradeId = $assessment->education_grade_id;

			$assessmentStatuses = $AssessmentStatuses
				->find()
				->contain(['AcademicPeriods'])
				->where([
					$AssessmentStatuses->aliasField('assessment_id') => $assessmentId,
					$AssessmentStatuses->aliasField('date_disabled >=') => $todayDate
				])
				->all();

			foreach ($assessmentStatuses as $assessmentStatus) {
				foreach ($assessmentStatus->academic_periods as $academic_period) {
					$academicPeriodId = $academic_period->id;

					// Check whether the school got offer the grade in the academic period
					$gradeResults = $Grades
						->find()
						->find('AcademicPeriod', ['academic_period_id' => $academicPeriodId])
						->where([
							$Grades->aliasField('institution_id') => $institutionId,
							$Grades->aliasField('education_grade_id') => $gradeId
						])
						->all();
					// End

					if (!$gradeResults->isEmpty()) {
						$results = $this
							->find()
							->where([
								$this->aliasField('institution_id') => $institutionId,
								$this->aliasField('academic_period_id') => $academicPeriodId,
								$this->aliasField('assessment_id') => $assessmentId
							])
							->all();

						if ($results->isEmpty()) {
							// Insert New Assessment if not found
							$data = [
								'institution_id' => $institutionId,
								'academic_period_id' => $academicPeriodId,
								'assessment_id' => $assessmentId
							];

							// Set validate = false to bypass validation
							$entity = $this->newEntity($data, ['validate' => false]);

							if ($this->save($entity)) {
							} else {
								$this->log($entity->errors(), 'debug');
							}
						} else {
							// Update Expired Assessment back to New
							$this->updateAll(['status' => self::NEW_STATUS],
								[
									'institution_id' => $institutionId,
									'academic_period_id' => $academicPeriodId,
									'assessment_id' => $assessmentId,
									'status' => self::EXPIRED
								]
							);
						}
					}
				}
			}
		}
	}

	public function _setupFields(Entity $entity) {
		$this->ControllerAction->field('status');
		$this->ControllerAction->field('assessment_id', ['type' => 'select']);
		$this->ControllerAction->field('academic_period_id', ['type' => 'select']);
		// $this->ControllerAction->field('class', ['visible' => ['view' => false, 'edit' => true]]);
		// $this->ControllerAction->field('subject', ['visible' => ['view' => false, 'edit' => true]]);
		// $this->ControllerAction->field('assessment_item_id', ['visible' => ['view' => false, 'edit' => true]]);
		// $this->ControllerAction->field('students', ['visible' => ['view' => false, 'edit' => true]]);
		$this->ControllerAction->field('class');
		$this->ControllerAction->field('subject');
		$this->ControllerAction->field('assessment_item');
		$this->ControllerAction->field('students');

		$this->ControllerAction->setFieldOrder(['status', 'assessment_id', 'academic_period_id', 'class', 'subject', 'assessment_item', 'students']);
	}

	public function _getSelectOptions() {
		//Return all required options and their key
		$statusOptions = $this->getSelectOptions('Assessments.status');
		$selectedStatus = $this->queryString('status', $statusOptions);

		// If do not have access to Assessment - edit but have access to Assessment - view, then set selectedStatus to 2
		if (!$this->AccessControl->check([$this->controller->name, 'Assessments', 'edit'])) {
			if ($this->AccessControl->check([$this->controller->name, 'Assessments', 'index'])) {
				$selectedStatus = self::COMPLETED;
			}
		}

		return compact('statusOptions', 'selectedStatus');
	}
}
