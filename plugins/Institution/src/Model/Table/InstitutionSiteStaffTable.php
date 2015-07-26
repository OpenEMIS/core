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

		$this->addBehavior('AcademicPeriod.Period');
        $this->addBehavior('HighChart', [
        	'number_of_staff' => [
        		'_function' => 'getNumberOfStaff',
				'chart' => ['type' => 'column', 'borderWidth' => 1],
				'xAxis' => ['title' => ['text' => 'Position Type']],
				'yAxis' => ['title' => ['text' => 'Total']]
			],
			'institution_site_staff_gender' => [
				'_function' => 'getNumberOfStaffsByGender'
			]
		]);

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

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		parent::beforeSave($event, $entity, $options);
		
		if ($entity->isNew()) {
			if (isset($entity->FTE)) {
				$entity->FTE = $entity->FTE/100;
			}
		}
	}


/******************************************************************************************************************
**
** add action functions
**
******************************************************************************************************************/
	public function addAfterPatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$timeNow = strtotime("now");
		$sessionVar = $this->alias().'.add.'.strtotime("now");
		$this->Session->write($sessionVar, $this->request->data);

		if (!$entity->errors()) {
			$event->stopPropagation();
			return $this->controller->redirect(['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'add'.'?new='.$timeNow]);
		}
	}


/******************************************************************************************************************
**
** finders functions to be used with query
**
******************************************************************************************************************/
	/**
	 * $options['type'] == 0 > non-teaching
	 * $options['type'] == 1 > teaching
	 * refer to OptionsTrait
	 */
	public function findByPositions(Query $query, array $options) {
		if (array_key_exists('Institutions.id', $options) && array_key_exists('type', $options)) {
			$positions = $this->Positions->find('list')
						->find('withBelongsTo')
				        ->where([
				        	'Institutions.id' => $options['Institutions.id'],
				        	$this->Positions->aliasField('type') => $options['type']
				        ])
				        ->toArray();
			$positions = array_keys($positions);
			return $query->where([$this->aliasField('institution_site_position_id IN') => $positions]);
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

	/**
	 * currently available values:
	 * 	Full-Time
	 * 	Part-Time
	 * 	Contract
	 */
	public function findByType(Query $query, array $options) {
		if (array_key_exists('type', $options)) {
			$types = $this->StaffTypes->getList()->toArray();
			if (is_array($types) && in_array($options['type'], $types)) {
				$typeId = array_search($options['type'], $types);
				return $query->where([$this->aliasField('staff_type_id') => $typeId]);
			} else {
				return $query;
			}
		} else {
			return $query;
		}
	}

	/**
	 * currently available values:
	 * 	Current
	 * 	Transferred
	 * 	Resigned
	 * 	Leave
	 * 	Terminated
	 */
	public function findByStatus(Query $query, array $options) {
		if (array_key_exists('status', $options)) {
			$statuses = $this->StaffStatuses->getList()->toArray();
			if (is_array($statuses) && in_array($options['status'], $statuses)) {
				$statusId = array_search($options['status'], $statuses);
				return $query->where([$this->aliasField('staff_status_id') => $statusId]);
			} else {
				return $query;
			}
		} else {
			return $query;
		}
	}

	public function findWithBelongsTo(Query $query, array $options) {
		return $query
			->contain(['Users', 'Institutions', 'Positions', 'StaffTypes', 'StaffStatuses']);
	}


/******************************************************************************************************************
**
** essential functions
**
******************************************************************************************************************/
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
			if ($staffByPosition->has('position')) {
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
		}

		$params['options']['subtitle'] = array('text' => 'For Year '. $currentYear);
		$params['options']['xAxis']['categories'] = array_values($positionTypes);
		$params['dataSet'] = $dataSet;

		return $params;
	}

	public function getNumberOfStaffsByGender($params=[]) {

			$institutionSiteRecords = $this->find();
			
			$institutionSiteStaffCount = $institutionSiteRecords
				->contain(['Users', 'Users.Genders'])
				->select([
					'count' => $institutionSiteRecords->func()->count('security_user_id'),	
					'gender' => 'Genders.name'
				])
				->group('gender_id');

			if (!empty($params)) {
				$institutionSiteStaffCount->where(['institution_site_id' => $params['institution_site_id']]);
			}	

			$modelId = 'gender_id';
			// Creating the data set		
			$dataSet = [];
			foreach ($institutionSiteStaffCount->toArray() as $value) {
				//To get the name from the array
				$text = $modelId;
	            if (substr($text, -3) === '_id') {
	                $text = substr($text, 0, -3);
	            }

	            //Compile the dataset
				$dataSet[] = [$value[$text], $value['count']];
			}
			$params['dataSet'] = $dataSet;
		//}
		return $params;
	}

}
