<?php
namespace Institution\Model\Table;

use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSiteSectionsTable extends AppTable {
	private $weightingType = [
		1 => ['id' => 1, 'name' => 'Points'],
		2 => ['id' => 2, 'name' => 'Percentage']
	];
	private $_selectedGradeType = 'single';

	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Staff', 			['className' => 'User.Users', 						'foreignKey' => 'security_user_id']);
		$this->belongsTo('Shifts', 			['className' => 'Institution.InstitutionSiteShifts','foreignKey' => 'institution_site_shift_id']);
		$this->belongsTo('Institutions', 	['className' => 'Institution.Institutions', 		'foreignKey' => 'institution_site_id']);
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

		$this->fields['academic_period_id']['type'] = 'select';
		$this->fields['education_grade_id']['type'] = 'select';
		$this->fields['institution_site_shift_id']['type'] = 'select';
		$this->fields['security_user_id']['type'] = 'select';
		
		//Setup fields
		// list($weightingTypeOptions) = array_values($this->getSelectOptions());

		// $this->fields['weighting_type']['type'] = 'select';
		// $this->fields['weighting_type']['options'] = $weightingTypeOptions;

    	if ($this->_selectedGradeType == 'single') {
			$this->fields['name']['visible'] = false;
			$this->fields['security_user_id']['visible'] = false;

			$this->fields['academic_period_id']['order'] = 1;
			$this->fields['education_grade_id']['order'] = 2;
			$this->fields['institution_site_shift_id']['order'] = 3;
			$this->fields['section_number']['order'] = 4;
    	} else {
			$this->fields['education_grade_id']['visible'] = false;
			$this->fields['section_number']['visible'] = false;
			
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

	public function addBeforeAction($event) {
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
		// if ()
		

        $this->controller->set('selectedAction', $this->_selectedGradeType);
	}

}
