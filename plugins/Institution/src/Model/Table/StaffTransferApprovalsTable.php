<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Cake\Validation\Validator;
use Cake\Network\Request;
use Cake\I18n\Time;

class StaffTransferApprovalsTable extends ControllerActionTable {
	use OptionsTrait;

	// Type for application
	const NEW_REQUEST = 0;
	const APPROVED = 1;
	const REJECTED = 2;

	// Type status for transfer / assignment
	const TRANSFER = 2;
	const ASSIGNMENT = 1;

	public function initialize(array $config) {
		$this->table('institution_staff_assignments');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('Positions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
		$this->belongsTo('PreviousInstitutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'previous_institution_id']);
		$this->belongsTo('StaffTypes', ['className' => 'FieldOption.StaffTypes']);

		$this->behaviors()->get('ControllerAction')->config([
			'action' => [
				'add' => false,
				'remove' => false 
			]
		]);
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Workbench.Model.onGetList'] = 'onGetWorkbenchList';

		return $events;
	}

	public function validationDefault(Validator $validator) {
		return $validator->requirePresence('previous_institution_id');
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('status');
		$this->field('staff_id');
		$this->field('previous_institution_id', ['after' => 'staff_id']);
		$this->field('institution_id', ['type' => 'integer', 'after' => 'previous_institution_id', 'visible' => ['index' => true, 'edit' => true, 'view' => true]]);
		$this->field('institution_position_id', ['after' => 'institution_id', 'visible' => ['edit' => true, 'view' => true]]);
		$this->field('type', ['visible' => false]);
		$this->field('staff_type_id', ['after' => 'institution_position_id', 'visible' => ['view' => true, 'edit' => true, 'add' => true]]);
		$fteOptions = ['0.25' => '25%', '0.5' => '50%', '0.75' => '75%', '1' => '100%'];
		$this->field('FTE', ['type' => 'select', 'after' => 'staff_type_id', 'visible' => ['view' => true, 'edit' => true, 'add' => true]]);
		$this->field('comment', ['after' => 'start_date', 'visible' => ['view' => true, 'edit' => true, 'add' => true]]);
		$this->field('end_date', ['visible' => false]);
		$this->field('update', ['visible' => false]);
		$extra['config']['selectedLink'] = ['controller' => 'Institutions', 'action' => 'Staff', 'index'];
		
		if ($this->action == 'edit' || $this->action == 'view') {
			$toolbarButtons = $extra['toolbarButtons'];
			if ($toolbarButtons['back']['url']['controller']=='Dashboard') {
				$toolbarButtons['back']['url']['action']= 'index';
				unset($toolbarButtons['back']['url'][0]);
				unset($toolbarButtons['back']['url'][1]);
			} else if ($toolbarButtons['back']['url']['controller']=='Institutions') {
				$toolbarButtons['back']['url']['action']= 'StaffTransferApprovals';
				unset($toolbarButtons['back']['url'][0]);
				unset($toolbarButtons['back']['url'][1]);
			}
		}
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$this->request->data[$this->alias()]['status'] = $entity->status;
	}

	public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$this->field('id', ['type' => 'hidden', 'value' => $entity->id]);
		$this->field('status', ['type' => 'readonly']);
		$this->field('staff_id', ['type' => 'readonly', 'attr' => ['value' => $this->Users->get($entity->staff_id)->name_with_id]]);
		$this->field('previous_institution_id', ['type' => 'readonly', 'attr' => ['value' => $this->Institutions->get($entity->previous_institution_id)->name]]);
		$this->field('institution_id', ['type' => 'readonly', 'attr' => ['value' => $this->Institutions->get($entity->institution_id)->name], 'value' => $entity->institution_id]);
		$this->field('institution_position_id', ['type' => 'readonly', 'attr' => ['value' => $this->Positions->get($entity->institution_position_id)->name]]);
		$this->field('staff_type_id', ['type' => 'readonly', 'attr' => ['value' => __($this->StaffTypes->get($entity->staff_type_id)->name)]]);
		$fteOptions = ['0.25' => '25%', '0.5' => '50%', '0.75' => '75%', '1' => '100%'];
		$this->field('FTE', ['type' => 'readonly', 'options' => $fteOptions, 'value' => $entity->FTE]);
		$startDate = Time::parse($entity->start_date);
		$this->field('start_date', ['type' => 'readonly', 'value' => $startDate->format('Y-m-d')]);
		if ($entity->status == self::NEW_REQUEST) {
			$this->field('comment');
		} else {
			$this->field('comment', ['attr' => [ 'disabled' => 'true']]);
		}
		
	}

	public function viewAfterAction(Event $event, Entity $entity, $extra) {
		$toolbarButtons = $extra['toolbarButtons'];
		$status = $entity->status;
		switch ($status) {
			case self::REJECTED:
			case self::APPROVED:
				if (isset($toolbarButtons['edit'])) {
					unset($toolbarButtons['edit']);
				}
				break;
		}
	}

	public function onGetStaffId(Event $event, Entity $entity) {
		$urlParams = $this->url('index');
		$action = $urlParams['action'];
		$page = 'view';
		if ($entity->status_id == self::NEW_REQUEST) {
			if ($this->AccessControl->check(['Institutions', 'StaffTransferRequests', 'edit'])) {
				$page = 'edit';
			}
		}
		return $event->subject()->Html->link($entity->user->name, [
					'plugin' => $urlParams['plugin'],
					'controller' => $urlParams['controller'],
					'action' => $action,
					'0' => $page,
					'1' => $entity->id
				]);
	}

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
    	$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
    	if ($entity->status_id != self::NEW_REQUEST) {
    		if (isset($buttons['edit'])) {
    			unset($buttons['edit']);
    		}
    		if (isset($buttons['remove'])) {
    			unset($buttons['remove']);
    		}
    	}
    	return $buttons;
    }

	public function indexBeforeAction(Event $event, $extra) {
    	$toolbarButtons = $extra['toolbarButtons'];
    	if (isset($toolbarButtons['add'])) {
    		unset($toolbarButtons['add']);
    	}
    }

    public function indexBeforeQuery(Event $event, Query $query, $extra) {
    	$institutionId = $this->Session->read('Institution.Institutions.id');
    	$statusToshow = [self::NEW_REQUEST, self::REJECTED];
    	$query
    		->where([
    				$this->aliasField('previous_institution_id') => $institutionId,
    				$this->aliasField('status'). ' IN ' => $statusToshow,
    				$this->aliasField('type') => self::TRANSFER
    			], [], true);
    }

	private function getStaffPositionList() {
		$positionTable = TableRegistry::get('Institution.InstitutionPositions');
		$userId = $this->Auth->user('id');
		$institutionId = $this->Session->read('Institution.Institutions.id');
		if ($this->AccessControl->isAdmin()) {
			$userId = null;
			$roles = [];
		} else {
			$roles = $this->Institutions->getInstitutionRoles($userId, $institutionId);
		}
		
		// Filter by active status
		$activeStatusId = $this->Workflow->getStepsByModelCode($positionTable->registryAlias(), 'ACTIVE');
		$staffPositionsOptions = $this->Positions
				->find()
				->innerJoinWith('StaffPositionTitles.SecurityRoles')
				->where([
					$this->Positions->aliasField('institution_id') => $institutionId, 
					$this->Positions->aliasField('status_id').' IN ' => $activeStatusId
				])
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
		return $options;
	}

	private function newStaffProfileRecord(array $data) {
    	$InstitutionStaff = TableRegistry::get('Institution.Staff');
		unset($data['created']);
		unset($data['created_user_id']);
		unset($data['modified']);
		unset($data['modified_user_id']);
		unset($data['id']);
		$newEntity = $InstitutionStaff->newEntity($data);
    	return $newEntity;
    }

	public function onGetStatus(Event $event, Entity $entity) {
		$name = '';
		switch($entity->status) {
			case self::APPROVED:
				$name = __('Approved');
				break;
			case self::REJECTED:
				$name = __('Rejected');
				break;
			case self::NEW_REQUEST:
				$name = __('New');
				break;
		}
		$entity->status_id = $entity->status;
		return '<span class="status highlight">' . $name . '</span>';
	}

	public function onGetFTE(Event $event, Entity $entity) {
		$value = '100%';
		if ($entity->FTE < 1) {
			$value = ($entity->FTE * 100) . '%';
		}
		return $value;
	}

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		if ($this->action == 'edit') {
			// If the status is new application then display the approve and reject button, 
			// if not remove the button just in case the user gets to access the edit page
			if ($this->request->data[$this->alias()]['status'] == self::NEW_REQUEST || !($this->AccessControl->check(['Institutions', 'StaffTransferApprovals', 'edit']))) {
				$buttons[0]['name'] = '<i class="fa fa-check"></i> ' . __('Approve');

				$buttons[1] = [
					'name' => '<i class="fa fa-close"></i> ' . __('Reject'),
					'attr' => ['class' => 'btn btn-outline btn-cancel', 'div' => false, 'name' => 'submit', 'value' => 'reject']
				];
			} else {
				unset($buttons[0]);
				unset($buttons[1]);
			}
		}
	}

    public function onUpdateFieldStatus(Event $event, array $attr, $action, Request $request) {
    	if ($action == 'edit' || $action == 'add') {
    		if (isset($this->request->data[$this->alias()]['status'])) {
    			$status = $this->request->data[$this->alias()]['status'];
    			$name = '';
				switch($status) {
					case self::APPROVED:
						$name = __('Approved');
						break;
					case self::REJECTED:
						$name = __('Rejected');
						break;
					case self::NEW_REQUEST:
						$name = __('New');
						break;
				}
    			$attr['attr']['value'] = $name;
    			return $attr;
    		}	
    	}	
    }

	// Workbench.Model.onGetList
	public function onGetWorkbenchList(Event $event, $AccessControl, ArrayObject $data) {
		if ($AccessControl->check(['Institutions', 'StaffTransferApprovals', 'edit'])) {
			// $institutionIds = $AccessControl->getInstitutionsByUser(null, ['Dashboard', 'TransferApprovals', 'edit']);
			$institutionIds = $AccessControl->getInstitutionsByUser();

			$where = [$this->aliasField('status') => self::NEW_REQUEST, $this->aliasField('type') => self::TRANSFER];
			if (!$AccessControl->isAdmin()) {
				$where[$this->aliasField('previous_institution_id') . ' IN '] = $institutionIds;
			}

			$resultSet = $this
				->find()
				->contain(['Users', 'Institutions', 'PreviousInstitutions', 'ModifiedUser', 'CreatedUser'])
				->where($where)
				->order([
					$this->aliasField('created') => 'DESC'
				])
				->toArray();

			foreach ($resultSet as $key => $obj) {
				$requestTitle = sprintf('Transfer of staff (%s) from %s to %s', $obj->user->name, $obj->previous_institution->name, $obj->institution->name);
				$url = [
					'plugin' => false,
					'controller' => 'Dashboard',
					'action' => 'StaffTransferApprovals',
					'edit',
					$obj->id
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

	// Approval of application
	public function editBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
		$errors = $entity->errors();
		if (empty($errors)) {
			$Staff = TableRegistry::get('Institution.Staff');

			$newEntity = $Staff->newEntity($data[$this->alias()]);

			$startDate = Time::parse($newEntity->start_date);

			// If the start date is started
			
			$staffEntity = $Staff->save($newEntity);
			if ($staffEntity) {
				$this->Alert->success('TransferApprovals.approve');
				$entity->status = self::APPROVED;
				if ($startDate->isFuture()) {
					$entity->update = $staffEntity->id;
				}
				if (!$this->save($entity)) {
					$this->Alert->error('general.edit.failed');
					$this->log($entity->errors(), 'debug');
				}				
			} else {
				$this->Alert->error('general.edit.failed');
				$this->log($newEntity->errors(), 'debug');
			}

			// To redirect back to the student admission if it is not access from the workbench
			$urlParams = $this->url('index');
			$plugin = false;
			$controller = 'Dashboard';
			$action = 'index';
			if ($urlParams['controller'] == 'Institutions') {
				$plugin = 'Institution';
				$controller = 'Institutions';
				$action = 'StaffTransferApprovals';
			}

			$event->stopPropagation();
			return $this->controller->redirect(['plugin' => $plugin, 'controller' => $controller, 'action' => $action]);
		} else {
			// required for validation to work
			$process = function($model, $entity) {
				return false;
			};

			return $process;
		}
	}

	public function activateStaff() {
		while (true) {
			$records = $this->find()
				->where([$this->aliasField('status') => self::APPROVED, $this->aliasField('update').' <> ' => 0])
				->limit(10);

			if ($records->count() == 0) {
				break;
			}

			$assignedStatus = TableRegistry::get('Staff.StaffStatuses')->findCodeList()['ASSIGNED'];
			$StaffTable = TableRegistry::get('Institution.Staff');
			foreach ($records as $record) {
				$staffRecord = $StaffTable->get($record->update);
				$StaffTable->updateStaffStatus($staffRecord, $assignedStatus);
			}
		}
	}

	// Reject of application
	public function editOnReject(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		// Update status to 2 => reject
		$this->updateAll(['status' => self::REJECTED], ['id' => $entity->id]);
		// End

		$this->Alert->success('TransferApprovals.reject');
		
		// To redirect back to the student admission if it is not access from the workbench
		$urlParams = $this->url('index');
		$plugin = false;
		$controller = 'Dashboard';
		$action = 'index';
		if ($urlParams['controller'] == 'Institutions') {
			$plugin = 'Institution';
			$controller = 'Institutions';
			$action = 'StaffTransferApprovals';
		}

		$event->stopPropagation();
		return $this->controller->redirect(['plugin' => $plugin, 'controller' => $controller, 'action' => $action]);
	}
}
