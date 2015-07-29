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

	public function editAfterAction(Event $event, Entity $entity) {
		$this->ControllerAction->field('security_user_id', ['type' => 'readonly', 'attr' => ['value' => $entity->user->name_with_id]]);
	}

	public function editBeforeQuery(Event $event, Query $query) {
		$query->contain(['Users']);
	}

	public function beforeAction() {
		$this->ControllerAction->field('openemis_no', ['type' => 'string']);
		$this->ControllerAction->field('academic_period');
		$this->ControllerAction->field('security_user_id', ['type' => 'string']);
		$this->ControllerAction->field('staff_behaviour_category_id', ['type' => 'select']);
		$this->ControllerAction->field('academic_period', ['visible' => true]);
	}	

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$this->ControllerAction->field('academic_period', ['visible' => false]);
		$this->ControllerAction->field('description', ['visible' => false]);
		$this->ControllerAction->field('action', ['visible' => false]);
		$this->ControllerAction->field('time_of_behaviour', ['visible' => false]);

		$this->ControllerAction->setFieldOrder(['openemis_no', 'security_user_id', 'date_of_behaviour', 'title', 'staff_behaviour_category_id', 'institution_site_id']);

		//display toolbar only when it's adding/editing behaviours from Institutions
		if($this->controller->name == "Institutions") {
			$toolbarElements = [
				['name' => 'Institution.Behaviours/controls', 'data' => [], 'options' => []]
			];
			$this->controller->set('toolbarElements', $toolbarElements);

			$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
			$periodOptions = $AcademicPeriod->getList();

			$institutionId = $this->Session->read('Institutions.id');
			$selectedPeriod = $this->queryString('period_id', $periodOptions);
	
			$InstitutionSiteStaff = TableRegistry::get('Institution.InstitutionSiteStaff');

			$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
				'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStaff')),
				'callable' => function($id) use ($InstitutionSiteStaff, $AcademicPeriod, $institutionId) {
					return $InstitutionSiteStaff
								->find('AcademicPeriod', ['academic_period_id' => $id])
								->where(['institution_site_id' => $institutionId])
								->group(['security_user_id'])
								->count();	
				}
			]);

			$start_date = null;
			$end_date = null;
			if ($selectedPeriod != 0) {
				$this->controller->set(compact('periodOptions', 'selectedPeriod'));

				$selectedPeriodEntity = $AcademicPeriod->get($selectedPeriod);
				$start_date =$selectedPeriodEntity->start_date;
				$end_date = $selectedPeriodEntity->end_date;
			}	

			$settings['pagination'] = false;
			$query
				->find('all')
				->contain(['Users'])
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
		$this->ControllerAction->setFieldOrder(['openemis_no', 'security_user_id', 'staff_behaviour_category_id']);
	}

	public function editBeforeAction(Event $event) {
		$this->ControllerAction->field('openemis_no', ['visible' => false]);
		$this->ControllerAction->field('academic_period', ['visible' => false]);
		$this->ControllerAction->setFieldOrder(['security_user_id', 'staff_behaviour_category_id', 'date_of_behaviour', 'time_of_behaviour']);
	}

	public function addBeforeAction(Event $event) {
		$this->ControllerAction->field('openemis_no', ['visible' => false]);
		$this->ControllerAction->field('security_user_id', ['type' => 'select']);
		$this->ControllerAction->setFieldOrder(['academic_period', 'security_user_id', 'staff_behaviour_category_id', 'date_of_behaviour', 'time_of_behaviour']);
	}

	public function onUpdateFieldAcademicPeriod(Event $event, array $attr, $action, $request) {
		$institutionId = $this->Session->read('Institutions.id');
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');

		$periodOptions = $AcademicPeriod->getList();
		$selectedPeriod = $this->queryString('period', $periodOptions);

		$InstitutionSiteStaff = TableRegistry::get('Institution.InstitutionSiteStaff');

		$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStaff')),
			'callable' => function($id) use ($InstitutionSiteStaff, $AcademicPeriod, $institutionId) {
				return $InstitutionSiteStaff
							->find('AcademicPeriod', ['academic_period_id' => $id])
							->where(['institution_site_id' => $institutionId])
							->group(['security_user_id'])
							->count();
			}
		]);
	
		if ($request->is(['post', 'put'])) {
			$selectedPeriod = $this->request->data($this->aliasField('academic_period'));
		}
		$request->query['period'] = $selectedPeriod;

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

	public function onUpdateFieldSecurityUserId(Event $event, array $attr, $action, $request) {
		if ($action == 'add') {
			$staff = [];

			$institutionId = $this->Session->read('Institutions.id');
			$selectedPeriod = $this->request->query['period'];
			$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
			$InstitutionSiteStaff = TableRegistry::get('Institution.InstitutionSiteStaff');
			$selectedPeriodObj = $AcademicPeriod->get($selectedPeriod);

			$staff = $InstitutionSiteStaff
						->find('list', ['keyField' => 'security_user_id', 'valueField' => 'staff_name'])
						->find('AcademicPeriod', ['academic_period_id' => $selectedPeriod])
						->contain(['Users'])
						->where(['institution_site_id' => $institutionId])
						->group(['security_user_id'])
						->toArray();
						
			$attr['options'] = $staff;			

		} 
		return $attr;
	}

	public function onGetOpenemisNo(Event $event, Entity $entity) {
		return $entity->user->openemis_no;
	}

	public function getFormattedDate($given_date){
		return date_format($given_date, 'Y-m-d');
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

}