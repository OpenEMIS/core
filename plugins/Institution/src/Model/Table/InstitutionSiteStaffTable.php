<?php
namespace Institution\Model\Table;

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

	public function findWithBelongsTo(Query $query, array $options) {
		return $query
			->contain(['Users', 'Institutions', 'Positions', 'StaffTypes', 'StaffStatuses']);
	}

	

	// public function beforeAction() {

	// 	$this->fields['security_user_id']['order'] = 0;
	// 	$this->fields['institution_site_position_id']['order'] = 1;		
	// 	$this->fields['FTE']['order'] = 2;
	// 	$this->fields['start_date']['order'] = 3;
	// 	$this->fields['end_date']['order'] = 4;
	// 	$this->fields['staff_type_id']['order'] = 5;
	// 	$this->fields['staff_status_id']['order'] = 6;

	// }

	public function addBeforeAction(Event $event) {
		$this->ControllerAction->field('institution');
		$this->ControllerAction->field('institution_site_position_id');
		$this->ControllerAction->field('start_date');
		$this->ControllerAction->field('FTE');
		$this->ControllerAction->field('staff_type_id');
		// $this->ControllerAction->field('search');

		$this->fields['start_year']['visible'] = false;
		$this->fields['end_year']['visible'] = false;
		$this->fields['end_date']['visible'] = false;
		$this->fields['staff_status_id']['visible'] = false;

		// initializing to bypass validation - will be modified later when appropriate
		$this->fields['security_user_id']['type'] = 'hidden';
		$this->fields['security_user_id']['value'] = 0;

		$this->ControllerAction->setFieldOrder([
			'institution', 'institution_site_position_id', 'start_date', 'FTE', 'staff_type_id'
			// , 'search'
			]);
	}

	public function addAfterPatch(Event $event, Entity $entity, array $data, array $options) {
		$timeNow = strtotime("now");
		$sessionVar = $this->alias().'.add.'.strtotime("now");
		$this->Session->write($sessionVar, $this->request->data);

		if (!$entity->errors()) {
			$event->stopPropagation();
			return $this->controller->redirect(['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'add'.'?new='.$timeNow]);
		}

		return compact('entity', 'data', 'options');
	}

	public function onUpdateFieldInstitution(Event $event, array $attr, $action, $request) {
		$attr['type'] = 'readonly';

		$institutionsId = $this->Session->read('Institutions.id');
		$result = $this->Institutions
			->find()
			->where([$this->Institutions->primaryKey()=>$institutionsId])
			->first()
		;
		
		if (!empty($result)) {
			$result = $result->toArray();
			$attr['attr']['value'] = $result['name'];
		}
		return $attr;
	}

	public function onUpdateFieldInstitutionSitePositionId(Event $event, array $attr, $action, $request) {
		$institutionsId = $this->Session->read('Institutions.id');

		$InstitutionSitePositions = TableRegistry::get('Institution.InstitutionSitePositions');
		$list = $InstitutionSitePositions->getInstitutionSitePositionList($institutionsId, true);

		$attr['type'] = 'select';
		$attr['options'] = $list;
		$attr['onChangeReload'] = 'true';

		return $attr;
	}

	public function onUpdateFieldStartDate(Event $event, array $attr, $action, $request) {
		$attr['onChangeReload'] = 'true';
		return $attr;
	}

	public function onUpdateFieldFTE(Event $event, array $attr, $action, $request) {
		if (array_key_exists('institution_site_position_id', $this->fields)) {
			if (array_key_exists('options', $this->fields['institution_site_position_id'])) {
				$positionId = key($this->fields['institution_site_position_id']['options']);
				if ($this->request->data($this->aliasField('institution_site_position_id'))) {
					$positionId = $this->request->data($this->aliasField('institution_site_position_id'));
				}
			}
		}

		$startDate = null;
		if ($this->request->data($this->aliasField('start_date'))) {
			$startDate = $this->request->data($this->aliasField('start_date'));
		}

		$attr['type'] = 'select';
		$attr['options'] = $this->getFTEOptions($positionId, ['startDate' => $startDate]);
		return $attr;
	}

	public function onUpdateFieldStaffTypeID(Event $event, array $attr, $action, $request) {
		$attr['type'] = 'select';
		$attr['options'] = $this->StaffTypes->getList();

		return $attr;
	}

	public function getFTEOptions($positionId, $options = []) {
		$options['showAllFTE'] = !empty($options['showAllFTE']) ? $options['showAllFTE'] : false;
		$options['includeSelfNum'] = !empty($options['includeSelfNum']) ? $options['includeSelfNum'] : false;
		$options['FTE_value'] = !empty($options['FTE_value']) ? $options['FTE_value'] : 0;
		$options['startDate'] = !empty($options['startDate']) ? date('Y-m-d', strtotime($options['startDate'])) : null;
		$options['endDate'] = !empty($options['endDate']) ? date('Y-m-d', strtotime($options['endDate'])) : null;
		$currentFTE = !empty($options['currentFTE']) ? $options['currentFTE'] : 0;

		if ($options['showAllFTE']) {
			foreach ($this->fteOptions as $obj) {
				$filterFTEOptions[$obj] = $obj;
			}
		} else {
			$query = $this->find();
			$query->where(['AND' => ['institution_site_position_id' => $positionId]]);

			if (!empty($options['startDate'])) {
				$query->where(['AND' => ['OR' => [
					'end_date >= ' => $options['startDate'],
					'end_date is null'
					]]]);
			}

			if (!empty($options['endDate'])) {
				$query->where(['AND' => ['start_date <= ' => $options['endDate']]]);
			}

			$query->select([
					// todo:mlee unable to implement 'COALESCE(SUM(FTE),0) as totalFTE'
					'totalFTE' => $query->func()->sum('FTE'),
					'institution_site_position_id'
				])
				->group('institution_site_position_id')
			;

			if (is_object($query)) {
				$data = $query->toArray();
				$totalFTE = empty($data[0]->totalFTE) ? 0 : $data[0]->totalFTE * 100;
				$remainingFTE = 100 - intval($totalFTE);
				$remainingFTE = ($remainingFTE < 0) ? 0 : $remainingFTE;

				if ($options['includeSelfNum']) {
					$remainingFTE +=  $options['FTE_value'];
				}
				$highestFTE = (($remainingFTE > $options['FTE_value']) ? $remainingFTE : $options['FTE_value']);

				$filterFTEOptions = [];

				foreach ($this->fteOptions as $obj) {
					if ($highestFTE >= $obj) {
						$objLabel = number_format($obj / 100, 2);
						$filterFTEOptions[$obj] = $objLabel;
					}
				}

				if(!empty($currentFTE) && !in_array($currentFTE, $filterFTEOptions)){
					if($remainingFTE > 0) {
						$newMaxFTE = $currentFTE + $remainingFTE;
					}else{
						$newMaxFTE = $currentFTE;
					}
					
					foreach ($this->fteOptions as $obj) {
						if ($obj <= $newMaxFTE) {
							$objLabel = number_format($obj / 100, 2);
							$filterFTEOptions[$obj] = $objLabel;
						}
					}
				}

			}

			if (count($filterFTEOptions)==0) {
				$filterFTEOptions = array(''=>__('No available FTE'));
			}
		}
		return $filterFTEOptions;
	}

	public function validationDefault(Validator $validator) {
		return $validator
			// this function doesnt update... only adds
			->requirePresence('staff_status_id', 'update')
		;
	}

	


	// public function onUpdateFieldInstitution(Event $event, array $attr, $action, $request) {

	// }	

	// public function addEditBeforeAction($event) {

	// 	$this->fields['start_year']['visible'] = false;
	// 	$this->fields['end_year']['visible'] = false;

	// 	$this->fields['institution_site_position_id']['type'] = 'select';
	// 	$rawData = $this->Positions->find('all')->select(['id', 'position_no']);
	// 	$options = [];
	// 	foreach ($rawData as $rd) {
	// 		$options[$rd['id']] = $rd['position_no'];
	// 	}
	// 	$this->fields['institution_site_position_id']['options'] = $options;

	// 	$this->fields['staff_type_id']['type'] = 'select';
	// 	$this->fields['staff_status_id']['type'] = 'select';
	// }

	// public function editBeforeQuery(Event $event, Query $query, $contain) {
	// 	$contain = ['Users', 'Positions', 'StaffTypes', 'StaffStatuses'];
	// 	return compact('query', 'contain');
	// }

	// public function editOnInitialize($event, $entity) {

	// 		$this->fields['security_user_id']['type'] = 'readonly';
	// 		$this->fields['security_user_id']['attr']['value'] = $entity->user->name;

	// 		$this->fields['institution_site_position_id']['type'] = 'readonly';

	// 		$this->fields['FTE']['type'] = 'readonly';

	// 		$this->fields['start_date']['type'] = 'readonly';
	// 		$this->fields['start_date']['attr']['value'] = $this->formatDateTime($entity->start_date);

	// }

	// public function editAfterAction($event) {
	// 	pr($this->fields['staff_type_id']);
	// 	pr($this->fields['staff_status_id']);
	// }
}
