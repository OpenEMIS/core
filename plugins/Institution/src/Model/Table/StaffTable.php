<?php
namespace Institution\Model\Table;

use DateTime;
use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class StaffTable extends ControllerActionTable {
	use OptionsTrait;

	private $assigned;
	private $endOfAssignment;

	const PENDING = 0;
	const APPROVED = 1;
	const REJECTED = 2;
	const CLOSED = 3;

	const PENDING_PROFILE = -1;
	const PENDING_TRANSFERIN = -2;
	const PENDING_TRANSFEROUT = -3;

	private $dashboardQuery = null;

	public function initialize(array $config) {
		$this->table('institution_staff');
		parent::initialize($config);

		$this->belongsTo('Users',			['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('Positions',		['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
		$this->belongsTo('Institutions',	['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('StaffTypes',		['className' => 'Staff.StaffTypes']);
		$this->belongsTo('StaffStatuses',	['className' => 'Staff.StaffStatuses']);
		$this->belongsTo('SecurityGroupUsers', ['className' => 'Security.SecurityGroupUsers']);
		$this->hasMany('StaffPositionProfiles', ['className' => 'Institution.StaffPositionProfiles', 'foreignKey' => 'institution_staff_id', 'dependent' => true, 'cascadeCallbacks' => true]);

		$this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
		$this->addBehavior('AcademicPeriod.Period');
		// to handle field type (autocomplete)
		$this->addBehavior('OpenEmis.Autocomplete');
		$this->addBehavior('User.User');
		$this->addBehavior('User.AdvancedNameSearch');
		$this->addBehavior('AcademicPeriod.AcademicPeriod');

		$this->addBehavior('Excel', [
			'excludes' => ['start_year', 'end_year', 'security_group_user_id'],
			'pages' => ['index']
		]);

		$this->addBehavior('Restful.RestfulAccessControl', [
        	'StaffRoom' => ['index', 'add'],
        	'Staff' => ['index', 'add']
        ]);

		$this->addBehavior('HighChart', [
	      	'number_of_staff_by_type' => [
        		'_function' => 'getNumberOfStaffByType',
				'chart' => ['type' => 'column', 'borderWidth' => 1],
				'xAxis' => ['title' => ['text' => __('Position Type')]],
				'yAxis' => ['title' => ['text' => __('Total')]]
			],
			'number_of_staff_by_position' => [
        		'_function' => 'getNumberOfStaffByPosition',
				'chart' => ['type' => 'column', 'borderWidth' => 1],
				'xAxis' => ['title' => ['text' => __('Position Title')]],
				'yAxis' => ['title' => ['text' => __('Total')]]
			],
			'institution_staff_gender' => [
				'_function' => 'getNumberOfStaffsByGender'
			],
			'institution_staff_qualification' => [
				'_function' => 'getNumberOfStaffsByQualification'
			],
		]);

		/**
		 * Advance Search Types.
		 * AdvanceSearchBehavior must be included first before adding other types of advance search.
		 * If no "belongsTo" relation from the main model is needed, include its foreign key name in AdvanceSearch->exclude options.
		 */
        $advancedSearchFieldOrder = [
            'first_name', 'middle_name', 'third_name', 'last_name',
            'contact_number', 'identity_type', 'identity_number'
        ];

		$this->addBehavior('AdvanceSearch', [
			'exclude' => [
				'staff_id',
				'institution_id',
				'staff_type_id',
				'staff_status_id',
				'institution_position_id',
				'security_group_user_id'
			],
            'order' => $advancedSearchFieldOrder
		]);
		$this->addBehavior('User.AdvancedIdentitySearch', [
			'associatedKey' => $this->aliasField('staff_id')
		]);
		$this->addBehavior('User.AdvancedContactNumberSearch', [
			'associatedKey' => $this->aliasField('staff_id')
		]);
		$this->addBehavior('User.AdvancedSpecificNameTypeSearch', [
			'modelToSearch' => $this->Users
		]);

		$this->addBehavior('Institution.StaffValidation');
		/**
		 * End Advance Search Types
		 */

		$statuses = $this->StaffStatuses->findCodeList();
		$this->assigned = $statuses['ASSIGNED'];
		$this->endOfAssignment = $statuses['END_OF_ASSIGNMENT'];

        $this->addBehavior('Import.ImportLink');
        $this->addBehavior('ControllerAction.Image');

        $this->setDeleteStrategy('restrict');
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		$validator = $this->buildStaffValidation();
		return $validator
			->allowEmpty('staff_name')
			->add('staff_name', 'ruleInstitutionStaffId', [
				'rule' => ['institutionStaffId'],
				'on' => 'create'
			])
			->add('staff_assignment', 'ruleTransferRequestExists', [
				'rule' => ['checkPendingStaffTransfer'],
				'on' => 'create'
			])
			->add('staff_assignment', 'ruleCheckStaffAssignment', [
				'rule' => ['checkStaffAssignment'],
				'on' => 'create'
			])
			->requirePresence('FTE')
			->requirePresence('position_type')
		;
	}

	public function validationAllowEmptyName(Validator $validator) {
		$validator = $this->validationDefault($validator);
        $validator->remove('staff_name');
        return $validator;
	}

	public function validationAllowPositionType(Validator $validator) {
		$validator = $this->validationDefault($validator);
		$validator->requirePresence('position_type', false);
		return $validator;
	}

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$query->where([$this->aliasField('institution_id') => $institutionId]);
		$periodId = $this->request->query['academic_period_id'];
		if ($periodId > 0) {
			$query->find('academicPeriod', ['academic_period_id' => $periodId]);
		}
		$query->contain(['Positions.StaffPositionTitles'])->select(['position_title_teaching' => 'StaffPositionTitles.type'])->autoFields(true);
	}

	public function onExcelGetFTE(Event $event, Entity $entity) {
		return ($entity->FTE * 100) . '%';
	}

	public function onExcelGetPositionTitleTeaching(Event $event, Entity $entity) {
		$yesno = $this->getSelectOptions('general.yesno');
		return (array_key_exists($entity->position_title_teaching, $yesno))? $yesno[$entity->position_title_teaching]: '';
	}

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {
		$fieldArray = $fields->getArrayCopy();
		$extraField[] = [
			'key' => 'Positions.position_title_teaching',
			'field' => 'position_title_teaching',
			'type' => 'string',
			'label' => __('Teaching')
		];

		$newFields = array_merge($fieldArray, $extraField);
		$fields->exchangeArray($newFields);
	}

	public function indexBeforeAction(Event $event, ArrayObject $settings) {
		$this->fields['staff_id']['order'] = 5;
		$this->fields['institution_position_id']['type'] = 'integer';
		$this->fields['staff_id']['type'] = 'integer';
		$this->fields['start_date']['type'] = 'date';
		$this->fields['institution_position_id']['order'] = 6;
		$this->fields['FTE']['visible'] = false;

		$this->controller->set('ngController', 'AdvancedSearchCtrl');

		$selectedStatus = $this->request->query('staff_status_id');
		switch ($selectedStatus) {
			case self::PENDING_PROFILE:
				$event->stopPropagation();
				return $this->controller->redirect(['plugin'=>'Institution', 'controller' => 'Institutions', 'action' => 'StaffPositionProfiles']);
				break;
			case self::PENDING_TRANSFERIN:
				$event->stopPropagation();
				return $this->controller->redirect(['plugin'=>'Institution', 'controller' => 'Institutions', 'action' => 'StaffTransferRequests']);
				break;
			case self::PENDING_TRANSFEROUT:
				$event->stopPropagation();
				return $this->controller->redirect(['plugin'=>'Institution', 'controller' => 'Institutions', 'action' => 'StaffTransferApprovals']);
				break;
		}
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		$request = $this->request;
		$query->contain(['Positions']);
		$sortList = ['start_date', 'end_date'];
		if (array_key_exists('sortWhitelist', $extra)) {
			$sortList = array_merge($extra['sortWhitelist'], $sortList);
		}
		$extra['sortWhitelist'] = $sortList;

		$AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		// Academic Periods
		$periodOptions = $AcademicPeriodTable->getList();

		if (empty($request->query['academic_period_id'])) {
			$request->query['academic_period_id'] = $AcademicPeriodTable->getCurrent();
		}

		// Positions
		$session = $request->session();
		$institutionId = $session->read('Institution.Institutions.id');

		$StaffPositionTitles = TableRegistry::get('Institution.StaffPositionTitles');
		$activeStatusId = $this->Workflow->getStepsByModelCode('Institution.InstitutionPositions', 'ACTIVE');

		$positionData = $StaffPositionTitles->find('list')
			->matching('Titles', function ($q) use ($institutionId, $activeStatusId) {
				$q->where([
					'Titles.institution_id' => $institutionId,
					'Titles.status_id IN ' => $activeStatusId
				]);
				return $q;
			})
			->group([$StaffPositionTitles->aliasField($StaffPositionTitles->primaryKey())])
			->order([$StaffPositionTitles->aliasField('order')])
			->toArray()
			;

		$positionOptions = [0 => __('All Positions')] + $positionData;

		// Query Strings
		$selectedPeriod = $this->queryString('academic_period_id', $periodOptions);
		$selectedPosition = $this->queryString('position', $positionOptions);

		$Staff = $this;

		// Advanced Select Options
		$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
			'message' => '{{label}} - ' . $this->getMessage('general.noStaff'),
			'callable' => function($id) use ($Staff, $institutionId) {
				return $Staff
					->findByInstitutionId($institutionId)
					->find('academicPeriod', ['academic_period_id' => $id])
					->count();
			}
		]);

		// To add the academic_period_id to export
        if (isset($extra['toolbarButtons']['export']['url'])) {
            $extra['toolbarButtons']['export']['url']['academic_period_id'] = $selectedPeriod;
        }

		$request->query['academic_period_id'] = $selectedPeriod;

		$this->advancedSelectOptions($positionOptions, $selectedPosition);

		$query->find('academicPeriod', ['academic_period_id' => $selectedPeriod]);
		if ($selectedPosition != 0) {
			$query->matching('Positions', function ($q) use ($selectedPosition) {
				return $q->where(['Positions.staff_position_title_id' => $selectedPosition]);
			});
		}

		$search = $this->getSearchKey();
		if (!empty($search)) {
			// function from AdvancedNameSearchBehavior
			$query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $search]);
		}

		// start: sort by name
		$sortList = ['Users.first_name'];
		if (array_key_exists('sortWhitelist', $extra)) {
			$sortList = array_merge($extra['sortWhitelist'], $sortList);
		}
		$extra['sortWhitelist'] = $sortList;
		// end: sort by name

		$statusOptions = $this->StaffStatuses->find('list')->toArray();

		$approvedStatus = $this->Workflow->getStepsByModelCode('Institution.StaffPositionProfiles', 'APPROVED');
		$closedStatus = $this->Workflow->getStepsByModelCode($this->registryAlias(), 'CLOSED');
		$staffPositionProfileStatuses = array_merge($approvedStatus, $closedStatus);

		$StaffPositionProfilesTable = TableRegistry::get('Institution.StaffPositionProfiles');
		$staffPositionProfilesRecordCount = $StaffPositionProfilesTable->find()
			->where([
				$StaffPositionProfilesTable->aliasField('institution_id') => $institutionId,
				$StaffPositionProfilesTable->aliasField('status_id'). ' NOT IN ' => $staffPositionProfileStatuses
			])
			->count();

		$StaffTransferTable = TableRegistry::get('Institution.StaffTransferRequests');
		$staffTransferInRecord = $StaffTransferTable->find()
			->where([
				$StaffTransferTable->aliasField('institution_id') => $institutionId,
				$StaffTransferTable->aliasField('status'). ' IN ' => [self::PENDING, self::APPROVED]
			])
			->count();

		$staffTransferOutRecord = $StaffTransferTable->find()
			->where([
				$StaffTransferTable->aliasField('previous_institution_id') => $institutionId,
				$StaffTransferTable->aliasField('status'). ' IN ' => [self::PENDING]
			])
			->count();

		$statusOptions[self::PENDING_PROFILE] = __('Pending Change in Assignment'). ' - '. $staffPositionProfilesRecordCount;
		$statusOptions[self::PENDING_TRANSFERIN] = __('Pending Transfer In'). ' - ' . $staffTransferInRecord;
		$statusOptions[self::PENDING_TRANSFEROUT] = __('Pending Transfer Out'). ' - ' . $staffTransferOutRecord;

		$selectedStatus = $this->queryString('staff_status_id', $statusOptions);
		$this->advancedSelectOptions($statusOptions, $selectedStatus);
		$request->query['staff_status_id'] = $selectedStatus;
		$query->where([$this->aliasField('staff_status_id') => $selectedStatus]);
		$this->controller->set(compact('periodOptions', 'positionOptions', 'statusOptions'));
	}

	public function indexAfterAction(Event $event, Query $query, ResultSet $resultSet, ArrayObject $extra)
	{
		$this->dashboardQuery = clone $query;
	}

	public function addStaffRole($staffEntity) {
		$positionEntity = null;

		if (empty($staffEntity->security_group_user_id)) {
			// every staff record in school will be linked to a security role record in security_group_users
			$securityGroupId = $this->Institutions->get($staffEntity->institution_id)->security_group_id;
			$positionEntity = $this->Positions->find()
				->where([
					$this->Positions->aliasField('id') => $staffEntity->institution_position_id
				])
				->matching('StaffPositionTitles.SecurityRoles')
				->select(['security_role_id' => 'SecurityRoles.id', 'is_homeroom'])
				->first();

			$securityRoleId = $positionEntity->security_role_id;

			$SecurityGroupUsersTable = TableRegistry::get('Security.SecurityGroupUsers');
			$securityGroupUsersRecord = [
				'security_role_id' => $securityRoleId,
				'security_group_id' => $securityGroupId,
				'security_user_id' => $staffEntity->staff_id
			];

			$newSecurityGroupEntity = $SecurityGroupUsersTable->newEntity($securityGroupUsersRecord);
			$entity = $SecurityGroupUsersTable->save($newSecurityGroupEntity);
			$this->updateSecurityGroupUserId($staffEntity, $entity->id);

			if (!empty($positionEntity) && $positionEntity->is_homeroom) {
				// add homeroomrole
				$SecurityRoles = TableRegistry::get('Security.SecurityRoles');
				$homeroomSecurityRoleId = $SecurityRoles->getHomeroomRoleId();
				if (!empty($homeroomSecurityRoleId)) {
					try {
						$SecurityGroupUsersTable = TableRegistry::get('Security.SecurityGroupUsers');
						$securityGroupId = $this->Institutions->get($staffEntity->institution_id)->security_group_id;
						$securityRoleId = $homeroomSecurityRoleId;

						$securityGroupUsersRecord = [
							'security_role_id' => $homeroomSecurityRoleId,
							'security_group_id' => $securityGroupId,
							'security_user_id' => $staffEntity->staff_id
						];
						$newSecurityGroupEntity = $SecurityGroupUsersTable->newEntity($securityGroupUsersRecord);
						$entity = $SecurityGroupUsersTable->save($newSecurityGroupEntity);
					} catch (InvalidPrimaryKeyException $ex) {
						Log::write('error', __METHOD__ . ': ' . $this->Institutions->alias() . ' primary key not found (' . $institutionId . ')');
					}
				}
			}
		}
	}

	// IMPORTANT: when editing this method, need to consider impact on removeInactiveStaffSecurityRole()
	public function removeStaffRole($staffEntity) {
		$SecurityGroupUsersTable = TableRegistry::get('Security.SecurityGroupUsers');
		$securityGroupUserResults = $SecurityGroupUsersTable->find()->where([$SecurityGroupUsersTable->aliasField('id') => $staffEntity->security_group_user_id])->all();

		$affectedRows = 0;
		if (!$securityGroupUserResults->isEmpty()) {
			$affectedRows = $securityGroupUserResults->count();
			$deleteEntity = $securityGroupUserResults->first();
			$SecurityGroupUsersTable->delete($deleteEntity);
		}
		$this->updateSecurityGroupUserId($staffEntity, NULL);

		if ($affectedRows) {
			$positionEntity = $this->Positions->find()
				->where([
					$this->Positions->aliasField('id') => $staffEntity->institution_position_id
				])
				->matching('StaffPositionTitles.SecurityRoles')
				->select(['security_role_id' => 'SecurityRoles.id', 'is_homeroom'])
				->first();
			if (!empty($positionEntity) && $positionEntity->is_homeroom) {
				// remove homeroom role
				// delete 1 entry only
				$SecurityRoles = TableRegistry::get('Security.SecurityRoles');
				$homeroomSecurityRoleId = $SecurityRoles->getHomeroomRoleId();

				if (!empty($homeroomSecurityRoleId)) {
					try {
						$SecurityGroupUsersTable = TableRegistry::get('Security.SecurityGroupUsers');
						$securityGroupId = $this->Institutions->get($staffEntity->institution_id)->security_group_id;
						$deleteEntity = $SecurityGroupUsersTable->find()
							->where([
								$SecurityGroupUsersTable->aliasField('security_group_id') => $securityGroupId,
								$SecurityGroupUsersTable->aliasField('security_user_id') => $staffEntity->staff_id,
								$SecurityGroupUsersTable->aliasField('security_role_id') => $homeroomSecurityRoleId
							])
							->first();
						if (!empty($deleteEntity)) {
							$SecurityGroupUsersTable->delete($deleteEntity);
						}
					} catch (InvalidPrimaryKeyException $ex) {
						Log::write('error', __METHOD__ . ': ' . $this->Institutions->alias() . ' primary key not found (' . $institutionId . ')');
					}
				}
			}
		}
	}

	public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		if (isset($extra['toolbarButtons'])) {
			$toolbarButtons = $extra['toolbarButtons'];

			if (isset($toolbarButtons['edit'])) {
				$url = $toolbarButtons['edit']['url'];
				$staffId = $url[1];
				unset($url[1]);
				$url[0] = 'add';
				$url['institution_staff_id'] = $staffId;
				$url['action'] = 'StaffPositionProfiles';
				$toolbarButtons['edit']['url'] = $url;
			}
		}
		$this->Session->write('Staff.Staff.id', $entity->staff_id);
		$this->Session->write('Staff.Staff.name', $entity->user->name);
		$this->setupTabElements($entity);
	}

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		if ($this->action == 'add') {
			$buttons[0]['name'] = '<i class="fa kd-add"></i> ' . __('Create New');
			$buttons[0]['attr']['value'] = 'new';
		}
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		if (!$entity->isNew() && $entity->dirty('FTE')) {
			$newFTE = $entity->FTE;
			$newEndDate = $entity->end_date;

			$entity->FTE = $entity->getOriginal('FTE');
			$entity->newFTE = $newFTE;
			$todayDate = new Date();

			if (empty($newEndDate)) {
				if ($entity->start_date < $todayDate) {
					$entity->end_date = $todayDate;
				} else {
					$entity->end_date = $entity->start_date;
				}
			} else {
				// If end date is of a past date, set the user status to end of assignment
				if ($entity->end_date < $todayDate) {
					$entity->staff_status_id = $this->endOfAssignment;
				}
			}
		}
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		$institutionPositionId = $entity->institution_position_id;
		$staffId = $entity->staff_id;
		$institutionId = $entity->institution_id;
		$securityGroupId = $this->Institutions->get($institutionId)->security_group_id;

		if (!$entity->isNew()) { // edit operation
			if ($entity->has('newFTE')) {
				unset($entity->id);
				$entity->FTE = $entity->newFTE;
				$entity->start_date = $entity->end_date;
				if ($entity->start_date instanceof Date) {
					$entity->start_date->modify('+1 days');
				} else {
					$startDate = $entity->start_date->format('Y-m-d');
					$date = date_create($startDate);
					date_add($date, date_interval_create_from_date_string('1 day'));
					$entity->start_date = $date->format('Y-m-d');
				}
				$entity->end_date = null;
				$entity->end_year = null;
				unset($entity->staff_type);
				unset($entity->staff_status);
				unset($entity->position);
				unset($entity->user);
				$newEntity = $this->newEntity($entity->toArray(), ['validate' => 'AllowPositionType']);
				$this->save($newEntity);
				// if ($this->save($newEntity)) {
				// 	$url = [
				// 		'plugin' => 'Institution',
				// 		'controller' => 'Institutions',
				// 		'action' => 'Staff',
				// 		'0' => 'view',
				// 		'1' => $newEntity->id
				// 	];
				// 	$url = array_merge($url, $this->ControllerAction->params());
				// 	$event->stopPropagation();
				// 	return $this->controller->redirect($url);
				// }
			} else {
				if (empty($entity->end_date) || $entity->end_date->isToday() || $entity->end_date->isFuture()) {
					$this->addStaffRole($entity);
					$this->updateStaffStatus($entity, $this->assigned);
				} else {
					$this->removeStaffRole($entity);
					$this->updateStaffStatus($entity, $this->endOfAssignment);
				}
			}
		} else { // add operation
			$this->addStaffRole($entity);
			$this->updateStaffStatus($entity, $this->assigned);
		}

		$listeners = [
			TableRegistry::get('Institution.InstitutionSubjectStaff')
		];
		$this->dispatchEventToModels('Model.Staff.afterSave', [$entity], $this, $listeners);
	}

	private function updateStaffStatus($entity, $staffStatuses) {
		$this->updateAll(
			['staff_status_id' => $staffStatuses],
			['id' => $entity->id]
		);
	}

	private function updateSecurityGroupUserId($entity, $groupUserId) {
		$this->updateAll(
			['security_group_user_id' => $groupUserId],
			['id' => $entity->id]
		);
	}

	private function setupTabElements($entity) {
		$options = [
			'userRole' => 'Staff',
			'action' => $this->action,
			'id' => $entity->id,
			'userId' => $entity->staff_id
		];
		$tabElements = $this->controller->getCareerTabElements($options);

		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', 'Positions');
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		if (isset($buttons['view'])) {
			$primaryKey = is_array($this->primaryKey()) ? array_flip($this->primaryKey()) : [0 => $this->primaryKey()];
			$entityArr = $entity->getOriginalValues();
			$primaryKeyValues = array_intersect_key($entityArr, $primaryKey);
			$encodeValue = $this->paramsEncode($primaryKeyValues);

			$url = $this->url('view');
			$url['action'] = 'StaffUser';
			$url[1] = $this->paramsEncode(['id' => $entity['_matchingData']['Users']['id']]);
			$url['id'] = $encodeValue;
			$buttons['view']['url'] = $url;
		}

		if (isset($buttons['edit'])) {
			$primaryKey = is_array($this->primaryKey()) ? array_flip($this->primaryKey()) : [0 => $this->primaryKey()];
			$url = $this->url('add');
			$url['action'] = 'StaffPositionProfiles';
			$url['institution_staff_id'] = $this->paramsEncode(['id' => $entity->id]);
			$url['action'] = 'StaffPositionProfiles';
			$buttons['edit']['url'] = $url;
		}

		if ($this->Session->read('Auth.User.id') == $entity->_matchingData['Users']->id) { //if logged on user = current user, then unset the delete button
			unset($buttons['remove']);
		}

		return $buttons;
	}

	public function onGetStaffId(Event $event, Entity $entity) {
		$value = '';
		if ($entity->has('user')) {
			$value = $entity->user->name;
		} else {
			$value = $entity->_matchingData['Users']->name;
		}
		return $value;

	}

	public function onGetPositionType(Event $event, Entity $entity) {
		$options = $this->getSelectOptions('Position.types');
		$value = $options['FULL_TIME'];
		if ($entity->FTE < 1) {
			$value = $options['PART_TIME'];
		}
		return $value;
	}

	public function onGetFTE(Event $event, Entity $entity) {
		$value = '100%';
		if ($entity->FTE < 1) {
			$value = ($entity->FTE * 100) . '%';
		}
		return $value;
	}

	public function afterAction(Event $event, ArrayObject $extra) {
		$this->field('staff_type_id', ['type' => 'select', 'visible' => ['index' => false, 'view' => true, 'edit' => true]]);
		$this->field('staff_status_id', ['type' => 'select']);
		$this->field('staff_id');
		$this->field('security_group_user_id', ['visible' => false]);

		$this->fields['staff_id']['sort'] = ['field' => 'Users.first_name'];

		if ($this->action == 'index') {
			$InstitutionArray = [];


			$session = $this->Session;
			$institutionId = $session->read('Institution.Institutions.id');

			$periodId = $this->request->query('academic_period_id');
			$conditions = ['institution_id' => $institutionId];

			$positionId = $this->request->query('position');

			$searchConditions = $this->getSearchConditions($this->Users, $this->request->data['Search']['searchField']);
			$searchConditions['OR'] = array_merge($searchConditions['OR'], $this->advanceNameSearch($this->Users, $this->request->data['Search']['searchField']));

			$institutionStaffQuery = clone $this->dashboardQuery;
			// Get Number of staff in an institution
			$staffCount = $institutionStaffQuery->group($this->aliasField('staff_id'))->count();

			unset($institutionStaffQuery);

			// Get Gender
			$InstitutionArray[__('Gender')] = $this->getDonutChart('institution_staff_gender',
				['query' => $this->dashboardQuery, 'key' => __('Gender')]);

			// Get Staff Licenses
			$table = TableRegistry::get('Staff.Licenses');
			// Revisit here in awhile
			$InstitutionArray[__('Licenses')] = $table->getDonutChart('institution_staff_licenses',
				['query' => $this->dashboardQuery, 'table'=>$this, 'key' => __('Licenses')]);

			$indexElements = (isset($this->controller->viewVars['indexElements']))?$this->controller->viewVars['indexElements'] :[] ;
			$indexElements[] = ['name' => 'Institution.Staff/controls', 'data' => [], 'options' => [], 'order' => 0];
			$indexDashboard = 'dashboard';

            if (!$this->isAdvancedSearchEnabled()) { //function to determine whether dashboard should be shown or not
    			$indexElements['mini_dashboard'] = [
    	            'name' => $indexDashboard,
    	            'data' => [
    	            	'model' => 'staff',
    	            	'modelCount' => $staffCount,
    	            	'modelArray' => $InstitutionArray,
    	            ],
    	            'options' => [],
    	            'order' => 2
    	        ];
            }
			foreach ($indexElements as $key => $value) {
                if ($value['name']=='OpenEmis.ControllerAction/index') {
                    $indexElements[$key]['order'] = 3;
                } else if ($value['name']=='OpenEmis.pagination') {
                    $indexElements[$key]['order'] = 4;
                }
            }

            $extra['elements'] = array_merge($extra['elements'], $indexElements);

            $this->setFieldOrder(['photo_content', 'openemis_no', 'staff_id', 'institution_position_id', 'start_date', 'end_date', 'staff_status_id']);
		}
	}

	public function viewBeforeAction(Event $event) {
		if ($this->Session->read('Institution.StaffPositionProfiles.addSuccessful')) {
			$this->Alert->success('StaffPositionProfiles.request');
			$this->Session->delete('Institution.StaffPositionProfiles.addSuccessful');
		}
		$this->field('photo_content', ['type' => 'image', 'order' => 0]);
		$this->field('openemis_no', ['type' => 'readonly', 'order' => 1]);
		$i = 10;
		$this->fields['staff_id']['order'] = $i++;
		$this->fields['institution_position_id']['order'] = $i++;
		$this->fields['FTE']['order'] = $i++;
	}

	public function editBeforeQuery(Event $event, Query $query) {
		$query->contain(['Users', 'Positions', 'StaffTypes', 'StaffStatuses']);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->field('staff_id', [
			'type' => 'readonly',
			'order' => 10,
			'attr' => ['value' => $entity->user->name_with_id]
		]);
		$this->field('institution_position_id', [
			'type' => 'readonly',
			'order' => 11,
			'attr' => ['value' => $entity->position->name]
		]);

		if (empty($entity->end_date)) {
			$this->field('FTE', [
				'type' => 'select',
				'order' => 12,
				'options' => ['0.25' => '25%', '0.5' => '50%', '0.75' => '75%', '1' => '100%']
			]);
		} else {
			$this->field('FTE', [
				'type' => 'readonly',
				'order' => 12,
				'attr' => ['value' => $entity->FTE]
			]);
		}
		$this->Session->write('Staff.Staff.id', $entity->staff_id);
		$this->Session->write('Staff.Staff.name', $entity->user->name);
		$this->setupTabElements($entity);
	}

	public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
	{
		// populate 'to be deleted' field
		$staff = $this->Users->get($entity->staff_id);
		$entity->showDeletedValueAs = $staff->name_with_id;

		$extra['excludedModels'] = [$this->StaffPositionProfiles->alias()];

		// staff assignments
		$StaffTransferRequests = TableRegistry::get('Institution.StaffTransferRequests');
		$transferRecordsCount = $StaffTransferRequests->find()
			->where([
				$StaffTransferRequests->aliasField('staff_id') => $entity->staff_id,
				$StaffTransferRequests->aliasField('previous_institution_id') => $entity->institution_id
			])
			->count();
		$extra['associatedRecords'][] = ['model' => 'StaffTransferRequests', 'count' => $transferRecordsCount];

		$associationArray = [
			'Institution.StaffAbsences' => 'StaffAbsences',
			'Institution.StaffLeave' => 'StaffLeave',
			'Institution.InstitutionClasses' =>'InstitutionClasses',
			'Institution.InstitutionSubjectStaff' => 'InstitutionSubjects',
			'Institution.InstitutionRubrics' => 'InstitutionRubrics',
			'Institution.InstitutionQualityVisits' => 'InstitutionVisits'
		];

		foreach ($associationArray as $tableName => $model) {
			$Table = TableRegistry::get($tableName);
			$recordsCount = $Table->find()
				->where([
					$Table->aliasField('staff_id') => $entity->staff_id,
					$Table->aliasField('institution_id') => $entity->institution_id
				])
				->count();
			$extra['associatedRecords'][] = ['model' => $model, 'count' => $recordsCount];
		}
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		$broadcaster = $this;
		$listeners = [
            TableRegistry::get('Institution.StaffLeave')	// Staff Leave associated to institution must be deleted.
        ];
        $this->dispatchEventToModels('Model.InstitutionStaff.afterDelete', [$entity], $broadcaster, $listeners);

		// note that $this->table('institution_staff');
		$id = $entity->id;
		$institutionId = $entity->institution_id;
		$staffId = $entity->staff_id;


		$startDate = (!empty($entity->start_date))? $entity->start_date->format('Y-m-d'): null;
		$endDate = (!empty($entity->end_date))? $entity->end_date->format('Y-m-d'): null;

		$InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');

		// Deleting a staff-to-position record in a school removes all records related to the staff in the school (i.e. remove him from classes/subjects) falling between end date and start date of his assignment in the position.
		$classesInPosition = $InstitutionClasses->find()
			->where(
				['staff_id' => $staffId, 'institution_id' => $institutionId]
			)
			->matching('AcademicPeriods', function ($q) use ($startDate, $endDate) {
				$overlapDateCondition = [];
				if (empty($endDate)) {
					$overlapDateCondition['AcademicPeriods.end_date' . ' >= '] = $startDate;
				} else {
					$overlapDateCondition['OR'] = [];
					$overlapDateCondition['OR'][] = ['AcademicPeriods.start_date' . ' >= ' => $startDate, 'AcademicPeriods.start_date' . ' <= ' => $endDate];
					$overlapDateCondition['OR'][] = ['AcademicPeriods.end_date' . ' >= ' => $startDate, 'AcademicPeriods.end_date' . ' <= ' => $endDate];
					$overlapDateCondition['OR'][] = ['AcademicPeriods.start_date' . ' <= ' => $startDate, 'AcademicPeriods.end_date' . ' >= ' => $endDate];
				}
				return $q->where($overlapDateCondition);
			})
			;
		$classArray = [];
		foreach ($classesInPosition as $key => $value) {
			$classArray[] = $value->id;
		}
		if (!empty($classArray)) {
			$InstitutionClasses->updateAll(
				['staff_id' => 0],
				['id IN ' => $classArray]
			);
		}
		// delete the staff from subjects
		// find subjects that matched the start-end date then delete from subject_staff that matches staff id and subjects returned from previous

		$InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
		$subjectsDuringStaffPeriod = $InstitutionSubjects->find()
			->where([$InstitutionSubjects->aliasField('institution_id') => $institutionId])
			->matching('AcademicPeriods', function ($q) use ($startDate, $endDate) {
				$overlapDateCondition = [];
				if (empty($endDate)) {
					$overlapDateCondition['AcademicPeriods.end_date' . ' >= '] = $startDate;
				} else {
					$overlapDateCondition['OR'] = [];
					$overlapDateCondition['OR'][] = ['AcademicPeriods.start_date' . ' >= ' => $startDate, 'AcademicPeriods.start_date' . ' <= ' => $endDate];
					$overlapDateCondition['OR'][] = ['AcademicPeriods.end_date' . ' >= ' => $startDate, 'AcademicPeriods.end_date' . ' <= ' => $endDate];
					$overlapDateCondition['OR'][] = ['AcademicPeriods.start_date' . ' <= ' => $startDate, 'AcademicPeriods.end_date' . ' >= ' => $endDate];
				}
				return $q->where($overlapDateCondition);
			})
			;
		$subjectIdsDuringStaffPeriod = [];
		foreach ($subjectsDuringStaffPeriod as $key => $value) {
			$subjectIdsDuringStaffPeriod[] = $value->id;
		}

		// Staff behavior associated to institution must be deleted.
		$StaffBehaviours = TableRegistry::get('Institution.StaffBehaviours');
		$staffBehavioursData = $StaffBehaviours->find()
            ->where([
    			$StaffBehaviours->aliasField('staff_id') => $entity->staff_id,
    			$StaffBehaviours->aliasField('institution_id') => $entity->institution_id,
    		])
            ->toArray()
            ;
        foreach ($staffBehavioursData as $key => $value) {
            $StaffBehaviours->delete($value);
        }

		// Staff absence associated to institution must be deleted.
		$StaffAbsences = TableRegistry::get('Institution.StaffAbsences');
		$staffAbsencesData = $StaffAbsences->find()
            ->where([
    			$StaffAbsences->aliasField('staff_id') => $entity->staff_id,
    			$StaffAbsences->aliasField('institution_id') => $entity->institution_id,
    		])
            ->toArray()
            ;
        foreach ($staffAbsencesData as $key => $value) {
            $StaffAbsences->delete($value);
        }

		// Rubrics related to staff must be deleted. (institution_site_quality_rubrics)
		// association cascade deletes institution_site_quality_rubric_answers
		$InstitutionRubrics = TableRegistry::get('Institution.InstitutionRubrics');
		$institutionRubricsQuery = $InstitutionRubrics->find()
			->where([
				$InstitutionRubrics->aliasField('staff_id') => $entity->staff_id,
				$InstitutionRubrics->aliasField('institution_id') => $entity->institution_id,
			])
		;
		foreach ($institutionRubricsQuery as $key => $value) {
			$InstitutionRubrics->delete($value);
		}

		if (!empty($subjectIdsDuringStaffPeriod)) {
			$InstitutionSubjectStaff = TableRegistry::get('Institution.InstitutionSubjectStaff');
			$InstitutionSubjectStaff->deleteAll([
				$InstitutionSubjectStaff->aliasField('staff_id') => $staffId,
				$InstitutionSubjectStaff->aliasField('institution_subject_id') . ' IN ' => $subjectIdsDuringStaffPeriod
			]);
		}

		// this logic here is to delete the roles from groups when the staff is deleted from the school
		try {

			$securityGroupId = $this->Institutions->get($institutionId)->security_group_id;
			$this->removeStaffRole($entity);

		} catch (InvalidPrimaryKeyException $ex) {
			Log::write('error', __METHOD__ . ': ' . $this->Institutions->alias() . ' primary key not found (' . $institutionId . ')');
		}
	}

	// Function used by the Mini-Dashboard (Institution Staff)
	public function getNumberOfStaffsByGender($params=[]) {
		$query = $params['query'];
		$InstitutionRecords = clone $query;
		$InstitutionStaffCount = $InstitutionRecords
			->matching('Users.Genders')
			->select([
				'count' => $InstitutionRecords->func()->count('DISTINCT staff_id'),
				'gender' => 'Genders.name'
			])
			->group('Users.gender_id');

		// Creating the data set
		$dataSet = [];
		foreach ($InstitutionStaffCount->toArray() as $value) {
            //Compile the dataset
			$dataSet[] = [__($value['gender']), $value['count']];
		}
		$params['dataSet'] = $dataSet;

		unset($InstitutionRecords);

		return $params;
	}

	// Function used by the Dashboard (For Institution Dashboard and Home Page)
	public function getNumberOfStaffByType($params=[]) {
		$conditions = isset($params['conditions']) ? $params['conditions'] : [];
		$_conditions = [];
		foreach ($conditions as $key => $value) {
			$_conditions[$this->alias().'.'.$key] = $value;
		}

		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$currentYearId = $AcademicPeriod->getCurrent();
		if (!empty($currentYearId)) {
			$currentYear = $AcademicPeriod->get($currentYearId, ['fields'=>'name'])->name;
		} else {
			$currentYear = __('Not Defined');
		}

		$staffsByPositionConditions = ['Genders.name IS NOT NULL'];
		$staffsByPositionConditions = array_merge($staffsByPositionConditions, $_conditions);

		$query = $this->find('all');
		$staffByPositions = $query
			->find('AcademicPeriod', ['academic_period_id'=> $currentYearId])
			->contain(['Users.Genders','Positions.StaffPositionTitles'])
			->select([
				'Positions.id',
				'StaffPositionTitles.type',
				'Users.id',
				'Genders.name',
				'total' => $query->func()->count('DISTINCT '.$this->aliasField('staff_id'))
			])
			->where($staffsByPositionConditions)
			->group([
				'StaffPositionTitles.type', 'Genders.name'
			])
			->order(
				'StaffPositionTitles.type'
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
				$positionType = $staffByPosition->position->staff_position_title->type;
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

		$params['options']['subtitle'] = array('text' => sprintf(__('For Year %s'), $currentYear));
		$params['options']['xAxis']['categories'] = array_values($positionTypes);
		$params['dataSet'] = $dataSet;

		return $params;
	}

	// Function used by the Dashboard (For Institution Dashboard and Home Page)
	public function getNumberOfStaffByPosition($params=[]) {
		$conditions = isset($params['conditions']) ? $params['conditions'] : [];
		$_conditions = [];
		foreach ($conditions as $key => $value) {
			$_conditions[$this->alias().'.'.$key] = $value;
		}

		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$currentYearId = $AcademicPeriod->getCurrent();
		if (!empty($currentYearId)) {
			$currentYear = $AcademicPeriod->get($currentYearId, ['fields'=>'name'])->name;
		} else {
			$currentYear = __('Not Defined');
		}

		$staffsByPositionConditions = ['Genders.name IS NOT NULL'];
		$staffsByPositionConditions = array_merge($staffsByPositionConditions, $_conditions);

		$query = $this->find('all');
		$staffByPositions = $query
                ->find('AcademicPeriod', ['academic_period_id'=> $currentYearId])
                ->contain(['Users.Genders','Positions.StaffPositionTitles'])
                ->select([
                    'Positions.id',
                    'StaffPositionTitles.id',
                    'StaffPositionTitles.name',
                    'Users.id',
                    'Genders.name',
                    'total' => $query->func()->count('DISTINCT '.$this->aliasField('staff_id'))
                ])
                ->where($staffsByPositionConditions)
                ->group([
                    'StaffPositionTitles.id', 'Genders.name'
                ])
                ->order(
                    'StaffPositionTitles.id'
                )
                ->toArray();

		$positionTypes = [];
		foreach ($staffByPositions as $staffPosition) {
			if ($staffPosition->has('position') && $staffPosition->position->has('staff_position_title')) {
				$id = $staffPosition->position->staff_position_title->id;
				$name = $staffPosition->position->staff_position_title->name;
				$positionTypes[$id] = $name;
			}
		}

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
				$positionType = $staffByPosition->position->staff_position_title->id;
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

		$params['options']['subtitle'] = array('text' => sprintf(__('For Year %s'), $currentYear));
		$params['options']['xAxis']['categories'] = array_values($positionTypes);
		$params['dataSet'] = $dataSet;

		return $params;
	}

// Functions that are migrated over
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
			$StaffPositionTitles = TableRegistry::get('Institution.StaffPositionTitles');
			$positions = $this->Positions->find('list')
						->find('withBelongsTo')
				        ->where([
				        	'Institutions.id' => $options['Institutions.id'],
				        	$StaffPositionTitles->aliasField('type') => $options['type']
				        ])
				        ->toArray()
				        ;
			$positions = array_keys($positions);
			if (!empty($positions)) {
				return $query->where([$this->aliasField('institution_position_id IN') => $positions]);
			} else {
				return $query;
			}
		} else {
			return $query;
		}
	}

	public function findByInstitution(Query $query, array $options) {
		if (array_key_exists('Institutions.id', $options)) {
			return $query->where([$this->aliasField('institution_id') => $options['Institutions.id']]);
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
			->contain(['Users', 'Institutions', 'Positions.StaffPositionTitles', 'StaffTypes', 'StaffStatuses']);
	}

    public function findStaffRecords(Query $query, array $options)
    {
        $academicPeriodId = (array_key_exists('academicPeriodId', $options))? $options['academicPeriodId']: null;
        $positionType = (array_key_exists('positionType', $options))? $options['positionType']: null;
        $staffId = (array_key_exists('staffId', $options))? $options['staffId']: null;
        $institutionId = (array_key_exists('institutionId', $options))? $options['institutionId']: null;
        $isHomeroom = (array_key_exists('isHomeroom', $options))? $options['isHomeroom']: null;

        if (!is_null($academicPeriodId)) {
            $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $academicPeriodData = $AcademicPeriods->find()
                ->select([
                    $AcademicPeriods->aliasField('start_date'), $AcademicPeriods->aliasField('end_date')
                ])
                ->where([$AcademicPeriods->aliasField($AcademicPeriods->primaryKey()) => $academicPeriodId])
                ->first();
            if (!empty($academicPeriodData)) {
                $start_date = $academicPeriodData->start_date;
                $end_date = $academicPeriodData->end_date;
                $query->find('inDateRange', ['start_date' => $start_date, 'end_date' => $end_date]);
            }
        }
        if (!is_null($positionType)) {
            $query->matching('Positions.StaffPositionTitles', function($q) use ($positionType) {
                // teaching staff only
                return $q->where(['StaffPositionTitles.type' => $positionType]);
            });
        }
        if (!is_null($isHomeroom)) {
            $query->matching('Positions', function($q) use ($isHomeroom) {
                // homeroom teachers only
                return $q->where(['Positions.is_homeroom' => $isHomeroom]);
            });
        }
        if (!is_null($staffId)) {
            $query->where([$this->aliasField('staff_id') => $staffId]);
        }
        if (!is_null($institutionId)) {
            $query->where([$this->aliasField('institution_id') => $institutionId]);
        }

        return $query;
    }

	public function removeInactiveStaffSecurityRole()
	{
		$SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');

		$StaffTable = $this;
		while (true) {
			$query = $this->find()
				->where([
					$this->aliasField('security_group_user_id IS NOT NULL'),
					$this->aliasField('end_date IS NOT NULL'),
					$this->aliasField('staff_status_id') => $this->assigned
				])
				->where(
					function ($exp) use ($StaffTable) {
						return $exp->lt($StaffTable->aliasField('end_date'), $StaffTable->find()->func()->now('date'));
					}
				)
				->limit(10)
				->page(1)
				;

			$resultSet = $query->all();

			if ($resultSet->count() == 0) {
				break;
			} else {
				foreach ($resultSet as $entity) {
					$this->removeStaffRole($entity);
					$this->updateStaffStatus($entity, $this->endOfAssignment);
				}
			}
		}
	}

	public function removeIndividualStaffSecurityRole($staffId)
	{
		$SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
		$StaffTable = $this;
		$institutionStaffRecords = $this->find()
			->where([
				$this->aliasField('security_group_user_id IS NOT NULL'),
				$this->aliasField('end_date IS NOT NULL'),
				$this->aliasField('staff_id') => $staffId
			])
			->where(
				function ($exp) use ($StaffTable) {
					return $exp->lt($StaffTable->aliasField('end_date'), $StaffTable->find()->func()->now('date'));
				}
			)
			->toArray();
		foreach($institutionStaffRecords as $entity) {
			$SecurityGroupUsers->deleteAll([
				$SecurityGroupUsers->aliasField($SecurityGroupUsers->primaryKey()) => $entity->security_group_user_id
			]);
			$this->updateAll(
				['security_group_user_id' => NULL],
				[$this->primaryKey() => $entity->id]
			);
			$this->updateStaffStatus($entity, $this->endOfAssignment);
		}
	}
}
