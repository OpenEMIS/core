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
		
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Staff', 			['className' => 'User.Users', 						'foreignKey' => 'security_user_id']);
		$this->belongsTo('Shifts', 			['className' => 'Institution.InstitutionSiteShifts','foreignKey' => 'institution_site_shift_id']);
		$this->belongsTo('Institutions', 	['className' => 'Institution.Institutions', 		'foreignKey' => 'institution_site_id']);

		// $this->hasMany('InstitutionSiteSectionGrades', 			['className' => 'Institution.InstitutionSiteSectionGrades']);
		$this->hasMany('InstitutionSiteSectionGrades', 			['className' => 'Institution.InstitutionSiteSectionGrades']);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator;
	}

	// public function onPopulateSelectOptions(Event $event, $query) {
		// $result = onPopulateSelectOptions($event, $query);
		// pr($result);
		// return true;
	// }

    public function addEditBeforeAction($event) {
    	if (array_key_exists('grade_type', $this->ControllerAction->buttons['index']['url'])) {
    		$this->_selectedGradeType = $this->ControllerAction->buttons['index']['url']['grade_type'];
    	}
    	if (array_key_exists($this->alias(), $this->request->data)) {
	    	$_data = $this->request->data[$this->alias()];
			$this->_selectedAcademicPeriodId = $_data['academic_period_id'];

			if ($this->_selectedGradeType == 'single') {
				$this->_selectedEducationGradeId = $_data['education_grade_id'];
				$this->_numberOfSections = $_data['number_of_sections'];
			} elseif ($this->_selectedGradeType == 'multi') {
		    
		    }
	
		}

		$institutionsId = $this->Session->read('Institutions.id');

		/**
		 * default query condition
		 */
		$conditions = array(
			'institution_site_id' => $institutionsId
		);

		/**
		 * academic_period_id field setup
		 */
		$academicPeriodOptions = $this->getAcademicPeriodOptions($conditions);
		if(empty($academicPeriodOptions)) {
			$this->Alert->warning('InstitutionSite.noProgramme');
			return $this->redirect($this->ControllerAction->buttons['index']['url']);
		}else{
			if ($this->_selectedAcademicPeriodId != 0) {
				if (!array_key_exists($this->_selectedAcademicPeriodId, $academicPeriodOptions)) {
					$this->_selectedAcademicPeriodId = key($academicPeriodOptions);
				}
			} else {
				$this->_selectedAcademicPeriodId = key($academicPeriodOptions);
			}
		}
		$this->fields['academic_period_id']['type'] = 'select';
		$this->fields['academic_period_id']['options'] = $academicPeriodOptions;

		/**
		 * education_grade_id field setup
		 */
		$gradeOptions = $this->Institutions->Grades->getInstitutionSiteGradeOptions($institutionsId, $this->_selectedAcademicPeriodId);
		if ($this->_selectedEducationGradeId != 0) {
			if (!array_key_exists($this->_selectedEducationGradeId, $gradeOptions)) {
				$this->_selectedEducationGradeId = key($gradeOptions);
			}
		} else {
			$this->_selectedEducationGradeId = key($gradeOptions);
		}
		$this->fields['education_grade_id']['type'] = 'select';
		$this->fields['education_grade_id']['options'] = $gradeOptions;


		$this->Shifts->createInstitutionDefaultShift($institutionsId, $this->_selectedAcademicPeriodId);
		$shiftOptions = $this->Shifts->getShiftOptions($institutionsId, $this->_selectedAcademicPeriodId);
		$this->fields['institution_site_shift_id']['type'] = 'select';
		$this->fields['institution_site_shift_id']['options'] = $shiftOptions;

		$academicPeriodObj = $this->AcademicPeriods->get($this->_selectedAcademicPeriodId);
		$startDate = $this->AcademicPeriods->getDate($academicPeriodObj->start_date);
        $endDate = $this->AcademicPeriods->getDate($academicPeriodObj->end_date);

		$this->fields['section_number']['visible'] = false;
		$this->fields['security_user_id']['type'] = 'select';
        // TODO-Hanafi: add date conditions as commented below
        // pr($startDate);
        // pr($endDate);
        $Staff = $this->Institutions->Staff;
		$query = $this->Institutions->Staff->find('all')
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
		$staffOptions = $this->getStaffOptions($query->toArray());
		// pr($staffOptions);//die;
		// pr($query->__toString());die;

		if ($this->_selectedGradeType == 'single') {
			$numberOfSectionsOptions = $this->numberOfSectionsOptions();
			$this->ControllerAction->addField('number_of_sections', [
				'type' => 'select', 
				'order' => 5,
				'options' => $numberOfSectionsOptions,
				'onChangeReload' => true
			]);

			$grade = [];
			if ($this->EducationGrades->exists(['id' => $this->_selectedEducationGradeId])) {
				$grade = $this->EducationGrades->get($this->_selectedEducationGradeId, [
				    'contain' => ['EducationProgrammes']
				])->toArray();
			}
			$startingSectionNumber = $this->getNewSectionNumber($institutionsId, $this->_selectedEducationGradeId);	
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
			$this->fields['education_grade_id']['order'] = 2;
			$this->fields['institution_site_shift_id']['order'] = 3;
			$this->fields['section_number']['order'] = 4;
    	} else {
			$this->fields['education_grade_id']['visible'] = false;
			
			$grades = $this->EducationGrades->find()
											->contain(['EducationProgrammes'])
											->toArray();
			$this->ControllerAction->addField('multi_grade_field', [
				'type' => 'element', 
				'order' => 6,
				'element' => 'Institution.Sections/multi_grade',
				'model' => $this->alias(),
				'field' => 'multi_grade_field',
				'data' => $grades
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
						'education_grade_id' => $commonData['education_grade_id'],
						'status' => 1
					];
			}
			$data['InstitutionSiteSections'] = $data['MultiSections'];
			unset($data['MultiSections']);
		} else {
			// pr($data);
			foreach($data['MultiGrades'] as $key => $row) {
				$data['MultiGrades'][$key]['institution_site_shift_id'] = $commonData['institution_site_shift_id'];
				$data['MultiGrades'][$key]['institution_site_id'] = $commonData['institution_site_id'];
				$data['MultiGrades'][$key]['academic_period_id'] = $commonData['academic_period_id'];

				$data['MultiGrades'][$key]['institution_site_section_grades'][0] = [
						'education_grade_id' => $row['id'],
						'status' => 1
					];
				unset($data['MultiGrades'][$key]['id']);
			}
			$data['InstitutionSiteSections'] = $data['MultiGrades'];
			unset($data['MultiGrades']);
		}
		// pr($data);
		// die;
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
			pr($error);
			// $this->log($error, 'debug');
			$this->Alert->error('general.add.failed');
		}
		return false;
	}

	// public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
	// 	if (!empty($entity->id) && $entity->dirty()) { // edit operation
	// 	}
	// }

	// public function addOnInitialize($event, $entity) {
	// 	pr($entity);die;
	// }

	public function addEditAfterAction($event, $entity) {
		$this->fields['academic_period_id']['onChangeReload'] = true;
		$this->fields['education_grade_id']['onChangeReload'] = true;

		if ($entity->academic_period_id == '') {
			$this->fields['academic_period_id']['attr']['value'] = $this->_selectedAcademicPeriodId;
		}
		if ($entity->education_grade_id  == '') {
			$this->fields['education_grade_id']['attr']['value'] = $this->_selectedEducationGradeId;
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

	// public function getSelectOptions() {
	// 	//Return all required options and their key
	// 	$weightingTypeOptions = [];
	// 	foreach ($this->weightingType as $key => $weightingType) {
	// 		$weightingTypeOptions[$weightingType['id']] = __($weightingType['name']);
	// 	}
	// 	$selectedWeightingType = key($weightingTypeOptions);

	// 	return compact('weightingTypeOptions', 'selectedWeightingType');
	// }

	public function beforeAction($event) {

		
		// $this->controller->set('contentHeader', $header);

		// $this->ControllerAction->addField('education_level', ['type' => 'select', 'onChangeReload' => true]);
		// $this->EducationLevels = TableRegistry::get('Education.EducationLevels');

		// if ($this->action == 'add') {
		// 	$tabElements = [
		// 		'single_grade' => [
		// 			'url' => ['controller' => 'Institutions', 'action' => 'Sections', 'add'],
		// 			'text' => __('Single Grade')
		// 		],
		// 		'MultiGradeSections' => [
		// 			'url' => ['controller' => 'Institutions', 'action' => 'MultiGradeSections', 'add'],
		// 			'text' => __('Multi Grades')
		// 		]
		// 	];
			
		// 	$this->fields['academic_period_id']['type'] = 'select';
		// 	$this->fields['academic_period_id']['attr'] = ['onchange' => "$('#reload').click()"];
		// 	$this->fields['academic_period_id']['order'] = 0;

		// 	$this->fields['name']['visible'] = false;
		// 	$this->fields['staff_id']['visible'] = false;
		// 	$this->fields['section_number']['visible'] = false;
		// 	$this->fields['education_grade_id']['type'] = 'select';
		// 	$this->fields['institution_site_shift_id']['type'] = 'select';

		// 	$this->controller->set('tabElements', $tabElements);
		// 	$this->controller->set('selectedGradeType', 'single_grade');
		// }
	}

	public function afterAction($event) {
        $this->controller->set('selectedAction', $this->_selectedGradeType);
	}

	public function getAcademicPeriodOptions( $conditions=[] ) {
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
	
	public function getNewSectionNumber($institutionSiteId, $gradeId) {
		$data = $this->find()
					->where([
						'institution_site_id' => $institutionSiteId,
						'education_grade_id' => $gradeId
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
	
	protected function getStaffOptions($staffObjectsArray) {
		$options = [];
		foreach ($staffObjectsArray as $key => $value) {
			$options[$value->user->id] = $value->user->name;
		}
		return $options;
	}

}
