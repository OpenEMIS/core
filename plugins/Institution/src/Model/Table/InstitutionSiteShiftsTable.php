<?php
namespace Institution\Model\Table;

use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSiteShiftsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('AcademicPeriods', 		['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Institutions', 			['className' => 'Institution.Institutions', 			'foreignKey' => 'institution_site_id']);
		$this->belongsTo('LocationInstitutionSites',['className' => 'Institution.LocationInstitutionSites']);
	
		$this->hasMany('InstitutionSiteSections', 	['className' => 'Institution.InstitutionSiteSections', 	'foreignKey' => 'institution_site_shift_id']);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator;
	}

	public function beforeAction() {

		$this->fields['name']['type'] = 'string';
		$this->fields['academic_period_id']['type'] = 'select';		
		$this->fields['start_time']['type'] = 'string';
		$this->fields['end_time']['type'] = 'string';


		$this->fields['name']['order'] = 0;
		$this->fields['academic_period_id']['order'] = 1;		
		$this->fields['start_time']['order'] = 2;
		$this->fields['end_time']['order'] = 3;
		$this->fields['location_institution_site_id']['order'] = 4;


		if (strtolower($this->action) != 'index') {
			$this->Navigation->addCrumb($this->getHeader($this->action));
		}
	}

	public function addEditBeforeAction($event) {

		$this->fields['location_institution_site_id']['visible'] = false;

	}

	// public function onPopulateSelectOptions(Event $event, $query) {
		// $query = parent::onPopulateSelectOptions($event, $query);
		// $query->
		// // pr($result->toArray());
		// return $query;
	// }


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
