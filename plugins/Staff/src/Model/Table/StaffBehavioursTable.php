<?php
namespace Staff\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class StaffBehavioursTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('StaffBehaviourCategories', ['className' => 'FieldOption.StaffBehaviourCategories']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
	}

	public function beforeAction() {
		$this->ControllerAction->field('academic_period', ['type' => 'select']);
		$this->ControllerAction->field('section', ['type' => 'select']);
		$this->ControllerAction->field('security_user_id', ['type' => 'string']);
		$this->fields['staff_behaviour_category_id']['type'] = 'select';
		$this->fields['academic_period']['visible'] = true;
		$this->fields['section']['visible'] = true;
	}	

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$this->fields['academic_period']['visible'] = false;
		$this->fields['section']['visible'] = false;
		$this->fields['description']['visible'] = false;
		$this->fields['action']['visible'] = false;
		$this->fields['time_of_behaviour']['visible'] = false;

		$this->ControllerAction->setFieldOrder(['date_of_behaviour', 'title', 'staff_behaviour_category_id', 'institution_site_id']);

		//display toolbar only when it's adding/editing behaviours from Institutions
		if($this->controller->name == "Institutions") {
			$toolbarElements = [
				['name' => 'Institution.Behaviours/controls', 'data' => [], 'options' => []]
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

			$Staff = TableRegistry::get('Institution.InstitutionSiteSections');
			$staff = $Staff
						->findAllById($selectedSection)
						->contain(['Staff'])
						->find('list', ['keyField' => 'security_user_id', 'valueField' => 'staff_name'])
						->toArray();
		
			$existingStaff = is_array($staff) ? array_keys($staff) : array();	

			$settings['pagination'] = false;
			$query
				->find('all')
				->contain(['Users'])
			    ->where(function ($exp, $q) use ($existingStaff) {
			        return $exp->in('security_user_id', $existingStaff);	
			    })
			    ->andWhere(['institution_site_id' => $institutionId])
			    ;
		} 
	}


	public function viewBeforeAction(Event $event) {
		$this->fields['academic_period']['visible'] = false;
		$this->fields['section']['visible'] = false;
	}

	public function editBeforeAction(Event $event) {
		$this->fields['academic_period']['visible'] = false;
		$this->fields['section']['visible'] = false;
		$this->ControllerAction->field('security_user_id', ['type' => 'readonly']);
		$this->ControllerAction->setFieldOrder(['security_user_id', 'staff_behaviour_category_id']);
	}

	public function addBeforeAction(Event $event) {
		$this->ControllerAction->field('security_user_id', ['type' => 'select']);
		$this->ControllerAction->setFieldOrder(['academic_period', 'section', 'security_user_id', 'staff_behaviour_category_id']);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function onUpdateFieldAcademicPeriod(Event $event, array $attr, $action, $request) {
		$institutionId = $this->Session->read('Institutions.id');
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$periodOptions = $AcademicPeriod->getList();

		$periodOptions = array();

		$matching = $AcademicPeriod
					->find('all')
					->leftJoin(
						 ['InstitutionSiteSections' => 'institution_site_sections'],	
						[
									'InstitutionSiteSections.academic_period_id = AcademicPeriods.id', 
									'InstitutionSiteSections.institution_site_id' => $institutionId,
										
						])
					->group(['AcademicPeriods.name'])
					->where(['AcademicPeriods.parent_id <> 0'])
					->select(['AcademicPeriods.name', 'InstitutionSiteSections.id', 'AcademicPeriods.id'])	
					->order(['AcademicPeriods.name DESC', 'InstitutionSiteSections.name ASC'])
					;

		foreach($matching as $key=>$academic) {
			$periodOptions[$academic->id] = (!is_null($academic->InstitutionSiteSections['id'])) ? $academic->name : $academic->name." [No Sections]";
		}	

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
			$staff = [];

			$sectionId = key($this->fields['section']['options']);
			if ($request->is('post')) {
				if (isset($request->data[$this->alias()]['section'])) {
					$sectionId = $request->data[$this->alias()]['section'];
				}
				if (!empty($sectionId)) {
					$Staff = TableRegistry::get('Institution.InstitutionSiteSections');
					$staff = $Staff
						->findAllById($sectionId)
						->contain(['Staff'])
						->find('list', ['keyField' => 'security_user_id', 'valueField' => 'staff_name'])
						->toArray();
				}
			}
			
			$attr['options'] = $staff;
		} 
		return $attr;
	}
}