<?php
namespace Student\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;


class StudentBehavioursTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('StudentBehaviourCategories', ['className' => 'FieldOption.StudentBehaviourCategories']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->ControllerAction->field('security_user_id', ['type' => 'readonly', 'attr' => ['value' => $entity->user->name_with_id]]);
	}

	public function editBeforeQuery(Event $event, Query $query) {
		$query->contain(['Users']);
	}

	public function beforeAction() {
		$this->ControllerAction->field('openemis_no', ['type' => 'string']);
		$this->ControllerAction->field('academic_period');
		$this->ControllerAction->field('section');
		$this->ControllerAction->field('security_user_id', ['type' => 'string']);
		$this->ControllerAction->field('student_behaviour_category_id', ['type' => 'select']);
		$this->ControllerAction->field('date_of_behaviour');
		$this->ControllerAction->field('academic_period', ['visible' => true]);
		$this->ControllerAction->field('section', ['visible' => true]);
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$this->ControllerAction->field('academic_period', ['visible' => false]);
		$this->ControllerAction->field('section', ['visible' => false]);
		$this->ControllerAction->field('description', ['visible' => false]);
		$this->ControllerAction->field('action', ['visible' => false]);
		$this->ControllerAction->field('time_of_behaviour', ['visible' => false]);

		$this->ControllerAction->setFieldOrder(['openemis_no', 'security_user_id', 'date_of_behaviour', 'title', 'student_behaviour_category_id', 'institution_site_id']);

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
				'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noSections')),
				'callable' => function($id) use ($Sections, $institutionId) {
					return $Sections->findByInstitutionSiteIdAndAcademicPeriodId($institutionId, $id)->count();
				}
			]);
			// End setup periods
			$start_date = null;
			$end_date = null;
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
				 
				$selectedPeriodEntity = $AcademicPeriod->get($selectedPeriod);
				$start_date =$selectedPeriodEntity->start_date;
				$end_date = $selectedPeriodEntity->end_date;
			}	
			$Students = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
			$students = $Students
							->findAllByInstitutionSiteSectionId($selectedSection)
							->contain(['Users'])
							->find('list', ['keyField' => 'student_id', 'valueField' => 'student_name'])
							->toArray();
		
			$existingStudents = is_array($students) ? array_keys($students) : array();	

			$settings['pagination'] = false;
			$query
				->find('all')
				->contain(['Users'])
			    ->where(function ($exp, $q) use ($existingStudents) {
			        return $exp->in('security_user_id', $existingStudents);	
			    })
			   ->andWhere(['institution_site_id' => $institutionId]);

			if(!is_null($start_date) && !is_null($end_date)){
				$query
					->andWhere([function($exp) use($start_date, $end_date) {
				        return $exp->between('date_of_behaviour', $start_date, $end_date, 'date');
				    }])
			    	;
			}
		} 
	}

	public function viewBeforeAction(Event $event) {
		$this->ControllerAction->field('academic_period', ['visible' => false]);
		$this->ControllerAction->field('section', ['visible' => false]);
		$this->ControllerAction->setFieldOrder(['openemis_no', 'security_user_id', 'student_behaviour_category_id']);
	}

	public function editBeforeAction(Event $event) {
		$this->ControllerAction->field('openemis_no', ['visible' => false]);
		$this->ControllerAction->field('academic_period', ['visible' => false]);
		$this->ControllerAction->field('section', ['visible' => false]);
		$this->ControllerAction->setFieldOrder(['security_user_id', 'student_behaviour_category_id', 'date_of_behaviour', 'time_of_behaviour']);
	}

	public function addBeforeAction(Event $event) {
		$this->ControllerAction->field('openemis_no', ['visible' => false]);
		$this->ControllerAction->field('security_user_id', ['type' => 'select']);
		$this->ControllerAction->setFieldOrder(['academic_period', 'section', 'security_user_id', 'student_behaviour_category_id', 'date_of_behaviour', 'time_of_behaviour']);
	}

	public function validationDefault(Validator $validator) {
		//get start and end date of selected academic period 
		$selectedPeriod = $this->request->query('period');
		if($selectedPeriod) {
			$selectedPeriodEntity = TableRegistry::get('AcademicPeriod.AcademicPeriods')->get($selectedPeriod);
			$startDateFormatted = date_format($selectedPeriodEntity->start_date,'d-m-Y');
			$endDateFormatted = date_format($selectedPeriodEntity->end_date,'d-m-Y');

			$validator
			->add('date_of_behaviour', 
					'ruleCheckInputWithinRange', 
						['rule' => ['checkInputWithinRange', 'date_of_behaviour', $startDateFormatted, $endDateFormatted]]
				
				)
			;
			return $validator;
		}
	}

	public function onUpdateFieldAcademicPeriod(Event $event, array $attr, $action, $request) {
		$institutionId = $this->Session->read('Institutions.id');
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');

		$Sections = TableRegistry::get('Institution.InstitutionSiteSections');

		$periodOptions = $AcademicPeriod->getList();
		$selectedPeriod = $this->queryString('period', $periodOptions);
		$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noSections')),
			'callable' => function($id) use ($Sections, $institutionId) {
				return $Sections
					->find()
					->where([
						$Sections->aliasField('institution_site_id') => $institutionId,
						$Sections->aliasField('academic_period_id') => $id
					])
					->count();
			}
		]);

		$attr['options'] = $periodOptions;
		$attr['onChangeReload'] = true;
		if ($action != 'add') {
			$attr['visible'] = false;
		}
		$attr['type'] = 'select';

		//set start and end dates for date of behaviour based on chosen academic period
		if(!empty($selectedPeriod)) {
			$selectedPeriodEntity = $AcademicPeriod->get($selectedPeriod);
			$startDateFormatted = date_format($selectedPeriodEntity->start_date,'d-m-Y');
			$endDateFormatted = date_format($selectedPeriodEntity->end_date,'d-m-Y');
			$this->ControllerAction->field('date_of_behaviour', [
												'date_options' => [
													'startDate' => $startDateFormatted, 
													'endDate' => $endDateFormatted
												]
											]
										);
		}
		
		return $attr;
	}

	public function onUpdateFieldSection(Event $event, array $attr, $action, $request) {
		$institutionId = $this->Session->read('Institutions.id');
		$selectedPeriod = $this->request->query('period');

		$Sections = TableRegistry::get('Institution.InstitutionSiteSections');
		$Students = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
		$sectionOptions = $Sections
			->find('list')
			->where([
				$Sections->aliasField('institution_site_id') => $institutionId,
				$Sections->aliasField('academic_period_id') => $selectedPeriod
			])
			->order([$Sections->aliasField('section_number') => 'ASC'])
			->toArray();
		$selectedSection = $this->queryString('section', $sectionOptions);
		$this->advancedSelectOptions($sectionOptions, $selectedSection, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
			'callable' => function($id) use ($Students) {
				return $Students
					->find()
					->where([
						$Students->aliasField('institution_site_section_id') => $id
					])
					->count();
			}
		]);

		if ($request->is(['post', 'put'])) {
			$selectedSection = $this->request->data($this->aliasField('section'));
		}
		$request->query['section'] = $selectedSection;

		$attr['options'] = $sectionOptions;
		$attr['onChangeReload'] = true;
		if ($action != 'add') {
			$attr['visible'] = false;
		}
		$attr['type'] = 'select';

		return $attr;
	}

	public function onUpdateFieldSecurityUserId(Event $event, array $attr, $action, $request) {
		if ($action == 'add') {
			$students = [];

			$sectionId = $this->request->query('section');
			if (!empty($sectionId)) {
				$Students = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
				$students = $Students
					->findAllByInstitutionSiteSectionId($sectionId)
					->contain(['Users'])
					->find('list', ['keyField' => 'student_id', 'valueField' => 'student_name'])
					->toArray();
			}
			
			$attr['options'] = $students;
		} 
		return $attr;
	}

	public function onGetOpenemisNo(Event $event, Entity $entity) {
		return $entity->user->openemis_no;
	}
}