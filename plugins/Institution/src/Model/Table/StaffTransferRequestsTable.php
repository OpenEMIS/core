<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;
use Institution\Model\Table\StaffTransfer;
use App\Model\Traits\OptionsTrait;

class StaffTransferRequestsTable extends StaffTransfer {
	use OptionsTrait;

	public function initialize(array $config) {
		parent::initialize($config);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator->requirePresence('institution_position_id');
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		parent::beforeAction($event, $extra);		
		$toolbarButtons = $extra['toolbarButtons'];

		if ($this->action != 'index') {
			if ($this->Session->check('Institution.Staff.transfer')) {
				if (isset($toolbarButtons['back'])) {
					$url = $this->url('add');
					$url['action'] = 'Staff';
					$url[0] = 'add';
					$toolbarButtons['back']['url'] = $url;
				}
			}
		} else {
			$this->Session->delete('Institution.Staff.transfer');
		}
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Workbench.Model.onGetList'] = 'onGetWorkbenchList';

		return $events;
	}

	public function indexBeforeQuery(Event $event, Query $query, $extra) {
		$query->where([$this->aliasField('type') => self::TRANSFER]);
	}

	public function editBeforeQuery(Event $event, Query $query, $extra) {
		$query->contain(['Users', 'Institutions', 'PreviousInstitutions', 'Positions']);
	}

	public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		parent::editAfterAction($event, $entity, $extra);

		$this->field('previous_institution_id', ['type' => 'readonly', 'after' => 'staff_id', 'attr' => ['value' => $entity->previous_institution->code_name]]);
		$this->field('institution_position_id', ['type' => 'select', 'attr' => ['value' => $this->getEntityProperty($entity, 'institution_position_id')]]);
		$this->field('staff_type_id', ['type' => 'select']);
		$this->field('FTE', ['type' => 'select']);
	}

	private function isTransferExists($transfer) {
		$entity = $this->find()
			->where([
				$this->aliasField('staff_id') => $transfer['staff_id'],
				$this->aliasField('previous_institution_id') => $transfer['previous_institution_id'],
				$this->aliasField('institution_position_id') => $transfer['institution_position_id'],
				$this->aliasField('status') => self::PENDING,
				$this->aliasField('type') => self::TRANSFER
			])
			->first();
		return $entity;
	}

	public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra) {
		$this->request->data[$this->alias()]['status'] = $entity->status;
	}

	public function addOnInitialize(Event $event, Entity $entity, ArrayObject $extra) {
		$url = false;
		if ($this->Session->check('Institution.Staff.transfer')) {
			$staffTransfer = $this->Session->read('Institution.Staff.transfer');

			// check if there is an existing transfer application
			if ($transferEntity = $this->isTransferExists($staffTransfer)) {
				// TODO
				// pr($transferEntity);
				// $url = $this->url('view');
				// $url[1] = $addOperation->id;
			} else { // no existing transfer application, proceed to initiate a transfer
				foreach ($staffTransfer as $key => $value) {
					$entity->{$key} = $value;
				}
				$entity->status = self::PENDING;
				$entity->type = self::TRANSFER;
			}
		} else { // invalid transfer data
			$url = $this->url('index');
		}

		if ($url) {
			$event->stopPropagation();
			return $this->controller->redirect($url);
		}
	}

	public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$staffName = $this->Users->get($this->getEntityProperty($entity, 'staff_id'))->name_with_id;
		$staffTypeName = $this->StaffTypes->get($this->getEntityProperty($entity, 'staff_type_id'))->name;
		$institutionCodeName = $this->Institutions->get($this->getEntityProperty($entity, 'institution_id'))->code_name;
		$prevInstitutionCodeName = $this->Institutions->get($entity->previous_institution_id)->code_name;
		$positionName = $this->Positions->get($this->getEntityProperty($entity, 'institution_position_id'))->name;

		$this->field('status', ['type' => 'readonly']);
		$this->field('staff_id', ['type' => 'readonly', 'attr' => ['value' => $staffName]]);
		$this->field('previous_institution_id', ['type' => 'readonly', 'attr' => ['value' => $prevInstitutionCodeName]]);
		$this->field('institution_id', ['type' => 'readonly', 'attr' => ['value' => $institutionCodeName]]);
		$this->field('institution_position_id', ['after' => 'institution_id', 'type' => 'readonly', 'attr' => ['value' => $positionName]]);
		$this->field('staff_type_id', ['type' => 'readonly', 'attr' => ['value' => $staffTypeName]]);
		$this->field('FTE', ['type' => 'readonly']);
		$this->field('start_date', ['type' => 'readonly']);
		$this->field('comment');
		$this->field('update', ['type' => 'hidden', 'value' => 0, 'visible' => true]);
		$this->field('type', ['type' => 'hidden', 'visible' => true, 'value' => self::TRANSFER]);

		$message = $this->getMessage($this->aliasField('alreadyAssigned'), ['sprintf' => [$staffName, $this->getEntityProperty($entity, 'transfer_from')]]);
		$this->Alert->warning($message, ['type' => 'text']);
		$this->Alert->info($this->aliasField('confirmRequest'));
	}

	public function onUpdateFieldInstitutionPositionId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'edit') {
			$positionTable = TableRegistry::get('Institution.InstitutionPositions');
			$userId = $this->Auth->user('id');
			$institutionId = $this->Session->read('Institution.Institutions.id');

			// // excluding positions where 'InstitutionStaff.end_date is NULL'
			$excludePositions = $this->Positions->find('list');
			$excludePositions->matching('InstitutionStaff', function ($q) {
					return $q->where(['InstitutionStaff.end_date is NULL', 'InstitutionStaff.FTE' => 1]);
				});
			$excludePositions->where([$this->Positions->aliasField('institution_id') => $institutionId])
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
			$activeStatusId = $this->Workflow->getStepsByModelCode($positionTable->registryAlias(), 'ACTIVE');
			$positionConditions = [];
			$positionConditions[$this->Positions->aliasField('institution_id')] = $institutionId;
			if (!empty($activeStatusId)) {
				$positionConditions[$this->Positions->aliasField('status_id').' IN '] = $activeStatusId;
			}
			if (!empty($excludeArray)) {
				$positionConditions[$this->Positions->aliasField('id').' NOT IN '] = $excludeArray;
			}
			$staffPositionsOptions = $this->Positions
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

			$attr['options'] = $options;
			return $attr;
		}
		
	}

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		if ($this->action == 'add') {
			$url = $this->url('add');
			$url['action'] = 'Staff';
			$buttons[1]['url'] = $url;
		} else if ($this->action == 'edit') {
			if ($this->request->data[$this->alias()]['status'] == self::APPROVED) {
				$buttons[0]['name'] = '<i class="fa fa-check"></i> ' . __('Assign');
				$buttons[0]['attr'] = ['class' => 'btn btn-default btn-save', 'div' => false, 'name' => 'submit', 'value' => 'assign'];
			}
		}
	}

	// Workbench.Model.onGetList
	public function onGetWorkbenchList(Event $event, $AccessControl, ArrayObject $data) {
		if ($AccessControl->check(['Institutions', 'StaffTransferRequests', 'edit'])) {
			// $institutionIds = $AccessControl->getInstitutionsByUser(null, ['Dashboard', 'TransferApprovals', 'edit']);
			$institutionIds = $AccessControl->getInstitutionsByUser();
			$where = [
				$this->aliasField('status') => self::APPROVED, 
				$this->aliasField('type') => self::TRANSFER
			];
			if (!$AccessControl->isAdmin()) {
				$userId = $event->subject()->Auth->user('id');
				foreach ($institutionIds as $key => $val) {
					$roles = $this->Institutions->getInstitutionRoles($userId, $val);
					if (!$AccessControl->check(['Institutions', 'StaffTransferRequests', 'edit'], $roles)) {
						unset($institutionIds[$key]);
					}
				}
				$where[$this->aliasField('institution_id') . ' IN '] = $institutionIds;
			}

			$resultSet = $this
				->find()
				->select([
					$this->aliasField('id'),
					$this->aliasField('institution_id'),
					$this->aliasField('modified'),
					$this->aliasField('created'),
					'Users.openemis_no',
					'Users.first_name',
					'Users.middle_name',
					'Users.third_name',
					'Users.last_name',
					'Users.preferred_name',
					'Institutions.name',
					'PreviousInstitutions.name',
					'CreatedUser.username'
				])
				->contain(['Users', 'Institutions', 'PreviousInstitutions', 'CreatedUser'])
				->where($where)
				->order([
					$this->aliasField('created') => 'DESC'
				])
				->limit(30)
				->toArray();

			foreach ($resultSet as $key => $obj) {
				$requestTitle = sprintf('Staff Transfer Approved (%s) from %s to %s', $obj->user->name_with_id, $obj->previous_institution->name, $obj->institution->name);
				$url = [
					'plugin' => 'Institution',
					'controller' => 'Institutions',
					'action' => 'StaffTransferRequests',
					'edit',
					$obj->id,
					'institution_id' => $obj->institution_id
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
					'type' => __('Staff Transfer')
				];
			}
		}
	}

	// Assign of staff
	public function editOnAssign(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$staffDetail = $data[$this->alias()];
		$transferRecord = $data[$this->alias()];
		unset($staffDetail['id']);
		unset($staffDetail['previous_institution_id']);
		unset($staffDetail['comment']);
        $staffDetail['staff_status_id'] = $staffDetail['status'];
        unset($staffDetail['status']);
		$StaffTable = TableRegistry::get('Institution.Staff');
		$newStaffEntity = $StaffTable->newEntity($staffDetail, ['validate' => "AllowPositionType"]);
		if ($newStaffEntity->errors()) {
			$message = [];
			$errors = $newStaffEntity->errors();
			foreach ($errors as $key => $value) {
				$msg = 'Institution.Staff.'.$key;
				if (is_array($value)) {
					foreach ($value as $k => $v) {
						$message[] = __($v);
					}
				}
			}
			$this->Session->write('Institution.StaffTransferRequests.errors', $message);
		} else {
			if ($StaffTable->save($newStaffEntity)) {
				$transferRecord['status'] = self::CLOSED;
				$transferEntity = $this->newEntity($transferRecord);
				if ($this->save($transferEntity)) {
					$url = $this->url('view');
					$this->Session->write('Institution.StaffTransferRequests.success', true);
					$this->controller->redirect($url);
				}
			}
		}
	}

	public function editBeforeAction(Event $event, $extra) {
		if ($this->Session->check('Institution.StaffTransferRequests.errors')) {
			$errors = $this->Session->read('Institution.StaffTransferRequests.errors');
			$this->Alert->error('StaffTransferRequests.errorApproval');
			foreach ($errors as $error) {
				$this->Alert->error($error, ['type' => 'text']);
			}
			$this->Session->delete('Institution.StaffTransferRequests.errors');
		}
	}

	public function viewAfterAction(Event $event, Entity $entity, $extra) {
		if ($this->Session->check('Institution.StaffTransferRequests.success')) {
			$this->Alert->success('general.add.success');
			$this->Session->delete('Institution.StaffTransferRequests.success');
		}
		$toolbarButtons = $extra['toolbarButtons'];
		if ($entity->status == self::APPROVED) {
			if (isset($toolbarButtons['remove'])) {
				unset($toolbarButtons['remove']);
			}
		}
	}
}
