<?php
namespace Institution\Model\Table;

use DateTime;
use DateInterval;
use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\Collection\Collection;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class InstitutionPositionsTable extends AppTable {
	use OptionsTrait;
	public $CAVersion = '4.0';
	
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
		$this->belongsTo('StaffPositionTitles', ['className' => 'Institution.StaffPositionTitles']);
		$this->belongsTo('StaffPositionGrades', ['className' => 'Institution.StaffPositionGrades']);
		$this->belongsTo('Institutions', 		['className' => 'Institution.Institutions']);

		$this->hasMany('InstitutionStaff', 		['className' => 'Institution.Staff']);
		$this->hasMany('StaffPositions', 		['className' => 'Staff.Positions']);
		$this->hasMany('StaffAttendances', 		['className' => 'Institution.StaffAttendances']);

		$this->addBehavior('OpenEmis.OpenEmis');
		$this->addBehavior('ControllerAction.ControllerAction', [
			'actions' => ['remove' => 'transfer'],
			'fields' => ['excludes' => ['modified_user_id', 'created_user_id']]
		]);
		$this->addBehavior('Institution.InstitutionWorkflowAccessControl');
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Workbench.Model.onGetList'] = 'onGetWorkbenchList';

    	return $events;
    }

	public function validationDefault(Validator $validator) {
		
		$validator
			->add('position_no', 'ruleUnique', [
				'rule' => 'validateUnique', 
				'provider' => 'table'
			])
			
			->add('position_no', 'ruleNoSpaces', [
				'rule' => 'checkNoSpaces',
				'provider' => 'custom'
			]);
		
		return $validator;
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		if ($entity->has('is_homeroom') && $entity->dirty('is_homeroom')) {
			$currIsHomeroom = $entity->is_homeroom;
			// have to find all the staff that is holding this institution position
			$InstitutionStaffTable = $this->InstitutionStaff;
			$staffInvolved = $InstitutionStaffTable->find()
				->where([
					$InstitutionStaffTable->aliasField('institution_position_id') => $entity->id,
					$InstitutionStaffTable->aliasField('security_group_user_id IS NOT NULL')
				])
				->where([
					'OR' => [
						[function ($exp) use ($InstitutionStaffTable) {
							return $exp->gte($InstitutionStaffTable->aliasField('end_date'), $InstitutionStaffTable->find()->func()->now('date'));
						}],
						[$InstitutionStaffTable->aliasField('end_date').' IS NULL']
					]
				])
				;
			if (!empty($staffInvolved)) {
				$SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
				$SecurityRoles = TableRegistry::get('Security.SecurityRoles');
				$homeroomSecurityRoleId = $SecurityRoles->getHomeroomRoleId();
				try {
					$securityGroupId = $this->Institutions->get($entity->institution_id)->security_group_id;
					foreach ($staffInvolved as $key => $value) {
						$homeRoomData = [
							'security_role_id' => $homeroomSecurityRoleId,
							'security_group_id' => $securityGroupId,
							'security_user_id' => $value->staff_id
						];
						if ($currIsHomeroom) {
							// add 1 homeroom value
							$newHomeroomEntity = $SecurityGroupUsers->newEntity($homeRoomData);
							$entity = $SecurityGroupUsers->save($newHomeroomEntity);
						} else {
							// remove homeroom value - find 1 entry and delete it
							$homeroomEntity = $SecurityGroupUsers->find()
								->where($homeRoomData)
								->first();
							if (!empty($homeroomEntity)) {
								$SecurityGroupUsers->delete($homeroomEntity);
							}
						}
					}
				} catch (InvalidPrimaryKeyException $ex) {
					Log::write('error', __METHOD__ . ': ' . $this->Institutions->alias() . ' primary key not found (' . $entity->institution_id . ')');
				}
			}
		}
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('position_no', ['visible' => true]);
		$this->field('is_homeroom', ['visible' => true]);
		$this->field('staff_position_title_id', [
			'visible' => true,
			'type' => 'select'
		]);
		$this->field('staff_position_grade_id', [
			'visible' => true,
			'type' => 'select'
		]);
		$this->field('current_staff_list', [
			'label' => '',
			'override' => true,
			'type' => 'element', 
			'element' => 'Institution.Positions/current',
			'visible' => true
		]);
		$this->field('past_staff_list', [
			'label' => '',
			'override' => true,
			'type' => 'element',
			'element' => 'Institution.Positions/past',
			'visible' => true
		]);
	}

	public function onUpdateFieldPositionNo(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$attr['attr']['value'] = $this->getUniquePositionNo();
			return $attr;
		}
	}

	public function onUpdateFieldIsHomeroom(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->getSelectOptions('general.yesno');
		return $attr;
	}

	public function onGetStaffPositionTitleId(Event $event, Entity $entity) {
		$types = $this->getSelectOptions('Staff.position_types');
   		if ($entity->has('staff_position_title')) {
			return $this->fields['staff_position_title_id']['options'][$entity->staff_position_title->id];
   		}
	}

   	public function onUpdateFieldStaffPositionTitleId(Event $event, array $attr, $action, $request) {
   		$types = $this->getSelectOptions('Staff.position_types');
		$titles = new ArrayObject();
		if (in_array($action, ['add', 'edit'])) {

			$userId = $this->Auth->user('id');
			$institutionId = $this->Session->read('Institution.Institutions.id');
			if ($this->AccessControl->isAdmin()) {
				$userId = null;
				$roles = [];
			} else {
				$roles = $this->Institutions->getInstitutionRoles($userId, $institutionId);
			}
			
			$staffTitleOptions = $this->StaffPositionTitles
					->find()
					->innerJoinWith('SecurityRoles')
					->select([
						'security_role_id' => 'SecurityRoles.id', 
						'name' => $this->StaffPositionTitles->aliasField('name')])
					->order([
						$this->StaffPositionTitles->aliasField('type') => 'DESC', 
						$this->StaffPositionTitles->aliasField('order'),
					])
					->autoFields(true)
				    ->toArray();

			// Filter by role previlege
			$SecurityRolesTable = TableRegistry::get('Security.SecurityRoles');
			$roleOptions = $SecurityRolesTable->getRolesOptions($userId, $roles);
			$roleOptions = array_keys($roleOptions);
			$staffTitleRoles = $this->array_column($staffTitleOptions, 'security_role_id');
			$staffTitleOptions = array_intersect_key($staffTitleOptions, array_intersect($staffTitleRoles, $roleOptions));

			// Adding the opt group
			$titles = [];
			foreach ($staffTitleOptions as $title) {
				$type = __($types[$title->type]);
				$titles[$type][$title->id] = $title->name;
			}
		} else {
			$titles = $this->StaffPositionTitles
				->find()
			    ->where([$this->StaffPositionTitles->aliasField('id').' >' => 1])
			    ->order([$this->StaffPositionTitles->aliasField('order')])
			    ->map(function ($row) use ($types) { // map() is a collection method, it executes the query
			        $row->name_and_type = $row->name . ' - ' . (array_key_exists($row->type, $types) ? $types[$row->type] : $row->type);
			        return $row;
			    })
			    ->combine('id', 'name_and_type') // combine() is another collection method
			    ->toArray(); // Also a collections library method
		}
		$attr['options'] = $titles;
		return $attr;
	}

	public function getUniquePositionNo() {
		$prefix = '';
		$currentStamp = time();
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$institutionCode = $this->Institutions->get($institutionId)->code;
		$prefix .= $institutionCode;
		$newStamp = $currentStamp;
		return $prefix.'-'.$newStamp;
	}


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
	public function indexBeforeAction(Event $event, ArrayObject $extra) {
		$this->fields['current_staff_list']['visible'] = false;
		$this->fields['past_staff_list']['visible'] = false;

		$this->fields['staff_position_title_id']['sort'] = ['field' => 'StaffPositionTitles.order'];
		$this->fields['staff_position_grade_id']['sort'] = ['field' => 'StaffPositionGrades.order'];

		$this->setFieldOrder([
			'position_no', 'staff_position_title_id', 
			'staff_position_grade_id',
		]);

		if ($extra['auto_search']) {
			$search = $this->getSearchKey();
			if (!empty($search)) {
				$extra['OR'] = [$this->StaffPositionTitles->aliasField('name').' LIKE' => '%' . $search . '%'];
			}
		}
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		// pr('model - indexBeforeQuery');
		$extra['auto_contain'] = false;
		$extra['auto_order'] = false;

		$query->contain(['Statuses', 'StaffPositionTitles', 'StaffPositionGrades', 'Institutions'])
			->autoFields(true);

		$sortList = ['position_no', 'StaffPositionTitles.order', 'StaffPositionGrades.order'];
		if (array_key_exists('sortWhitelist', $extra['options'])) {
			$sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
		}
		$extra['options']['sortWhitelist'] = $sortList;
	}

/******************************************************************************************************************
**
** addEdit action methods
**
******************************************************************************************************************/

	public function addEditBeforeAction(Event $event) {

		$this->fields['current_staff_list']['visible'] = false;
		$this->fields['past_staff_list']['visible'] = false;

		$this->setFieldOrder([
			'position_no', 'staff_position_title_id', 
			'staff_position_grade_id',
		]);

	}

/******************************************************************************************************************
**
** view action methods
**
******************************************************************************************************************/

	public function viewBeforeAction(Event $event) {

		$this->setFieldOrder([
			'position_no', 'staff_position_title_id', 
			'staff_position_grade_id',
			'modified_user_id', 'modified', 'created_user_id', 'created',
			'current_staff_list', 'past_staff_list'
		]);

		$session = $this->Session;
		$pass = $this->request->param('pass');
		if (is_array($pass) && !empty($pass)) {
			$id = $pass[1];
		}
		if (!isset($id)) {
			if ($session->check($this->registryAlias() . '.id')) {
				$id = $session->read($this->registryAlias() . '.id');
			}
		}

		if (!isset($id)) {
			die('no position id specified');
		}
		// pr($id);die;
		// start Current Staff List field
		$Staff = $this->Institutions->Staff;
		$currentStaff = $Staff ->findAllByInstitutionIdAndInstitutionPositionId($session->read('Institution.Institutions.id'), $id)
							->where(['('.$Staff->aliasField('end_date').' IS NULL OR ('.$Staff->aliasField('end_date').' IS NOT NULL AND '.$Staff->aliasField('end_date').' >= DATE(NOW())))'])
							->order([$Staff->aliasField('start_date')])
							->find('withBelongsTo');

		$this->fields['current_staff_list']['data'] = $currentStaff;
		$totalCurrentFTE = '0.00';
		if (count($currentStaff)>0) {
			foreach ($currentStaff as $cs) {
				$totalCurrentFTE = number_format((floatVal($totalCurrentFTE) + floatVal($cs->FTE)),2);
			}
		}
		$this->fields['current_staff_list']['totalCurrentFTE'] = $totalCurrentFTE;
		// end Current Staff List field

		// start PAST Staff List field
		$pastStaff = $Staff ->findAllByInstitutionIdAndInstitutionPositionId($session->read('Institution.Institutions.id'), $id)
							->where([$Staff->aliasField('end_date').' IS NOT NULL'])
							->andWhere([$Staff->aliasField('end_date').' < DATE(NOW())'])
							->order([$Staff->aliasField('start_date')])
							->find('withBelongsTo');

		$this->fields['past_staff_list']['data'] = $pastStaff;
		// end Current Staff List field

		return true;
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
** add action methods
**
******************************************************************************************************************/

	public function transferOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $options) {
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$query->where([$this->aliasField('institution_id') => $institutionId]);
	}


/******************************************************************************************************************
**
** essential methods
**
******************************************************************************************************************/
	public function findWithBelongsTo(Query $query, array $options) {
		return $query
			->contain(['StaffPositionTitles', 'Institutions', 'StaffPositionGrades']);
	}

	public function getInstitutionPositions($institutionId, $userId) {

			// // excluding positions where 'InstitutionStaff.end_date is NULL'
			$excludePositions = $this->find('list');
			$excludePositions->matching('InstitutionStaff', function ($q) {
					return $q->where(['InstitutionStaff.end_date is NULL', 'InstitutionStaff.FTE' => 1]);
				});
			$excludePositions->where([$this->aliasField('institution_id') => $institutionId])
				->toArray()
				;
			$excludeArray = [];
			foreach ($excludePositions as $key => $value) {
				$excludeArray[] = $value;
			}

			if ($this->AccessControl->isAdmin()) {
				$userId = null;
				$roles = [];
			} else {
				$roles = $this->Institutions->getInstitutionRoles($userId, $institutionId);
			}
			
			// Filter by active status
			$activeStatusId = $this->Workflow->getStepsByModelCode($this->registryAlias(), 'ACTIVE');
			$positionConditions = [];
			$positionConditions[$this->aliasField('institution_id')] = $institutionId;
			if (!empty($activeStatusId)) {
				$positionConditions[$this->aliasField('status_id').' IN '] = $activeStatusId;
			}
			if (!empty($excludeArray)) {
				$positionConditions[$this->aliasField('id').' NOT IN '] = $excludeArray;
			}
			$staffPositionsOptions = $this
					->find()
					->innerJoinWith('StaffPositionTitles.SecurityRoles')
					->where($positionConditions)
					->select(['security_role_id' => 'SecurityRoles.id', 'type' => 'StaffPositionTitles.type'])
					->order(['StaffPositionTitles.type' => 'DESC', 'StaffPositionTitles.order'])
					->autoFields(true)
				    ->toArray();

			// Filter by role previlege
			$SecurityRolesTable = TableRegistry::get('Security.SecurityRoles');
			$roleOptions = $SecurityRolesTable->getRolesOptions($userId, $roles);
			$roleOptions = array_keys($roleOptions);
			$staffPositionRoles = $this->array_column($staffPositionsOptions, 'security_role_id');
			$staffPositionsOptions = array_intersect_key($staffPositionsOptions, array_intersect($staffPositionRoles, $roleOptions));

			// Adding the opt group
			$types = $this->getSelectOptions('Staff.position_types');
			$options = [];
			foreach ($staffPositionsOptions as $position) {
				$type = __($types[$position->type]);
				$options[$type][$position->id] = $position->name;
			}
	}

	// Workbench.Model.onGetList
	public function onGetWorkbenchList(Event $event, $isAdmin, $institutionRoles, ArrayObject $data) {
		$activestatusIds = $event->subject()->Workflow->getStepsByModelCode($this->registryAlias(), 'ACTIVE');
		$inactivestatusIds = $event->subject()->Workflow->getStepsByModelCode($this->registryAlias(), 'INACTIVE');
		$statusIds = array_merge($activestatusIds, $inactivestatusIds);

		if ($isAdmin) {
			return []; // remove this line once workbench pagination is implemented
		} else {
			$where = [];
			if (empty($institutionRoles)) {
				return [];
			} else {
				$where[$this->aliasField('institution_id') . ' IN '] = array_keys($institutionRoles);
			}
			if (!empty($statusIds)) {
				$where[$this->aliasField('status_id') . ' NOT IN '] = $statusIds;
			}

			$resultSet = $this
				->find()
				->select([
					$this->aliasField('id'),
					$this->aliasField('status_id'),
					$this->aliasField('modified'),
					$this->aliasField('created'),
					'Statuses.name',
					'StaffPositionTitles.name',
					'StaffPositionGrades.name',
					'Institutions.id',
					'Institutions.name',
					'CreatedUser.username'
				])
				->contain(['Statuses', 'StaffPositionTitles', 'StaffPositionGrades', 'Institutions', 'CreatedUser'])
				->where($where)
				->order([$this->aliasField('created')])
				->limit(30)
				->toArray();

			$WorkflowStepsRoles = TableRegistry::get('Workflow.WorkflowStepsRoles');
			// Array to store security roles in each Workflow Step
			$stepRoles = [];
			foreach ($resultSet as $key => $obj) {
				$institutionId = $obj->institution->id;
				$stepId = $obj->status_id;
				$roles = $institutionRoles[$institutionId];

				// Permission
				if (!array_key_exists($stepId, $stepRoles)) {
					$stepRoles[$stepId] = $WorkflowStepsRoles->getRolesByStep($stepId);
				}
				// access is true if user roles exists in step roles
				$hasAccess = count(array_intersect_key($roles, $stepRoles[$stepId])) > 0;
				// End

				if ($hasAccess) {
					$requestTitle = sprintf('%s - %s with %s of %s', $obj->status->name, $obj->staff_position_title->name, $obj->staff_position_grade->name, $obj->institution->name);
					$url = [
						'plugin' => 'Institution',
						'controller' => 'Institutions',
						'action' => 'Positions',
						'view',
						$obj->id,
						'institution_id' => $institutionId
					];

					if (is_null($obj->modified)) {
						$receivedDate = $this->formatDate($obj->created);
					} else {
						$receivedDate = $this->formatDate($obj->modified);
					}

					$data[] = [
						'request_title' => ['title' => $requestTitle, 'url' => $url],
						'receive_date' => $receivedDate,
						'due_date' => '<i class="fa fa-minus"></i>',
						'requester' => $obj->created_user->username,
						'type' => __('Institution > Positions')
					];
				}
			}
		}
	}
}
