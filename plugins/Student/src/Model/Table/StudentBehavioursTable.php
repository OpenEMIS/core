<?php
namespace Student\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class StudentBehavioursTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('StudentBehaviourCategories', ['className' => 'FieldOption.StudentBehaviourCategories']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
	}

	public function beforeAction() {
		$this->ControllerAction->field('academic_period');
		$this->ControllerAction->field('section');
		$this->fields['security_user']['type'] = 'select';
		$this->fields['student_behaviour_category_id']['type'] = 'select';

	}	

	public function indexBeforeAction(Event $event) {
		$this->fields['description']['visible'] = false;
		$this->fields['action']['visible'] = false;
		$this->fields['time_of_behaviour']['visible'] = false;

		$this->ControllerAction->setFieldOrder(['date_of_behaviour', 'title', 'student_behaviour_category_id', 'institution_site_id']);

		$toolbarElements = [
			['name' => 'Institution.Attendance/controls', 'data' => [], 'options' => []]
		];
		$this->controller->set('toolbarElements', $toolbarElements);

		// Setup period options
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$periodOptions = $AcademicPeriod->getList();
		
		$Sections = TableRegistry::get('Institution.InstitutionSiteSections');
		$institutionId = $this->Session->read('Institutions.id');
		$selectedPeriod = $this->queryString('period_id', $periodOptions);

		$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
			'message' => '{{label}} - ' . $this->getMessage('general.noSections'),
			'callable' => function($id) use ($Sections, $institutionId) {
				return $Sections->findByInstitutionSiteIdAndAcademicPeriodId($institutionId, $id)->count();
			}
		]);
		// End setup periods

		if ($selectedPeriod != 0) {
			$this->controller->set(compact('periodOptions', 'selectedPeriod'));

			// Setup section options
			$sectionOptions = $Sections
				->find('list')
				->where([
					$Sections->aliasField('institution_site_id') => $institutionId, 
					$Sections->aliasField('academic_period_id') => $selectedPeriod
				])
				->toArray();

			$selectedSection = $this->queryString('section_id', $sectionOptions);
			$this->advancedSelectOptions($sectionOptions, $selectedSection);
			$this->controller->set(compact('sectionOptions', 'selectedSection'));
			// End setup sections
		}	
	}

	public function addEditBeforeAction(Event $event) {
		$this->ControllerAction->setFieldOrder(['academic_period', 'section', 'security_user', 'student_behaviour_category_id']);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function onUpdateFieldAcademicPeriod(Event $event, array $attr, $action, $request) {
		$institutionId = $this->Session->read('Institutions.id');
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$periodOptions = $AcademicPeriod->getList();

		// $periodOptions = array();
		// $matching = $AcademicPeriod
		// 	->find('all')
		// 	->join([
		// 		'table' => 'institution_site_sections',
		// 		'alias' => 'InstitutionSiteSections',
		// 		'type' => 'right',
		// 		'conditions' => ['InstitutionSiteSections.institution_site_id' => $institutionId],
		// 	])
		// 	->contain(['InstitutionSiteSections'])
		// 	->order(['AcademicPeriods.name', 'InstitutionSiteSections.name'])
		// 	; //->select(['InstitutionSiteSections.name', 'AcademicPeriods.id', 'AcademicPeriods.name'])

		// foreach($matching as $key=>$academic) {
		// 	$periodOptions[$academic->id] = (!is_null($academic->institution_site_sections)) ? $academic->name : $academic->name." [No Sections]";
		// }

		// foreach($periodOptions as $key=>$periodOption){
		// 	$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		// 	$sectionsAvailable = $AcademicPeriod->find('list')->autoFields(true)->contain(['InstitutionSiteSections'])->where(['institution_site_sections.institution_site_id' => $key]);
		// 	pr($sectionsAvailable);
		// }


		$attr['options'] = $periodOptions;
		$attr['onChangeReload'] = 'changePeriod';
		if ($action != 'add') {
			$attr['visible'] = false;
		}
		return $attr;
	}

	public function onUpdateFieldSection(Event $event, array $attr, $action, $request) {
		$institutionId = $this->Session->read('Institutions.id');
		$periodId = key($this->fields['academic_period']['options']);

		if ($request->is('post')) {
			$periodId = $this->request->data($this->aliasField('academic_period'));
		}

		$Sections = TableRegistry::get('Institution.InstitutionSiteSections');
		$sectionOptions = $Sections
			->findAllByInstitutionSiteIdAndAcademicPeriodId($institutionId, $periodId)
			->find('list')
			->order([$Sections->aliasField('section_number') => 'ASC'])
			->toArray();

		$attr['options'] = $sectionOptions;
		$attr['onChangeReload'] = 'changeSection';
		if ($action != 'add') {
			$attr['visible'] = false;
		}
		return $attr;
	}

	public function onUpdateFieldSecurityUserId(Event $event, array $attr, $action, $request) {
		if ($action == 'add') {
			$students = [];

			$sectionId = key($this->fields['section']['options']);
			if ($request->is('post')) {
				if (isset($request->data[$this->alias()]['section'])) {
					$sectionId = $request->data[$this->alias()]['section'];
				}
				if (!empty($sectionId)) {
					$Students = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
					$students = $Students
						->findAllByInstitutionSiteSectionId($sectionId)
						->contain(['Users'])
						->find('list', ['keyField' => 'security_user_id', 'valueField' => 'student_name'])
						->toArray();
				}
			}
			
			$attr['options'] = $students;
		}
		return $attr;
	}

	public function addEditOnChangePeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$institutionId = $this->Session->read('Institutions.id');
		$periodId = $data[$this->alias()]['academic_period'];

		$Sections = TableRegistry::get('Institution.InstitutionSiteSections');
		$sectionOptions = $Sections
			->findAllByInstitutionSiteIdAndAcademicPeriodId($institutionId, $periodId)
			->find('list')
			->order([$Sections->aliasField('section_number') => 'ASC'])
			->toArray();

		$this->fields['section']['options'] = $sectionOptions;

		$sectionId = key($sectionOptions);
		$students = [];
		if (!empty($sectionId)) {
			$Students = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
			$students = $Students
				->findAllByInstitutionSiteSectionId($sectionId)
				->contain(['Users'])
				->find('list', ['keyField' => 'security_user_id', 'valueField' => 'student_name'])
				->toArray();
		}
		$this->fields['security_user_id']['options'] = $students;
	}

	public function addEditOnChangeSection(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$sectionId = $data[$this->alias()]['section'];
		
		$Students = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
		$students = $Students
			->findAllByInstitutionSiteSectionId($sectionId)
			->contain(['Users'])
			->find('list', ['keyField' => 'security_user_id', 'valueField' => 'student_name'])
			->toArray();

		$this->fields['security_user_id']['options'] = $students;
	}

}