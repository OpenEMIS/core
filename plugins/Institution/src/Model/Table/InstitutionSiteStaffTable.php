<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use App\Model\Table\AppTable;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;


class InstitutionSiteStaffTable extends AppTable {
	public $fteOptions = array(5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85, 90, 95, 100);

	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Users', 		 ['className' => 'User.Users', 							'foreignKey' => 'security_user_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 			'foreignKey' => 'institution_site_id']);
		$this->belongsTo('Positions', 	 ['className' => 'Institution.InstitutionSitePositions','foreignKey' => 'institution_site_position_id']);
		$this->belongsTo('StaffTypes', 	 ['className' => 'FieldOption.StaffTypes', 				'foreignKey' => 'staff_type_id']);
		$this->belongsTo('StaffStatuses',['className' => 'FieldOption.StaffStatuses', 			'foreignKey' => 'staff_status_id']);

        $this->addBehavior('HighChart', [
        	'number_of_staff' => [
        		'_function' => 'getNumberOfStaff',
				'chart' => ['type' => 'column', 'borderWidth' => 1],
				'xAxis' => ['title' => ['text' => 'Position Type']],
				'yAxis' => ['title' => ['text' => 'Total']]
			]
		]);

	}

	public function findByPosition(Query $query, array $options) {
		if (array_key_exists('InstitutionSitePositions.id', $options)) {
			return $query->where([$this->aliasField('institution_site_position_id') => $options['InstitutionSitePositions.id']]);
		} else {
			return $query;
		}
	}

	public function findByInstitution(Query $query, array $options) {
		if (array_key_exists('Institutions.id', $options)) {
			return $query->where([$this->aliasField('institution_site_id') => $options['Institutions.id']]);
		} else {
			return $query;
		}
	}

	public function findAcademicPeriod(Query $query, array $options) {
		if (array_key_exists('academic_period_id', $options)) {
			$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
			$periodObj = $AcademicPeriods
				->findById($options['academic_period_id'])
				->first();
			$startDate = date('Y-m-d', strtotime($periodObj->start_date));
			$endDate = date('Y-m-d', strtotime($periodObj->end_date));

			$conditions = [];
			$conditions['OR'] = [
				'OR' => [
					[
						$this->aliasField('end_date') . ' IS NOT NULL',
						$this->aliasField('start_date') . ' <=' => $startDate,
						$this->aliasField('end_date') . ' >=' => $startDate
					],
					[
						$this->aliasField('end_date') . ' IS NOT NULL',
						$this->aliasField('start_date') . ' <=' => $endDate,
						$this->aliasField('end_date') . ' >=' => $endDate
					],
					[
						$this->aliasField('end_date') . ' IS NOT NULL',
						$this->aliasField('start_date') . ' >=' => $startDate,
						$this->aliasField('end_date') . ' <=' => $startDate
					]
				],
				[
					$this->aliasField('end_date') . ' IS NULL',
					$this->aliasField('start_date') . ' <=' => $endDate
				]
			];

			return $query->where($conditions);
		} else {
			return $query;
		}
	}

	public function findWithBelongsTo(Query $query, array $options) {
		return $query
			->contain(['Users', 'Institutions', 'Positions', 'StaffTypes', 'StaffStatuses']);
	}

	public function addAfterPatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$timeNow = strtotime("now");
		$sessionVar = $this->alias().'.add.'.strtotime("now");
		$this->Session->write($sessionVar, $this->request->data);

		if (!$entity->errors()) {
			$event->stopPropagation();
			return $this->controller->redirect(['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'add'.'?new='.$timeNow]);
		}
	}
	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		parent::beforeSave($event, $entity, $options);
		
		if ($entity->isNew()) {
			if (isset($entity->FTE)) {
				$entity->FTE = $entity->FTE/100;
			}
		}
	}

	public function validationDefault(Validator $validator) {
		return $validator
			// this function doesnt update... only adds
			->requirePresence('staff_status_id', 'update')
			->add('institution_site_position_id', [
			])
			->add('security_role_id', [
			])
			->add('FTE', [
			])
			->add('staff_type_id', [
			])
		;
	}

	public function getNumberOfStaff($params=[]) {
		$conditions = isset($params['conditions']) ? $params['conditions'] : [];
		$_conditions = [];
		foreach ($conditions as $key => $value) {
			$_conditions['InstitutionSiteStaff.'.$key] = $value;
		}

		$AcademicPeriod = $this->Institutions->InstitutionSiteProgrammes->AcademicPeriods;
		$currentYearId = $AcademicPeriod->getCurrent();
		$currentYear = $AcademicPeriod->get($currentYearId, ['fields'=>'name'])->name;

		$staffsByPositionConditions = ['Genders.name IS NOT NULL'];
		$staffsByPositionConditions = array_merge($staffsByPositionConditions, $_conditions);
		$staffsByPositionConditions['OR'] = array(
			'OR' => array(
				array(
					'InstitutionSiteStaff.end_year IS NOT NULL',
					'InstitutionSiteStaff.start_year <= "' . $currentYear . '"',
					'InstitutionSiteStaff.end_year >= "' . $currentYear . '"'
				)
			),
			array(
				'InstitutionSiteStaff.end_year IS NULL',
				'InstitutionSiteStaff.start_year <= "' . $currentYear . '"'
			)
		);
		$query = $this->find();
		$staffByPositions = $query
			->contain(['Users.Genders','Positions'])
			->select([
				'Positions.type',
				'Users.id',
				'Genders.name',
				'total' => $query->func()->count($this->aliasField('id'))
			])
			->where($staffsByPositionConditions)
			->group([
				'Positions.type', 'Genders.name'
			])
			->order(
				'Positions.type'
			)
			->toArray();

		$positionTypes = array(
			0 => __('Non-Teaching'),
			1 => __('Teaching')
		);

		$genderOptions = $this->Users->Genders->getList();
		$dataSet = array();
		foreach ($genderOptions as $key => $value) {
			$dataSet[$value] = array('name' => __($value), 'data' => []);
		}
		foreach ($dataSet as $key => $obj) {
			foreach ($positionTypes as $id => $name) {
				$dataSet[$key]['data'][$id] = 0;
			}
		}
		foreach ($staffByPositions as $key => $staffByPosition) {
			$positionType = $staffByPosition->position->type;
			$staffGender = $staffByPosition->user->gender->name;
			$StaffTotal = $staffByPosition->total;

			foreach ($dataSet as $dkey => $dvalue) {
				if (!array_key_exists($positionType, $dataSet[$dkey]['data'])) {
					$dataSet[$dkey]['data'][$positionType] = 0;
				}
			}
			$dataSet[$staffGender]['data'][$positionType] = $StaffTotal;
		}

		$params['options']['subtitle'] = array('text' => 'For Year '. $currentYear);
		$params['options']['xAxis']['categories'] = array_values($positionTypes);
		$params['dataSet'] = $dataSet;

		return $params;
	}

}
