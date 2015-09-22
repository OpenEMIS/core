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
	private $SubjectStudents = null;
	private $subjectIds = [];
	private $userId = null;

	public function initialize(array $config) {
		$this->table('institution_site_assessments');
		parent::initialize($config);
		
		$this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		$this->addBehavior('AcademicPeriod.AcademicPeriod');
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

	public function onGetToBeCompletedBy(Event $event, Entity $entity) {
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

	public function beforeAction(Event $event) {
		$this->Classes = TableRegistry::get('Institution.InstitutionSiteSections');
		$this->ClassGrades = TableRegistry::get('Institution.InstitutionSiteSectionGrades');
		$this->ClassSubjects = TableRegistry::get('Institution.InstitutionSiteSectionClasses');
		$this->Subjects = TableRegistry::get('Institution.InstitutionSiteClasses');
		$this->SubjectStudents = TableRegistry::get('Institution.InstitutionSiteClassStudents');
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
		$this->_setupFields($entity);
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$this->request->query['status'] = $entity->status;
		$this->request->query['academic_period_id'] = $entity->academic_period_id;
		$this->request->query['assessment_id'] = $entity->assessment_id;
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->_setupFields($entity);
	}

	public function onBeforeDelete(Event $event, ArrayObject $options, $id) {
		$assessmentRecord = $this->get($id);

		if ($assessmentRecord->status == self::COMPLETED) {
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

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		// To clear all records in assessment_item_results when delete from draft
		$AssessmentItems = $this->Assessments->AssessmentItems;
		$itemIds = $AssessmentItems
			->find('list', ['keyField' => 'id', 'valueField' => 'id'])
			->where([$AssessmentItems->aliasField('assessment_id') => $entity->assessment_id])
			->toArray();

		$Results = TableRegistry::get('Assessment.AssessmentItemResults');
		$Results->deleteAll([
			$Results->aliasField('institution_site_id') => $entity->institution_site_id,
			$Results->aliasField('academic_period_id') => $entity->academic_period_id,
			$Results->aliasField('assessment_item_id IN') => $itemIds
		]);
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
    	if ($action == 'edit') {
    		$selectedAssessment = $request->query('assessment_id');
			$assessment = $this->Assessments->get($selectedAssessment);
			$request->query['education_grade_id'] = $assessment->education_grade_id;

    		$AssessmentItems = TableRegistry::get('Assessment.AssessmentItems');
    		$this->subjectIds = $AssessmentItems
    			->find('list', ['keyField' => 'education_subject_id', 'valueField' => 'education_subject_id'])
    			->find('visible')
    			->where([
    				$AssessmentItems->aliasField('assessment_id') => $selectedAssessment
    			])
    			->toArray();

    		if (empty($this->subjectIds)) {
    			$this->Alert->warning($this->aliasField('noSubjects'));
    		}

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
		if ($action == 'edit') {
			$institutionId = $this->institutionId;
			$selectedStatus = $request->query('status');
	    	$selectedPeriod = $request->query('academic_period_id');
	    	$selectedAssessment = $request->query('assessment_id');
	    	$selectedGrade = $request->query('education_grade_id');

    		$classOptions = [];
    		$query = $this->Classes
    			->find('list')
    			->innerJoin(
    				[$this->ClassGrades->alias() => $this->ClassGrades->table()],
    				[
    					$this->ClassGrades->aliasField('institution_site_section_id = ') . $this->Classes->aliasField('id'),
    					$this->ClassGrades->aliasField('education_grade_id') => $selectedGrade
    				]
    			)
    			->innerJoin(
    				[$this->ClassSubjects->alias() => $this->ClassSubjects->table()],
    				[
    					$this->ClassSubjects->aliasField('institution_site_section_id = ') . $this->Classes->aliasField('id')
    				]
    			)
    			->innerJoin(
    				[$this->Subjects->alias() => $this->Subjects->table()],
    				[
    					$this->Subjects->aliasField('id = ') . $this->ClassSubjects->aliasField('institution_site_class_id'),
    					$this->Subjects->aliasField('education_subject_id IN') => $this->subjectIds
    				]
    			)
    			->where([
					$this->Classes->aliasField('institution_site_id') => $institutionId,
					$this->Classes->aliasField('academic_period_id') => $selectedPeriod
    			]);

    		if ($this->AccessControl->check(['Institutions', 'AllClasses', 'index'])) {
    			// User has access to AllClasses
    		} else {
    			$query->where([
					$this->Classes->aliasField('security_user_id') => $this->userId
    			]);
    		}

			$classOptions = $query->toArray();
    		if (empty($classOptions )) {
		  		$this->Alert->warning($this->aliasField('noSections'));
		  	} else {
		  		$selectedClass = $this->queryString('class', $classOptions);
				$this->advancedSelectOptions($classOptions, $selectedClass);
		  	}

    		$attr['type'] = 'select';
    		$attr['attr']['options'] = $classOptions;
    		$attr['onChangeReload'] = 'changeClass';
    	}

    	return $attr;
    }

    public function onUpdateFieldSubject(Event $event, array $attr, $action, Request $request) {
		if ($action == 'edit') {
			$institutionId = $this->institutionId;
			$selectedStatus = $request->query('status');
	    	$selectedPeriod = $request->query('academic_period_id');
	    	$selectedAssessment = $request->query('assessment_id');
	    	$selectedGrade = $request->query('education_grade_id');
	    	$selectedClass = $request->query('class');

	    	// pr('institutionId: ' . $institutionId . ' selectedPeriod: ' . $selectedPeriod . ' selectedAssessment: ' . $selectedAssessment);
	    	// pr('selectedStatus: ' . $selectedStatus . ' selectedGrade: ' . $selectedGrade . ' selectedClass: ' . $selectedClass);die;
	    	// $Classes,$ClassSubjects,$ClassGrades
    		// $Subjects,$SubjectStudents,$subjectIds
    		$subjectOptions = [];

    		if (empty($subjectOptions)) {
		  		$this->Alert->warning($this->aliasField('noClasses'));
		  	}

    		$attr['type'] = 'select';
    		$attr['attr']['options'] = $subjectOptions;
    		$attr['onChangeReload'] = 'changeSubject';
    	}

    	return $attr;
    }

    public function onUpdateFieldStudents(Event $event, array $attr, $action, Request $request) {
		$students = [];

		if (empty($students)) {
	  		$this->Alert->warning($this->aliasField('noStudents'));
	  	}

	  	$attr['type'] = 'element';
		$attr['element'] = 'Institution.Assessment/students';
		$attr['data'] = $students;

    	return $attr;
    }

    public function editOnChangeClass(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
    	$request = $this->request;
		unset($request->query['class']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('class', $request->data[$this->alias()])) {
					$request->query['class'] = $request->data[$this->alias()]['class'];
				}
			}
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
				'institution_site_id' => $institutionId,
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
		$Grades = TableRegistry::get('Institution.InstitutionSiteGrades');

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
							$Grades->aliasField('institution_site_id') => $institutionId,
							$Grades->aliasField('education_grade_id') => $gradeId
						])
						->all();
					// End

					if (!$gradeResults->isEmpty()) {
						$results = $this
							->find()
							->where([
								$this->aliasField('institution_site_id') => $institutionId,
								$this->aliasField('academic_period_id') => $academicPeriodId,
								$this->aliasField('assessment_id') => $assessmentId
							])
							->all();

						if ($results->isEmpty()) {
							// Insert New Assessment if not found
							$data = [
								'institution_site_id' => $institutionId,
								'academic_period_id' => $academicPeriodId,
								'assessment_id' => $assessmentId
							];
							$entity = $this->newEntity($data);

							if ($this->save($entity)) {
							} else {
								$this->log($entity->errors(), 'debug');
							}
						} else {
							// Update Expired Assessment back to New
							$this->updateAll(['status' => self::NEW_STATUS],
								[
									'institution_site_id' => $institutionId,
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
		$this->ControllerAction->field('class', ['visible' => ['view' => false, 'edit' => true]]);
		$this->ControllerAction->field('subject', ['visible' => ['view' => false, 'edit' => true]]);
		$this->ControllerAction->field('students');

		$this->ControllerAction->setFieldOrder(['status', 'assessment_id', 'academic_period_id', 'class', 'subject']);
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
