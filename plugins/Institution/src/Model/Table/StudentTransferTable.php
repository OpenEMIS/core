<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\Controller\Component;
use Cake\I18n\Date;
use App\Model\Table\ControllerActionTable;
use Workflow\Model\Behavior\WorkflowBehavior;


class StudentTransferTable extends ControllerActionTable
{
	private $Grades = null;
	private $GradeStudents = null;
	private $StudentTransfers = null;
	private $Students = null;

	private $institutionClasses = null;
	private $institutionId = null;
	private $currentPeriod = null;
	private $statuses = [];	// Student Status

	public function initialize(array $config)
    {
		$this->table('institution_students');
		parent::initialize($config);
		$this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('PreviousInstitutionStudents', ['className' => 'Institution.Students', 'foreignKey' => 'previous_institution_student_id']);

		$this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
		$this->addBehavior('Institution.ClassStudents');

        $this->toggle('index', false);
        $this->toggle('edit', false);
        $this->toggle('view', false);
	}

	public function validationDefault(Validator $validator)
    {
		$validator = parent::validationDefault($validator);

		return $validator
			->requirePresence('from_academic_period_id')
			->requirePresence('class')
			->requirePresence('education_grade_id')
			->notEmpty('education_grade_id', 'This field is required.')
			->requirePresence('next_academic_period_id')
			->notEmpty('next_academic_period_id', 'This field is required.')
			->requirePresence('next_education_grade_id')
			->notEmpty('next_education_grade_id', 'This field is required.')
			->requirePresence('next_institution_id')
			->notEmpty('next_institution_id', 'This field is required.')
			->requirePresence('student_transfer_reason_id')
			->notEmpty('student_transfer_reason_id', 'This field is required.');
	}

	public function implementedEvents()
    {
    	$events = parent::implementedEvents();
    	$events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
    	return $events;
    }

	public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
		$url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Students'];
		$Navigation->substituteCrumb('Transfer', 'Students', $url);
		$Navigation->addCrumb('Transfer');
	}

	public function beforeAction(Event $event, ArrayObject $extra)
    {
		$this->Grades = TableRegistry::get('Institution.InstitutionGrades');
		$this->GradeStudents = TableRegistry::get('Institution.StudentTransfer');
		$this->StudentTransfers = TableRegistry::get('Institution.InstitutionStudentTransfers');
	    $this->Students = TableRegistry::get('Institution.Students');

	    $institutionClassTable = TableRegistry::get('Institution.InstitutionClasses');
		$this->institutionId = $this->Session->read('Institution.Institutions.id');
		$this->institutionClasses = $institutionClassTable->find('list')
			->where([$institutionClassTable->aliasField('institution_id') => $this->institutionId])
			->toArray();
    	$this->statuses = $this->StudentStatuses->findCodeList();

        // set back button url
        $extra['toolbarButtons']['back']['url'] = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'Students',
            '0' => 'index'
        ];
    }

    public function addOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        // To clear the query string from the previous page to prevent logic conflict on this page
        $this->request->query = [];
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
    	$this->field('student_status_id', ['visible' => false]);
    	$this->field('student_id', ['visible' => false]);
		$this->field('start_date', ['visible' => false]);
		$this->field('end_date', ['visible' => false]);
		$this->field('academic_period_id', ['visible' => false]);
		$this->field('from_academic_period_id');
		$this->field('education_grade_id');
		$this->field('class');
		$this->field('next_academic_period_id');
		$this->field('next_education_grade_id');
		$this->field('area_id');
		$this->field('next_institution_id');
		$this->field('student_transfer_reason_id');
		$this->field('students');

		$this->setFieldOrder([
			'from_academic_period_id', 'education_grade_id', 'class',
			'next_academic_period_id', 'next_education_grade_id', 'area_id', 'next_institution_id', 'student_transfer_reason_id'
		]);
    }

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
    	if (array_key_exists($this->alias(), $requestData)) {
			$nextAcademicPeriodId = null;
            $currentAcademicPeriodId = null;
			$nextEducationGradeId = null;
			$nextInstitutionId = null;
			$studentTransferReasonId = null;
			$currentEducationGradeId = null;

			if (array_key_exists('next_academic_period_id', $requestData[$this->alias()])) {
				$nextAcademicPeriodId = $requestData[$this->alias()]['next_academic_period_id'];
			}
            if (array_key_exists('from_academic_period_id', $requestData[$this->alias()])) {
                $currentAcademicPeriodId = $requestData[$this->alias()]['from_academic_period_id'];
            }
			if (array_key_exists('next_education_grade_id', $requestData[$this->alias()])) {
				$nextEducationGradeId = $requestData[$this->alias()]['next_education_grade_id'];
			}
			if (array_key_exists('next_institution_id', $requestData[$this->alias()])) {
				$nextInstitutionId = $requestData[$this->alias()]['next_institution_id'];
			}
			if (array_key_exists('student_transfer_reason_id', $requestData[$this->alias()])) {
				$studentTransferReasonId = $requestData[$this->alias()]['student_transfer_reason_id'];
			}
			if (array_key_exists('education_grade_id', $requestData[$this->alias()])) {
				$currentEducationGradeId = $requestData[$this->alias()]['education_grade_id'];
			}

			if (!empty($nextAcademicPeriodId) && !empty($currentAcademicPeriodId) && !empty($nextEducationGradeId) && !empty($nextInstitutionId) && !empty($studentTransferReasonId) && !empty($currentEducationGradeId)) {
				if (array_key_exists('students', $requestData[$this->alias()])) {
                    $StudentTransferOut = TableRegistry::get('Institution.StudentTransferOut');
					$institutionId = $requestData[$this->alias()]['institution_id'];

					$tranferCount = 0;
					foreach ($requestData[$this->alias()]['students'] as $key => $studentObj) {
						if (isset($studentObj['selected']) && $studentObj['selected']) {
							unset($studentObj['selected']);
                            $studentObj['status_id'] = WorkflowBehavior::STATUS_OPEN;
                            $studentObj['institution_id'] = $nextInstitutionId;
							$studentObj['academic_period_id'] = $nextAcademicPeriodId;
							$studentObj['education_grade_id'] = $nextEducationGradeId;
							$studentObj['previous_institution_id'] = $institutionId;
                            $studentObj['previous_academic_period_id'] = $currentAcademicPeriodId;
                            $studentObj['previous_education_grade_id'] = $currentEducationGradeId;
                            $studentObj['student_transfer_reason_id'] = $studentTransferReasonId;

							$nextPeriod = $this->AcademicPeriods->get($nextAcademicPeriodId);
							$studentObj['requested_date'] = new Date();

							$entity = $StudentTransferOut->newEntity($studentObj, ['validate' => 'bulkTransfer']);
							if ($StudentTransferOut->save($entity)) {
								$tranferCount++;
							} else {
								$this->log($this->alias() . $entity . print_r($entity->errors(), true), 'error');
								$this->Alert->error('general.add.failed', ['reset' => true]);
							}
						}
					}

					if ($tranferCount == 0) {
						$this->Alert->error('general.notSelected');
					} else {
                        $this->Alert->success($this->aliasField('success'), ['reset' => true]);
                        $url = $this->url('add');
                        $event->stopPropagation();
                        return $this->controller->redirect($url);
                    }
				}
			}
		}
    }

    public function onUpdateFieldFromAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
    	if (isset($request->data[$this->alias()]['from_academic_period_id'])) {
    		$fromAcademicPeriodId = $request->data[$this->alias()]['from_academic_period_id'];
    		if (!empty($fromAcademicPeriodId)) {
    			$this->currentPeriod = $this->AcademicPeriods->get($fromAcademicPeriodId);
    		} else {
    			$this->currentPeriod = null;
    		}
    	} else {
    		$this->currentPeriod = null;
    	}
    	$attr['type'] = 'select';
    	$attr['options'] = $this->AcademicPeriods->getYearList(['isEditable' => true]);
    	$attr['onChangeReload'] = 'ChangeFromAcademicPeriod';

    	return $attr;
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
		$gradeOptions = [];

		if (!is_null($this->currentPeriod)) {
			$Grades = $this->Grades;
			$GradeStudents = $this->GradeStudents;
			$StudentTransfers = $this->StudentTransfers;
			$Students = $this->Students;

	    	$institutionId = $this->institutionId;
	    	$selectedPeriod = $this->currentPeriod->id;
			$statuses = $this->statuses;

			$gradeOptions = $Grades
				->find('list', ['keyField' => 'education_grade_id', 'valueField' => 'education_grade.programme_grade_name'])
				->contain(['EducationGrades'])
				->where([$Grades->aliasField('institution_id') => $institutionId])
				->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
				->toArray();

			$selectedGrade = $request->query('education_grade_id');
            $pendingTransferStatuses = $this->StudentTransfers->getStudentTransferWorkflowStatuses('PENDING');

			$this->advancedSelectOptions($gradeOptions, $selectedGrade, [
				'selectOption' => false,
				'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
				'callable' => function($id) use ($GradeStudents, $StudentTransfers, $Students, $pendingTransferStatuses, $institutionId, $selectedPeriod, $statuses) {
					return $GradeStudents
						->find()
						->leftJoin(
							[$StudentTransfers->alias() => $StudentTransfers->table()],
							[
								$StudentTransfers->aliasField('student_id = ') . $GradeStudents->aliasField('student_id'),
								$StudentTransfers->aliasField('status_id IN ') => $pendingTransferStatuses
							]
						)
						->leftJoin(
							[$Students->alias() => $Students->table()],
							[
								$Students->aliasField('student_id = ') . $GradeStudents->aliasField('student_id'),
								$Students->aliasField('student_status_id') => $statuses['CURRENT']
							]
						)
						->where([
							$this->aliasField('institution_id') => $institutionId,
							$this->aliasField('academic_period_id') => $selectedPeriod,
							$this->aliasField('education_grade_id') => $id,
							$this->aliasField('student_status_id IN') => [$statuses['PROMOTED'], $statuses['GRADUATED']],
							$StudentTransfers->aliasField('student_id IS') => NULL,
							$Students->aliasField('student_id IS') => NULL
						])
						->count();
				}
			]);
		}

    	$attr['options'] = $gradeOptions;
    	$attr['onChangeReload'] = 'changeGrade';

    	return $attr;
    }

    public function onUpdateFieldNextAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
    	$nextPeriodOptions = [];

    	if (!is_null($this->currentPeriod)) {
			$Grades = $this->Grades;
			$institutionId = $this->institutionId;
			$selectedPeriod = $this->currentPeriod->id;
			$periodLevelId = $this->currentPeriod->academic_period_level_id;
			$startDate = $this->currentPeriod->start_date->format('Y-m-d');

			$where = [
				$this->AcademicPeriods->aliasField('id <>') => $selectedPeriod,
				$this->AcademicPeriods->aliasField('academic_period_level_id') => $periodLevelId,
				$this->AcademicPeriods->aliasField('start_date >=') => $startDate
			];

			$nextPeriodOptions = $this->AcademicPeriods
				->find('list')
				->find('visible')
				->find('editable', ['isEditable' => true])
				->find('order')
				->where($where)
				->toArray();

			$nextPeriodId = $request->query('next_academic_period_id');
			$this->advancedSelectOptions($nextPeriodOptions, $nextPeriodId, [
				'selectOption' => false,
				'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noGrades')),
				'callable' => function($id) use ($Grades, $institutionId) {
					return $Grades
						->find()
						->where([$Grades->aliasField('institution_id') => $institutionId])
						->find('academicPeriod', ['academic_period_id' => $id])
						->count();
				}
			]);
		}

		$attr['options'] = $nextPeriodOptions;
    	$attr['onChangeReload'] = 'changeNextPeriod';

    	return $attr;
    }

    public function onUpdateFieldNextEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
		$selectedGrade = $request->query('education_grade_id');
		$nextPeriodId = $request->query('next_academic_period_id');
    	$nextGradeOptions = [];
    	if (!empty($selectedGrade) && $selectedGrade != -1 && !empty($nextPeriodId)) {

			$nextGradeOptions = $this->EducationGrades->getNextAvailableEducationGrades($selectedGrade);

            $nextGradeId = $request->query('next_education_grade_id');

			if (is_null($nextPeriodId)) {
                $nextGradeId = key($nextGradeOptions);
				$this->advancedSelectOptions($nextGradeOptions, $nextGradeId);
			} else {
				$Institutions = $this->Institutions;
				$Grades = $this->Grades;
				$institutionId = $this->institutionId;

				$nextPeriodData = $this->AcademicPeriods->get($nextPeriodId);
				if ($nextPeriodData->start_date instanceof Time || $nextPeriodData->start_date instanceof Date) {
					$nextPeriodStartDate = $nextPeriodData->start_date->format('Y-m-d');
				} else {
					$nextPeriodStartDate = date('Y-m-d', strtotime($nextPeriodData->start_date));
				}

			// 	$this->advancedSelectOptions($nextGradeOptions, $nextGradeId, [
			// 		'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noInstitutions')),
			// 		'callable' => function($id) use ($Institutions, $Grades, $institutionId, $nextPeriodStartDate) {
			// 			return $Institutions
			// 				->find()
			// 				->join([
			// 					'table' => $Grades->table(),
			// 					'alias' => $Grades->alias(),
			// 					'conditions' => [
			// 						$Grades->aliasField('institution_id = ') . $this->Institutions->aliasField('id'),
			// 						$Grades->aliasField('education_grade_id') => $id,
			// 						$Grades->aliasField('start_date <=') => $nextPeriodStartDate,
			// 						'OR' => [
			// 							$Grades->aliasField('end_date IS NULL'),
			// 							$Grades->aliasField('end_date >=') => $nextPeriodStartDate
			// 						]
			// 					]
			// 				])
			// 				->where([$this->Institutions->aliasField('id <>') => $institutionId])
			// 				->count();
			// 		}
			// 	]);
			}
			// $this->request->query['next_education_grade_id'] = $nextGradeId;
    	}

    	$attr['options'] = $nextGradeOptions;
    	$attr['onChangeReload'] = 'changeNextGrade';

    	return $attr;
    }

    public function onUpdateFieldAreaId(Event $event, array $attr, $action, Request $request) {
    	$nextPeriodId = $request->query('next_academic_period_id');
		$nextGradeId = $request->query('next_education_grade_id');
    	$areaOptions = [];

    	if (!is_null($nextPeriodId) && !is_null($nextGradeId)) {
    		$Grades = $this->Grades;
    		$institutionId = $this->institutionId;

    		$nextPeriodData = $this->AcademicPeriods->get($nextPeriodId);
			if ($nextPeriodData->start_date instanceof Time) {
				$nextPeriodStartDate = $nextPeriodData->start_date->format('Y-m-d');
			} else {
				$nextPeriodStartDate = date('Y-m-d', strtotime($nextPeriodData->start_date));
			}

			$Areas = $this->Institutions->Areas;
            $areaOptions = $Areas->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'code_name'
                ])
                ->innerJoinWith('Institutions.InstitutionGrades')
                ->where(['InstitutionGrades.education_grade_id' => $nextGradeId,
                    $this->Institutions->aliasField('id').' <> ' => $institutionId,
                    'InstitutionGrades.start_date <=' => $nextPeriodStartDate,
                    'OR' => [
                            'InstitutionGrades.end_date IS NULL',
                            'InstitutionGrades.end_date >=' => $nextPeriodStartDate
                    ]
                ])
                ->order([$Areas->aliasField('order')])
                ->toArray();
    	}

    	$attr['type'] = 'chosenSelect';
    	$attr['attr']['multiple'] = false;
    	$attr['select'] = true;
    	$attr['options'] = $areaOptions;
    	$attr['onChangeReload'] = true;

    	return $attr;
    }

    public function onUpdateFieldNextInstitutionId(Event $event, array $attr, $action, Request $request)
    {
		$nextPeriodId = $request->query('next_academic_period_id');
		$nextGradeId = $request->query('next_education_grade_id');
        $InstitutionStatuses = TableRegistry::get('Institution.Statuses');

    	$institutionOptions = [];

    	if (!is_null($nextPeriodId) && !is_null($nextGradeId)) {
    		$Grades = $this->Grades;
    		$institutionId = $this->institutionId;

    		$nextPeriodData = $this->AcademicPeriods->get($nextPeriodId);
			if ($nextPeriodData->start_date instanceof Time) {
				$nextPeriodStartDate = $nextPeriodData->start_date->format('Y-m-d');
			} else {
				$nextPeriodStartDate = date('Y-m-d', strtotime($nextPeriodData->start_date));
			}

			$institutionQuery = $this->Institutions
				->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
				->join([
					'table' => $Grades->table(),
					'alias' => $Grades->alias(),
					'conditions' => [
						$Grades->aliasField('institution_id = ') . $this->Institutions->aliasField('id'),
						$Grades->aliasField('education_grade_id') => $nextGradeId,
						$Grades->aliasField('start_date <=') => $nextPeriodStartDate,
						'OR' => [
							$Grades->aliasField('end_date IS NULL'),
							$Grades->aliasField('end_date >=') => $nextPeriodStartDate
						]
					]
				])
				->where([
                    $this->Institutions->aliasField('id <>') => $institutionId,
                    $this->Institutions->aliasField('institution_status_id') => $InstitutionStatuses->getIdByCode('ACTIVE')
                ])
				->order([$this->Institutions->aliasField('code')]);

				if (!empty($request->data[$this->alias()]['area_id'])) {
                    $institutionQuery->where([$this->Institutions->aliasField('area_id') => $request->data[$this->alias()]['area_id']]);
                }

                $institutionOptions = $institutionQuery->toArray();
    	}

    	$attr['attr']['label'] = __('Institution');
    	$attr['type'] = 'chosenSelect';
    	$attr['attr']['multiple'] = false;
    	$attr['select'] = true;
    	$attr['options'] = $institutionOptions;
        $attr['onChangeReload'] = true;

    	return $attr;
    }

    public function onUpdateFieldStudentTransferReasonId(Event $event, array $attr, $action, Request $request)
    {
    	$StudentTransferReasons = TableRegistry::get('Student.StudentTransferReasons');
		$attr['options'] = $StudentTransferReasons->getList()->toArray();
    	return $attr;
    }

    public function onUpdateFieldStudents(Event $event, array $attr, $action, Request $request)
    {
    	$institutionId = $this->institutionId;
    	$selectedGrade = $request->query('education_grade_id');
    	$selectedClass = $request->query('institution_class');
    	$nextEducationGradeId = $request->query('next_education_grade_id');

    	$students = [];
    	if (!empty($selectedGrade) && !is_null($this->currentPeriod)) {
    		$selectedPeriod = $this->currentPeriod->id;
	    	$GradeStudents = $this->GradeStudents;
	    	$statuses = $this->statuses;

			$studentQuery = $this
				->find('byNoExistingTransferRequest')
				->find('byNoEnrolledRecord')
				->find('byNotCompletedGrade', ['gradeId' => $nextEducationGradeId])
				->find('byStatus', ['statuses' => [$statuses['PROMOTED'], $statuses['GRADUATED']]])
                ->find('studentClasses', ['institution_class_id' => $selectedClass])
                ->select(['institution_class_id' => 'InstitutionClassStudents.institution_class_id'])
                ->matching('Users.Genders')
				->where([
					$this->aliasField('institution_id') => $institutionId,
					$this->aliasField('academic_period_id') => $selectedPeriod,
					$this->aliasField('education_grade_id') => $selectedGrade
				])
                ->group($this->aliasField('student_id'))
                ->order(['Users.first_name'])
                ->autoFields(true);
	  		$students = $studentQuery->toArray();

            if (empty($students)) {
                $this->Alert->warning($this->aliasField('noData'));
            }
	  	}

        if (!empty($request->data[$this->alias()]['next_institution_id'])) {
            $nextInstitutionId = $request->data[$this->alias()]['next_institution_id'];
            $institutionGender = $this->Institutions
                                ->find()
                                ->contain('Genders')
                                ->where([
                                    $this->Institutions->aliasField('id') => $nextInstitutionId
                                ])
                                ->select([
                                    'Genders.code',
                                    'Genders.name'
                                ])
                                ->first();
            $attr['nextInstitutionGender'] = $institutionGender->Genders->name;
            $attr['nextInstitutionGenderCode'] = $institutionGender->Genders->code;
        }

		$statusOptions = $this->StudentStatuses->find('list')->toArray();
    	$attr['type'] = 'element';
		$attr['element'] = 'Institution.StudentTransfer/students';
		$attr['attr']['statusOptions'] = $statusOptions;
		$attr['data'] = $students;
		$attr['classOptions'] = $this->institutionClasses;

		return $attr;
    }

    public function onUpdateFieldClass(Event $event, array $attr, $action, Request $request)
    {
    	$attr['type'] = 'select';
    	$attr['options'] = [];
    	if (!is_null($this->currentPeriod)) {
	    	$institutionClass = TableRegistry::get('Institution.InstitutionClasses');
			$institutionId = $this->institutionId;
			$selectedPeriod = $this->currentPeriod->id;
			$educationGradeId = $request->query('education_grade_id');

			$classes = $institutionClass
				->find('list')
				->innerJoinWith('ClassGrades')
				->where([
					$institutionClass->aliasField('academic_period_id') => $selectedPeriod,
					$institutionClass->aliasField('institution_id') => $institutionId,
					'ClassGrades.education_grade_id' => $educationGradeId
				])
				->toArray();
			$options = ['-1' => __('Students without Class')] + $classes;

			$selectedClass = $request->query('institution_class');
			if (empty($selectedClass)) {
				if (!empty($classes)) {
					$selectedClass = key($classes);
				}
			}

			$this->advancedSelectOptions($options, $selectedClass);
			$request->query['institution_class'] = $selectedClass;

			$attr['options'] = $options;
			$attr['select'] = false;
			$attr['onChangeReload'] = 'changeClass';
		}

		return $attr;
    }

	public function addOnChangeClass(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
		unset($this->request->query['institution_class']);

		if ($this->request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $data)) {
				if (array_key_exists('class', $data[$this->alias()])) {
					$this->request->query['institution_class'] = $data[$this->alias()]['class'];
				}
			}
		}
	}

    public function findByNoExistingTransferRequest(Query $query, array $options)
    {
    	$StudentTransfers = $this->StudentTransfers;
        $pendingTransferStatuses = $this->StudentTransfers->getStudentTransferWorkflowStatuses('PENDING');

    	$query->leftJoin(
				[$StudentTransfers->alias() => $StudentTransfers->table()],
				[
					$StudentTransfers->aliasField('student_id = ') . $this->aliasField('student_id'),
					$StudentTransfers->aliasField('status_id IN ') => $pendingTransferStatuses
				]
			)
			->where([$StudentTransfers->aliasField('student_id IS') => NULL]);

		return $query;
    }

    public function findByNoEnrolledRecord(Query $query, array $options) {
    	$Students = $this->Students;
    	$statuses = $this->statuses;
    	$query->leftJoin(
				['StudentEnrolledRecord' => $Students->table()],
				[
					'StudentEnrolledRecord.student_id = ' . $this->aliasField('student_id'),
					'StudentEnrolledRecord.student_status_id' => $statuses['CURRENT']
				]
			)
			->where(['StudentEnrolledRecord.student_id IS' => NULL]);

		return $query;
    }

    public function findByNotCompletedGrade(Query $query, array $options) {
    	$gradeId = array_key_exists('gradeId', $options)? $options['gradeId']: null;
		if (empty($gradeId)) {
			return $query;
		}

    	$Students = $this->Students;
    	$statuses = $this->statuses;
    	$query->leftJoin(
				['StudentCompletedGrade' => $Students->table()],
				[
					'StudentCompletedGrade.student_id = ' . $this->aliasField('student_id'),
					'StudentCompletedGrade.student_status_id IN ' => [$statuses['PROMOTED'], $statuses['GRADUATED']],
					'StudentCompletedGrade.education_grade_id' => $gradeId
				]
			)
			->where(['StudentCompletedGrade.student_id IS' => NULL]);

		return $query;
    }

    public function findByStatus(Query $query, array $options)
    {
    	$statuses = array_key_exists('statuses', $options)? $options['statuses']: null;
		if (empty($statuses)) {
			return $query;
		}
		$statuses = $this->statuses;

		$query->where([
			$this->aliasField('student_status_id IN') => [$statuses['PROMOTED'], $statuses['GRADUATED']]
		]);

		return $query;
    }
    public function addOnChangeFromAcademicPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
    	if (isset($data[$this->alias()]['education_grade_id'])) {
    		unset($data[$this->alias()]['education_grade_id']);
    	}
    	if (isset($data[$this->alias()]['next_academic_period_id'])) {
    		unset($data[$this->alias()]['next_academic_period_id']);
    	}
    }

    public function addOnChangeGrade(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
		unset($this->request->query['education_grade_id']);
		unset($this->request->query['institution_class']);
		unset($this->request->query['next_academic_period_id']);
		unset($this->request->query['next_education_grade_id']);

		if ($this->request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $data)) {
				if (array_key_exists('education_grade_id', $data[$this->alias()])) {
					$this->request->query['education_grade_id'] = $data[$this->alias()]['education_grade_id'];
				}
			}
		}
    }

    public function addOnChangeNextPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
		unset($this->request->query['next_academic_period_id']);
		unset($this->request->query['next_education_grade_id']);

		if ($this->request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $data)) {
				if (array_key_exists('next_academic_period_id', $data[$this->alias()])) {
					$this->request->query['next_academic_period_id'] = $data[$this->alias()]['next_academic_period_id'];
				}
			}
		}
    }

    public function addOnChangeNextGrade(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
		unset($this->request->query['next_education_grade_id']);

		if ($this->request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $data)) {
				if (array_key_exists('next_education_grade_id', $data[$this->alias()])) {
					$this->request->query['next_education_grade_id'] = $data[$this->alias()]['next_education_grade_id'];
				}
			}
		}
    }
}
