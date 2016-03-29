<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Cake\Validation\Validator;
use Cake\ORM\ResultSet;

class StaffTransferRequestsTable extends ControllerActionTable {
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
	}

	public function validationDefault(Validator $validator) {
		return $validator->requirePresence('previous_institution_id');
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
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
	}

	public function indexBeforeAction(Event $event, $extra) {
    	$toolbarButtons = $extra['toolbarButtons'];
    	if (isset($toolbarButtons['add'])) {
    		unset($toolbarButtons['add']);
    	}
    }

    public function indexBeforeQuery(Event $event, Query $query, $extra) {
    	$query->where([$this->aliasField('type') => self::TRANSFER]);
    }

	public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$staffName = $this->Users->get($entity->staff_id)->name_with_id;
		$assignedInstitution = $this->Institutions->get($entity->previous_institution_id)->name;
		$this->field('id', ['type' => 'hidden', 'value' => $entity->id]);
		$this->field('status', ['type' => 'readonly', 'value' => $entity->status]);
		$this->field('staff_id', ['type' => 'readonly', 'attr' => ['value' => $staffName]]);
		$this->field('previous_institution_id', ['type' => 'readonly', 'attr' => ['value' => $assignedInstitution]]);
		$this->field('institution_id', ['type' => 'readonly', 'attr' => ['value' => $this->Institutions->get($entity->institution_id)->name], 'value' => $entity->institution_id]);
		$this->field('type', ['type' => 'hidden', 'visible' => true, 'value' => self::TRANSFER]);
		$this->field('institution_position_id', ['after' => 'institution_id', 'type' => 'select', 'options' => $this->getStaffPositionList()]);
		$this->field('staff_type_id', ['type' => 'select']);
		$fteOptions = ['0.25' => '25%', '0.5' => '50%', '0.75' => '75%', '1' => '100%'];
		$this->field('FTE', ['type' => 'select', 'options' => $fteOptions, 'value' => $entity->FTE]);
		$this->field('start_date');
		$this->field('comment');
	}

	public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$staffName = $this->Users->get($entity->staff_id)->name_with_id;
		$assignedInstitution = $this->Institutions->get($entity->previous_institution_id)->name;
		if ($this->action == 'add') {
			$message = $staffName .' '.__('is currently assigned to').' '. $assignedInstitution;
    		$this->Alert->info($message, ['type' => 'text']);
    	}

		$this->field('id', ['type' => 'hidden', 'value' => $entity->id]);
		$this->field('status', ['type' => 'readonly', 'value' => $entity->status]);
		$this->field('staff_id', ['type' => 'readonly', 'attr' => ['value' => $staffName]]);
		$this->field('previous_institution_id', ['type' => 'readonly', 'attr' => ['value' => $assignedInstitution]]);
		$this->field('institution_id', ['type' => 'readonly', 'attr' => ['value' => $this->Institutions->get($entity->institution_id)->name], 'value' => $entity->institution_id]);
		$this->field('type', ['type' => 'hidden', 'visible' => true, 'value' => self::TRANSFER]);
		$this->field('institution_position_id', ['after' => 'institution_id', 'type' => 'readonly', 'attr' => ['value' => $this->Positions->get($entity->institution_position_id)->name], 'value' => $entity->institution_position_id]);
		$this->field('staff_type_id', ['type' => 'readonly', 'attr' => ['value' => $this->StaffTypes->get($entity->staff_type_id)->name], 'value' => $entity->staff_type_id]);
		$fteOptions = ['0.25' => '25%', '0.5' => '50%', '0.75' => '75%', '1' => '100%'];
		$this->field('FTE', ['type' => 'readonly', 'options' => $fteOptions, 'value' => $entity->FTE]);
		$this->field('start_date', ['type' => 'readonly']);
		$this->field('comment');
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

	private function initialiseVariable($entity, $institutionStaffData) {
		$institutionStaff = $institutionStaffData;
		if (is_null($institutionStaff)) {
			return true;
		}
		$staffTransfer = $this->find()
			->where([
				$this->aliasField('staff_id') => $institutionStaff['staff_id'],
				$this->aliasField('previous_institution_id') => $institutionStaff['transfer_from'],
				$this->aliasField('institution_position_id') => $institutionStaff['institution_position_id'],
				$this->aliasField('status') => self::NEW_REQUEST,
				$this->aliasField('type') => self::TRANSFER
			])
			->first();
		if (empty($staffTransfer)) {
			$entity->staff_id = $institutionStaff['staff_id'];
			$entity->institution_position_id = $institutionStaff['institution_position_id'];
			$entity->institution_id = $institutionStaff['institution_id'];
			$entity->start_date = $institutionStaff['start_date'];
			$entity->staff_type_id = $institutionStaff['staff_type_id'];
			$entity->FTE = $institutionStaff['FTE'];
			$entity->previous_institution_id = $institutionStaff['transfer_from'];
			$entity->status = self::NEW_REQUEST;
			$this->request->data[$this->alias()]['status'] = self::NEW_REQUEST;
			$entity->type = self::TRANSFER;
			return false;
		} else {
			return $staffTransfer;
		}
	}
	public function editOnInitialize(Event $event, Entity $entity) {
		$this->request->data[$this->alias()]['status'] = $entity->status;
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		$institutionStaff = $this->Session->read('Institution.Staff.transfer');
		$addOperation = $this->initialiseVariable($entity, $institutionStaff);
		if ($addOperation) {
			if ($addOperation === true) {
				$url = $this->url('index');
			} else {
				$url = $this->url('view');
				$url[1] = $addOperation->id;
			}
			$event->stopPropagation();
			return $this->controller->redirect($url);
		}
	}

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		if ($this->action == 'add') {
			$url = $this->url('add');
			$url['action'] = 'Staff';
			$buttons[1]['url'] = $url;
		}
	}
}
