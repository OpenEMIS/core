<?php
namespace Institution\Model\Table;

use Cake\ORM\Entity;
use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSiteSectionsTable extends AppTable {
	private $weightingType = [
		1 => ['id' => 1, 'name' => 'Points'],
		2 => ['id' => 2, 'name' => 'Percentage']
	];
	private $_selectedGradeType = 'single';
	private $_selectedAcademicPeriodId = 0;
	private $_selectedEducationGradeId = 0;
	private $_numberOfSections = 1;

	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Staff', 			['className' => 'User.Users', 						'foreignKey' => 'security_user_id']);
		$this->belongsTo('Shifts', 			['className' => 'Institution.InstitutionSiteShifts','foreignKey' => 'institution_site_shift_id']);
		$this->belongsTo('Institutions', 	['className' => 'Institution.Institutions', 		'foreignKey' => 'institution_site_id']);

		$this->hasMany('InstitutionSiteSectionGrades', 		['className' => 'Institution.InstitutionSiteSectionGrades']);
		$this->hasMany('InstitutionSiteSectionStudents', 	['className' => 'Institution.InstitutionSiteSectionStudents']);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator;
	}

/******************************************************************************************************************
**
** index action logics
**
******************************************************************************************************************/
    public function indexBeforeAction($event) {
    	if (array_key_exists('grade_type', $this->ControllerAction->buttons['index']['url'])) {
    		unset($this->ControllerAction->buttons['index']['url']['grade_type']);
			$action = $this->ControllerAction->buttons['index']['url'];
			$this->controller->redirect($action);
    	}
    }


/******************************************************************************************************************
**
** add action logics
**
******************************************************************************************************************/
    public function addBeforeAction($event) {
    	if (array_key_exists('grade_type', $this->ControllerAction->buttons['index']['url'])) {
    		$this->_selectedGradeType = $this->ControllerAction->buttons['index']['url']['grade_type'];
    	}
    	if (array_key_exists($this->alias(), $this->request->data)) {
	    	$_data = $this->request->data[$this->alias()];
			$this->_selectedAcademicPeriodId = $_data['academic_period_id'];

			if ($this->_selectedGradeType == 'single') {
				$this->_selectedEducationGradeId = $_data['education_grade'];
				$this->_numberOfSections = $_data['number_of_sections'];
			} elseif ($this->_selectedGradeType == 'multi') {
		    
		    }
	
		}

		$institutionsId = $this->Session->read('Institutions.id');

		/**
		 * academic_period_id field setup
		 */
		$academicPeriodOptions = $this->getAcademicPeriodOptions();
		$this->fields['academic_period_id']['type'] = 'select';
		$this->fields['academic_period_id']['options'] = $academicPeriodOptions;
		$this->fields['academic_period_id']['onChangeReload'] = true;
	
		/**
		 * institution_site_shift_id field setup
		 */
		$this->Shifts->createInstitutionDefaultShift($institutionsId, $this->_selectedAcademicPeriodId);
		$shiftOptions = $this->Shifts->getShiftOptions($institutionsId, $this->_selectedAcademicPeriodId);
		$this->fields['institution_site_shift_id']['type'] = 'select';
		$this->fields['institution_site_shift_id']['options'] = $shiftOptions;

		$academicPeriodObj = $this->AcademicPeriods->get($this->_selectedAcademicPeriodId);
		$startDate = $this->AcademicPeriods->getDate($academicPeriodObj->start_date);
        $endDate = $this->AcademicPeriods->getDate($academicPeriodObj->end_date);

		/**
		 * security_user_id field setup
		 */
		$this->fields['security_user_id']['type'] = 'select';
		$staffOptions = $this->getStaffOptions();
		// pr($staffOptions);//die;
		// pr($query->__toString());die;

		/**
		 * add/edit form setup
		 */
		$this->fields['section_number']['visible'] = false;
		if ($this->_selectedGradeType == 'single') {

			/**
			 * education_grade field setup
			 */
			$gradeOptions = $this->Institutions->InstitutionSiteGrades->getInstitutionSiteGradeOptions($institutionsId, $this->_selectedAcademicPeriodId);
			if ($this->_selectedEducationGradeId != 0) {
				if (!array_key_exists($this->_selectedEducationGradeId, $gradeOptions)) {
					$this->_selectedEducationGradeId = key($gradeOptions);
				}
			} else {
				$this->_selectedEducationGradeId = key($gradeOptions);
			}
			$this->ControllerAction->addField('education_grade', [
				'type' => 'select',
				'options' => $gradeOptions,
				'onChangeReload' => true
			]);

			$numberOfSectionsOptions = $this->numberOfSectionsOptions();
			$this->ControllerAction->addField('number_of_sections', [
				'type' => 'select', 
				'order' => 5,
				'options' => $numberOfSectionsOptions,
				'onChangeReload' => true
			]);

			$grade = [];
			if ($this->InstitutionSiteSectionGrades->EducationGrades->exists(['id' => $this->_selectedEducationGradeId])) {
				$grade = $this->InstitutionSiteSectionGrades->EducationGrades->get($this->_selectedEducationGradeId, [
				    'contain' => ['EducationProgrammes']
				])->toArray();
			}
			// $startingSectionNumber = $this->getNewSectionNumber($institutionsId, $this->_selectedEducationGradeId);	
			$startingSectionNumber = $this->getNewSectionNumber($institutionsId);	
			$this->ControllerAction->addField('single_grade_field', [
				'type' => 'element', 
				'order' => 6,
				'element' => 'Institution.Sections/single_grade',
				'data' => [	'numberOfSections'=>$this->_numberOfSections,
				 			'staffOptions'=>$staffOptions,
				 			'startingSectionNumber'=>$startingSectionNumber,
				 			'grade'=>$grade	]
			]);

			$this->fields['name']['visible'] = false;
			$this->fields['security_user_id']['visible'] = false;

			$this->fields['academic_period_id']['order'] = 1;
			$this->fields['education_grade']['order'] = 2;
			$this->fields['institution_site_shift_id']['order'] = 3;
			$this->fields['section_number']['order'] = 4;
    	} else {

			$gradeOptions = $this->Institutions->InstitutionSiteGrades->getInstitutionSiteGradeOptions($institutionsId, $this->_selectedAcademicPeriodId, false);
			$this->ControllerAction->addField('multi_grade_field', [
				'type' => 'element', 
				'order' => 6,
				'element' => 'Institution.Sections/multi_grade',
				'model' => $this->alias(),
				'field' => 'multi_grade_field',
				'data' => $gradeOptions
			]);

			$this->fields['security_user_id']['options'] = $staffOptions;

			$this->fields['academic_period_id']['order'] = 1;
			$this->fields['name']['order'] = 2;
			$this->fields['institution_site_shift_id']['order'] = 3;
			$this->fields['security_user_id']['order'] = 4;

    	}

		$this->Navigation->addCrumb(ucwords(strtolower($this->action)).' '.ucwords(strtolower($this->_selectedGradeType)).' Grade');

		$tabElements = [
			'single' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Sections', $this->action, 'grade_type'=>'single'],
				'text' => __('Single Grade')
			],
			'multi' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Sections', $this->action, 'grade_type'=>'multi'],
				'text' => __('Multi Grade')
			],
		];
        $this->controller->set('tabElements', $tabElements);
	}

	public function addBeforePatch($event, $entity, $data, $options) {
		$commonData = $data['InstitutionSiteSections'];

		if ($this->_selectedGradeType == 'single') {
			foreach($data['MultiSections'] as $key => $row) {
				$data['MultiSections'][$key]['institution_site_shift_id'] = $commonData['institution_site_shift_id'];
				$data['MultiSections'][$key]['institution_site_id'] = $commonData['institution_site_id'];
				$data['MultiSections'][$key]['academic_period_id'] = $commonData['academic_period_id'];
				$data['MultiSections'][$key]['institution_site_section_grades'][0] = [
						'education_grade_id' => $commonData['education_grade'],
						'status' => 1
					];
			}
			$data['InstitutionSiteSections'] = $data['MultiSections'];
			unset($data['MultiSections']);
			
			$sections = $this->newEntities($data['InstitutionSiteSections']);
			$error = false;
			foreach ($sections as $section) {
			    if ($section->errors()) {
			    	$error = $section->errors();
			    }
			}
			if (!$error) {
				foreach ($sections as $section) {
			    	$this->save($section);
				}
				$this->Alert->success('general.add.success');
				$action = $this->ControllerAction->buttons['index']['url'];
				$this->controller->redirect($action);
			} else {
				// pr($error);
				$this->log($error, 'debug');
				$this->Alert->error('general.add.failed');
			}
			return false;
		} else {
			foreach($data['InstitutionSiteSections']['institution_site_section_grades'] as $key => $row) {
				$data['InstitutionSiteSections']['institution_site_section_grades'][$key]['status'] = 1;
			}
			return compact('entity', 'data', 'options');
		}
	}

	public function addAfterAction($event, $entity) {
        $this->controller->set('selectedAction', $this->_selectedGradeType);

		if ($entity->academic_period_id == '') {
			$this->fields['academic_period_id']['attr']['value'] = $this->_selectedAcademicPeriodId;
		}
		if (isset($entity->education_grade) && $entity->education_grade  == '') {
			$this->fields['education_grade']['attr']['value'] = $this->_selectedEducationGradeId;
		}
		if (isset($entity->number_of_sections)) { 
			if($entity->number_of_sections  == '') {
				$this->fields['number_of_sections']['attr']['value'] = $this->_numberOfSections;
				$this->fields['single_grade_field']['data']['numberOfSections'] = $this->_numberOfSections;
			} else {
				$this->fields['single_grade_field']['data']['numberOfSections'] = $entity->number_of_sections;
			}
		}
	}



/******************************************************************************************************************
**
** edit action logics
**
******************************************************************************************************************/
    public function editBeforeAction($event) {

		$institutionsId = $this->Session->read('Institutions.id');

		/**
		 * academic_period_id field setup
		 */
		$academicPeriodOptions = $this->getAcademicPeriodOptions();
		$this->fields['academic_period_id']['type'] = 'readonly';
		$this->fields['academic_period_id']['options'] = $academicPeriodOptions;
	
		/**
		 * institution_site_shift_id field setup
		 */
		$this->Shifts->createInstitutionDefaultShift($institutionsId, $this->_selectedAcademicPeriodId);
		$shiftOptions = $this->Shifts->getShiftOptions($institutionsId, $this->_selectedAcademicPeriodId);
		$this->fields['institution_site_shift_id']['type'] = 'select';
		$this->fields['institution_site_shift_id']['options'] = $shiftOptions;

		/**
		 * security_user_id field setup
		 */
		$this->fields['security_user_id']['type'] = 'select';
		$staffOptions = $this->getStaffOptions();
		$this->fields['security_user_id']['options'] = $staffOptions;


		$categoryOptions = $this->InstitutionSiteSectionStudents->getStudentCategoryList();
		$this->ControllerAction->addField('students', [
			'label' => '',
			'type' => 'element',
			'element' => 'Institution.Sections/students',
			'data' => [	
				'students'=>[],
				'categoryOptions'=>$categoryOptions,
				'gradeOptions'=>[]
			]
		]);

		$this->fields['section_number']['visible'] = false;

		$this->fields['academic_period_id']['order'] = 1;
		$this->fields['name']['order'] = 2;
		$this->fields['institution_site_shift_id']['order'] = 3;
		$this->fields['security_user_id']['order'] = 4;
		$this->fields['students']['order'] = 5;

	}

	public function editBeforeQuery($event, $query, $contain) {
		$contain = ['Staff', 'InstitutionSiteSectionGrades', 'InstitutionSiteSectionStudents'=>['EducationGrades', 'Users'=>['Genders']]];
		return [$query, $contain];
	}

	public function editBeforePatch($event, $entity, $data, $options) {
		$this->InstitutionSiteSectionStudents->updateAll(['status'=>0], ['institution_site_section_id' => $entity->id]);
		$data['InstitutionSiteSections']['institution_site_section_students'] = $data['InstitutionSiteSectionStudents'];
		unset($data['InstitutionSiteSectionStudents']);
		// pr($data);die;
	// 	$this->InstitutionSiteSectionStudents->deleteAll(['institution_site_section_id' => $entity->id]);
		return compact('entity', 'data', 'options');
	}

	public function editAfterAction($event, $entity) {
		$students = $entity->institution_site_section_students;
		$studentOptions = $this->getStudentsOptions($entity);
		// removing existing students from StudentOptions
		if (count($this->request->data)>0 && $this->request->data['submit']=='add') {
			// clear institution_site_section_students list grab from db
			$students = [];
			if (array_key_exists('InstitutionSiteSectionStudents', $this->request->data)) {
				foreach ($this->request->data['InstitutionSiteSectionStudents'] as $row) {
					if ($row['status'] == 1 && array_key_exists($row['security_user_id'], $studentOptions)) {
						$id = $row['security_user_id'];
						$students[] = $this->createVirtualStudentEntity($id, $entity);
						unset($studentOptions[$id]);
					}
				}
			}
			if (array_key_exists('student_id', $this->request->data)) {
				$id = $this->request->data['student_id'];
				$students[] = $this->createVirtualStudentEntity($id, $entity);
				unset($studentOptions[$id]);
			}
		} else {
			foreach ($entity->institution_site_section_students as $row) {
				if ($row->status == 1 && array_key_exists($row->security_user_id, $studentOptions)) {
					unset($studentOptions[$row->security_user_id]);
				}
			}
		}
		$this->fields['students']['data']['students'] = $students;
		$this->fields['students']['data']['studentOptions'] = $studentOptions;

		$gradeOptions = $this->getSectionGradeOptions($entity);
		$this->fields['students']['data']['gradeOptions'] = $gradeOptions;

	}


/******************************************************************************************************************
**
** view action logics
**
******************************************************************************************************************/
    public function viewBeforeAction($event) {
		$this->fields['section_number']['visible'] = false;
		$this->fields['modified_user_id']['visible'] = false;
		$this->fields['modified']['visible'] = false;
		$this->fields['created_user_id']['visible'] = false;
		$this->fields['created']['visible'] = false;

		$categoryOptions = $this->InstitutionSiteSectionStudents->getStudentCategoryList();
		$studentOptions = [];
		$this->ControllerAction->addField('students', [
			'label' => '',
			'type' => 'element',
			'order' => 6,
			'element' => 'Institution.Sections/students',
			'data' => [	
				'students'=>[],
				'studentOptions'=>$studentOptions,
				'categoryOptions'=>$categoryOptions
			]
		]);

		$this->ControllerAction->addField('education_grades', [
			'type' => 'element',
			'element' => 'Institution.Sections/multi_grade',
			'data' => [	
				'grades'=>[]
			]
		]);
		$this->fields['academic_period_id']['type'] = 'select';
		$this->fields['institution_site_shift_id']['type'] = 'select';


		$this->fields['academic_period_id']['order'] = 1;
		$this->fields['name']['order'] = 2;
		$this->fields['institution_site_shift_id']['order'] = 3;
		$this->fields['education_grades']['order'] = 4;
		$this->fields['security_user_id']['order'] = 5;

	}

	public function viewBeforeQuery($event, $query, $contain) {
		$contain = ['Staff', 'InstitutionSiteSectionGrades', 'InstitutionSiteSectionStudents'=>['EducationGrades', 'Users'=>['Genders']]];
		return [$query, $contain];
	}

	public function viewAfterAction($event, $entity) {
		$this->fields['students']['data']['students'] = $entity->institution_site_section_students;
		$this->fields['education_grades']['data']['grades'] = $entity->institution_site_section_grades;
	}



/******************************************************************************************************************
**
** essential functions
**
******************************************************************************************************************/

	protected function getSectionGradeOptions($entity) {
		$Grade = $this->InstitutionSiteSectionGrades;
		$gradeOptions = $Grade->find()
							->contain('EducationGrades')
							->where([
								$Grade->aliasField('institution_site_section_id') => $entity->id,
								$Grade->aliasField('status') => 1
							])
							->toArray();
		$options = [];
		foreach ($gradeOptions as $key => $value) {
			$options[$value->education_grade->id] = $value->education_grade->name;
		}
		return $options;
	}

	protected function getStudentsOptions($sectionEntity) {
		$institutionsId = $this->Session->read('Institutions.id');
		$academicPeriodObj = $this->AcademicPeriods->get($this->_selectedAcademicPeriodId);
		$startDate = $this->AcademicPeriods->getDate($academicPeriodObj->start_date);
        $endDate = $this->AcademicPeriods->getDate($academicPeriodObj->end_date);

		$sectionGradeObjects = $sectionEntity->institution_site_section_grades;
		$sectionGrades = [];
		foreach ($sectionGradeObjects as $key=>$value) {
			$sectionGrades[] = $value->education_grade_id;
		}

		$students = $this->Institutions->InstitutionSiteStudents;
		$query = $students->find();
		$query = $query->contain(['Users', 'EducationProgrammes'=>['EducationGrades']]);
		$query = $query->where([
				$students->aliasField('institution_site_id') => $institutionsId,
				'OR' => array(
					'OR' => array(
						array(
							$students->aliasField('end_date').' IS NOT NULL',
							$students->aliasField('start_date').' <= "' . $startDate . '"',
							$students->aliasField('end_date').' >= "' . $startDate . '"'
						),
						array(
							$students->aliasField('end_date').' IS NOT NULL',
							$students->aliasField('start_date').' <= "' . $endDate . '"',
							$students->aliasField('end_date').' >= "' . $endDate . '"'
						),
						array(
							$students->aliasField('end_date').' IS NOT NULL',
							$students->aliasField('start_date').' >= "' . $startDate . '"',
							$students->aliasField('end_date').' <= "' . $endDate . '"'
						)
					),
					array(
						$students->aliasField('end_date').' IS NULL',
						$students->aliasField('start_date').' <= "' . $endDate . '"'
					)
				)
			]);

		$list = $query->toArray();
		$studentOptions = ['Add Students'];
		foreach ($list as $skey => $obj) {
			$studentGradeEligible = $obj->education_programme->education_grades;
			$studentGradeKeys = array();
			foreach ($studentGradeEligible as $key => $value) {
				$studentGradeKeys[] = $value->id;
			}

			$studentProgramEligible = false;
			foreach ($studentGradeKeys as $key => $value) {
				if (in_array($value, $sectionGrades)) {
					$studentProgramEligible = true;
				}
			}
			if ($studentProgramEligible) {
				if (isset($obj->user)) {
					$studentOptions[$obj->user->id] = $obj->user->name_with_id;
				} else {
					$this->log('Data corrupted with no security user for student id: '. $obj->id, 'debug');
				}
			}
		}

		$studentOptions = $this->attachSectionInfo($sectionEntity->id, $studentOptions, $institutionsId, $this->_selectedAcademicPeriodId);
		return $studentOptions;
	}

	public function attachSectionInfo($id, $studentOptions, $institutionsId, $periodId) {
		$query = $this->InstitutionSiteSectionStudents->find()
					->contain(['InstitutionSiteSections'])
					->where([
						$this->aliasField('institution_site_id') => $institutionsId,
						$this->aliasField('academic_period_id') => $periodId,
					])
					->where([
							$this->InstitutionSiteSectionStudents->aliasField('security_user_id').' IN' => array_keys($studentOptions),
							$this->InstitutionSiteSectionStudents->aliasField('status') => 1
						]);
		$sectionsWithStudents = $query->toArray();

		foreach($sectionsWithStudents as $student) {
			if($student->institution_site_section_id != $id) {
				if (!isset($studentOptions[$student->institution_site_section->name])) {
					$studentOptions[$student->institution_site_section->name] = ['text' => $student->institution_site_section->name, 'options' => [], 'disabled' => true];
				}
				$studentOptions[$student->institution_site_section->name]['options'][] = ['value' => $student->security_user_id, 'text' => $studentOptions[$student->security_user_id]];
				unset($studentOptions[$student->security_user_id]);
			}
		}
		return $studentOptions;
	}

	protected function getStaffOptions() {
		$institutionsId = $this->Session->read('Institutions.id');
		$academicPeriodObj = $this->AcademicPeriods->get($this->_selectedAcademicPeriodId);
		$startDate = $this->AcademicPeriods->getDate($academicPeriodObj->start_date);
        $endDate = $this->AcademicPeriods->getDate($academicPeriodObj->end_date);

        // TODO-Hanafi: add date conditions as commented below
        // pr($startDate);
        // pr($endDate);
        $Staff = $this->Institutions->Staff;
		$query = $Staff->find('all')
						->find('withBelongsTo')
						->find('byInstitution', ['Institutions.id'=>$institutionsId])
						->where([
							$Staff->aliasField('end_date') . ' IS NULL',
							$Staff->aliasField('start_date') . ' >= ' . $endDate
						])
						// ->where(['OR' => [
						// 			[
						// 				$Staff->aliasField('end_date') . ' IS NOT NULL',
						// 				$Staff->aliasField('start_date') . ' <= ' . $startDate,
						// 				$Staff->aliasField('end_date') . ' >= ' . $startDate
						// 			],
						// 			[
						// 				$Staff->aliasField('end_date') . ' IS NOT NULL',
						// 				$Staff->aliasField('start_date') . ' <= ' . $endDate,
						// 				$Staff->aliasField('end_date') . ' >= ' . $endDate
						// 			],
						// 			[
						// 				$Staff->aliasField('end_date') . ' IS NOT NULL',
						// 				$Staff->aliasField('start_date') . ' >= ' . $startDate,
						// 				$Staff->aliasField('end_date') . ' <= ' . $endDate
						// 			]
						// 		]
						// ])
							;
		$options = [];
		foreach ($query->toArray() as $key => $value) {
			$options[$value->user->id] = $value->user->name;
		}
		return $options;
	}

	public function getAcademicPeriodOptions( $conditions=[] ) {
		$institutionsId = $this->Session->read('Institutions.id');

		$query = $this->Institutions->Programmes->find('all')
												->select(['start_date', 'end_date'])
												->where($conditions)
												;
		$result = $query->toArray();
		$startDateObject = null;
		foreach ($result as $key=>$value) {
			$startDateObject = $this->getLowerDate($startDateObject, $value->start_date);
		}
		if (is_object($startDateObject)) {
			$startDate = $startDateObject->toDateString();
		}

		$endDateObject = null;
		foreach ($result as $key=>$value) {
			$endDateObject = $this->getHigherDate($endDateObject, $value->end_date);
		}
		if (is_object($endDateObject)) {
			$endDate = $endDateObject->toDateString();
		}

		$conditions = array_merge(array('end_date IS NULL'), $conditions);
		$query = $this->Institutions->Programmes->find('all')
												->where($conditions)
												;
		$nullDate = $query->count();

		$academicPeriodConditions = [];
		$academicPeriodConditions['parent_id >'] = 0;
		$academicPeriodConditions['end_date >='] = $startDate;
		if($nullDate == 0) {
			$academicPeriodConditions['start_date <='] = $endDate;
		} else {
			$academicPeriodConditions['end_date >='] = $startDate;
		}

		$query = $this->AcademicPeriods->find('list')
										->select(['id', 'name'])
										->where($academicPeriodConditions)
										->order('`order`')
										;
		$list = $query->toArray();

		if (empty($list)) {
			$this->Alert->warning('InstitutionSite.noProgramme');
			return $this->redirect($this->ControllerAction->buttons['index']['url']);
		} else {
			if ($this->_selectedAcademicPeriodId != 0) {
				if (!array_key_exists($this->_selectedAcademicPeriodId, $list)) {
					$this->_selectedAcademicPeriodId = key($list);
				}
			} else {
				$this->_selectedAcademicPeriodId = key($list);
			}
		}

		return $list;
	}

	protected function getLowerDate($a, $b) {
		if (is_null($a)) {
			return $b;
		}
		return (($a->toUnixString() <= $b->toUnixString()) ? $a : $b);
	}

	protected function getHigherDate($a, $b) {
		if (is_null($a)) {
			return $b;
		}
		return (($a->toUnixString() >= $b->toUnixString()) ? $a : $b);
	}

	public function numberOfSectionsOptions() {
		$total = 10;
		$options = array();
		for($i=1; $i<=$total; $i++){
			$options[$i] = $i;
		}
		
		return $options;
	}
	
	// public function getNewSectionNumber($institutionSiteId, $gradeId) {
	public function getNewSectionNumber($institutionsId) {
		$data = $this->find()
					->where([
						'institution_site_id' => $institutionsId,
						// 'education_grade_id' => $gradeId
					])
					->order(['section_number DESC'])
					->first()
					;
		
		$number = 1;
		if(!empty($data)){
			$number = $data->section_number + 1;
		}
		
		return $number;
	}
	
	protected function createVirtualStudentEntity($id, $entity) {
		$userData = $this->Institutions->InstitutionSiteStudents->find()->contain(['Users'=>['Genders']])->where(['security_user_id'=>$id])->first();
		$data = [
			'security_user_id'=>$id,
			'institution_site_section_id'=>$entity->id,
			'education_grade_id'=>0,
			'student_category_id'=>0,
			'status'=>1,
			'user'=>[]
		];
		$student = $this->InstitutionSiteSectionStudents->newEntity();
		$student = $this->InstitutionSiteSectionStudents->patchEntity($student, $data);
		$student->user = $userData->user;
		return $student;
	}

}
