<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

use App\Model\Table\AppTable;

class InstitutionSiteShiftsTable extends AppTable {
	public $institutionId = 0;

	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('AcademicPeriods', 		['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Institutions', 			['className' => 'Institution.Institutions', 			'foreignKey' => 'institution_site_id']);
		$this->belongsTo('LocationInstitutionSites',['className' => 'Institution.LocationInstitutionSites']);
	
		$this->hasMany('InstitutionSiteSections', 	['className' => 'Institution.InstitutionSiteSections', 	'foreignKey' => 'institution_site_shift_id', 'dependent' => true, 'cascadeCallbacks' => true]);
	}

	public function validationDefault(Validator $validator) {
		$validator
 	        ->add('start_time', 'ruleCompareDate', [
		            'rule' => ['compareDate', 'end_time', false]
	    	    ])
 	        ->add('end_time', 'ruleCompareDateReverse', [
		            'rule' => ['compareDateReverse', 'start_time', false]
	    	    ])
	        ;
		return $validator;
	}

	public function beforeAction() {
		$this->ControllerAction->field('start_time', ['type' => 'string', 'visible' => false]);
		$this->ControllerAction->field('end_time', ['type' => 'string', 'visible' => false]);

		$this->ControllerAction->field('academic_period_id', ['type' => 'select']);
		$this->ControllerAction->field('name', ['type' => 'string']);
		$this->ControllerAction->field('period', ['type' => 'string']);
		$this->ControllerAction->field('location_institution_site_id', ['type' => 'select']);
		
		$this->ControllerAction->field('other_school_id', ['visible' => false]);

		$this->ControllerAction->setFieldOrder([
			'academic_period_id', 'name', 'period', 'location_institution_site_id', 'other_school_id'
		]);
	}


	// public function onPopulateSelectOptions(Event $event, $query) {
		// $query = parent::onPopulateSelectOptions($event, $query);
		// $query->
		// // pr($result->toArray());
		// return $query;
	// }

/******************************************************************************************************************
**
** view action methods
**
******************************************************************************************************************/

	public function viewBeforeAction($event) {
		$this->fields['period']['visible'] = false;

		$this->fields['start_time']['visible'] = true;
		$this->fields['end_time']['visible'] = true;

		$this->ControllerAction->setFieldOrder([
			'name', 'academic_period_id', 'start_time', 'end_time', 'location_institution_site_id',
		]);

	}

    public function viewAfterAction(Event $event, Entity $entity) {
    	$this->fields['created_user_id']['options'] = [$entity->created_user_id => $entity->created_user->name];
    	if (!empty($entity->modified_user_id)) {
	    	$this->fields['modified_user_id']['options'] = [$entity->modified_user_id => $entity->modified_user->name];
	    }
		return $entity;
    }


/******************************************************************************************************************
**
** addEdit action methods
**
******************************************************************************************************************/
	public function addBeforeAction(Event $event) {
		//get institution id if it's not empty
		$options = [];
		$session = $this->request->session();
		if ($session->check('Institutions.id')) {
			$id = $session->read('Institutions.id');
			$Institution = TableRegistry::get('Institution.Institutions')->findById($id)->first();
			$institutionName = $Institution->name;
			$options[$id] = $institutionName;
		}
		$options[0] = 'Other School';
		$this->ControllerAction->field('location_institution_site_id', ['visible' => true, 'type' => 'select', 'attr' => ['options' => $options]]);
	}

	public function addEditBeforeAction(Event $event) {
		$this->ControllerAction->field('period', ['visible' => false]);
		$this->ControllerAction->field('start_time', ['visible' => true, 'type' => 'time']);
		$this->ControllerAction->field('end_time', ['visible' => true, 'type' => 'time']);
		
		$this->ControllerAction->field('other_school_id', ['visible' => false]);
		$this->ControllerAction->setFieldOrder([
			'name', 'academic_period_id', 'start_time', 'end_time', 'location_institution_site_id', 'other_school_id'
		]);

	}

	public function onUpdateFieldLocationInstitutionSiteId(Event $event, array $attr, $action, $request) {
		$this->ControllerAction->field('other_school_id', ['visible' => true, 'type' => 'select']);
	}

/******************************************************************************************************************
**
** field specific methods
**
******************************************************************************************************************/

	public function onGetPeriod(Event $event, Entity $entity) {
		return $entity->start_time . ' - ' .$entity->end_time;
	}


/******************************************************************************************************************
**
** essential methods
**
******************************************************************************************************************/
	public function createInstitutionDefaultShift($institutionsId, $academicPeriodId){
		$data = $this->getShifts($institutionsId, $academicPeriodId);

		if (empty($data)) {			
			$schoolAcademicPeriod = $this->AcademicPeriods->get($academicPeriodId);

			$ConfigItem = TableRegistry::get('ConfigItems');
			$settingStartTime = $ConfigItem->value('start_time');
			$hoursPerDay = intval($ConfigItem->value('hours_per_day'));
			if ($hoursPerDay > 1) {
				$endTimeStamp = strtotime('+' . $hoursPerDay . ' hours', strtotime($settingStartTime));
			} else {
				$endTimeStamp = strtotime('+' . $hoursPerDay . ' hour', strtotime($settingStartTime));
			}
			$endTime = date('h:i A', $endTimeStamp);

			$defaultShift = [
				'name' => __('Default') . ' ' . __('Shift') . ' ' . $schoolAcademicPeriod->name,
				'academic_period_id' => $academicPeriodId,
				'start_time' => $settingStartTime,
				'end_time' => $endTime,
				'institution_site_id' => $institutionsId,
				'location_institution_site_id' => $institutionsId,
				'location_institution_site_name' => 'Institution Site Name (Shift Location)'
			];

			$data = $this->newEntity();
			$data = $this->patchEntity($data, $defaultShift);
			$this->save($data);
		}
	}

	public function getShifts($institutionsId, $periodId) {
		$conditions = [
			'institution_site_id' => $institutionsId,
			'academic_period_id' => $periodId
		];
		$query = $this->find('all');
		// $query->contain(['Institutions', 'LocationInstitutionSites', 'AcademicPeriods']);
		$query->where($conditions);
		$data = $query->toArray();
		return $data;
	}
	
	public function getShiftOptions($institutionsId, $periodId) {
		$query = $this->find('all')
					->where([
						'institution_site_id' => $institutionsId,
						'academic_period_id' => $periodId
					])
					;
		$data = $query->toArray();

		$list = [];
		foreach ($data as $key => $obj) {
			$list[$obj->id] = $obj->name;
		}

		return $list;
	}

}
