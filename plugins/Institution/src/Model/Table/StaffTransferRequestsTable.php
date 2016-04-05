<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

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
		$fteOptions = ['0.25' => '25%', '0.5' => '50%', '0.75' => '75%', '1' => '100%'];
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
		$this->field('FTE', ['type' => 'select', 'options' => $fteOptions, 'after' => 'staff_type_id', 'visible' => ['view' => true, 'edit' => true, 'add' => true]]);
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

	public function editBeforeQuery(Event $event, Query $query, $extra) {
		$query->contain(['Users', 'Institutions', 'PreviousInstitutions', 'Positions']);
	}

	public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$this->field('status', ['type' => 'readonly']);
		$this->field('staff_id', ['type' => 'readonly', 'attr' => ['value' => $entity->user->name_with_id]]);
		$this->field('previous_institution_id', ['type' => 'readonly', 'attr' => ['value' => $entity->previous_institution->name]]);
		$this->field('institution_id', ['type' => 'readonly', 'attr' => ['value' => $entity->institution->name]]);
		$this->field('institution_position_id', ['type' => 'readonly', 'after' => 'institution_id', 'attr' => ['value' => $entity->position->name]]);
		$this->field('staff_type_id', ['type' => 'select']);
		$this->field('FTE', ['type' => 'select']);
	}

	private function isTransferExists($transfer) {
		$entity = $this->find()
			->where([
				$this->aliasField('staff_id') => $transfer['staff_id'],
				$this->aliasField('previous_institution_id') => $transfer['previous_institution_id'],
				$this->aliasField('institution_position_id') => $transfer['institution_position_id'],
				$this->aliasField('status') => self::NEW_REQUEST,
				$this->aliasField('type') => self::TRANSFER
			])
			->first();
		return $entity;
	}

	public function addOnInitialize(Event $event, Entity $entity, ArrayObject $extra) {
		$url = false;
		if ($this->Session->check('Institution.Staff.transfer')) {
			$staffTransfer = $this->Session->read('Institution.Staff.transfer');

			// check if there is an existing transfer application
			if ($transferEntity = $this->isTransferExists($staffTransfer)) {
				// pr($transferEntity);
				// $url = $this->url('view');
				// $url[1] = $addOperation->id;
			} else { // no existing transfer application, proceed to initiate a transfer
				foreach ($staffTransfer as $key => $value) {
					$entity->{$key} = $value;
				}
				$entity->status = self::NEW_REQUEST;
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
		$staffName = $this->Users->get($entity->staff_id)->name_with_id;
		$staffTypeName = $this->StaffTypes->get($entity->staff_type_id)->name;
		$institutionName = $this->Institutions->get($entity->institution_id)->name;
		$positionName = $this->Positions->get($entity->institution_position_id)->name;
		
		$this->field('status', ['type' => 'readonly']);
		$this->field('staff_id', ['type' => 'readonly', 'attr' => ['value' => $staffName]]);
		$this->field('previous_institution_id', ['type' => 'readonly', 'attr' => ['value' => $entity->transfer_from]]);
		$this->field('institution_id', ['type' => 'readonly', 'attr' => ['value' => $institutionName]]);
		$this->field('institution_position_id', ['after' => 'institution_id', 'type' => 'readonly', 'attr' => ['value' => $positionName]]);
		$this->field('staff_type_id', ['type' => 'readonly', 'attr' => ['value' => $staffTypeName]]);
		$this->field('FTE', ['type' => 'readonly']);
		$this->field('start_date', ['type' => 'readonly']);
		$this->field('comment');
		$this->field('update', ['type' => 'hidden', 'value' => 0, 'visible' => true]);
		$this->field('type', ['type' => 'hidden', 'visible' => true, 'value' => self::TRANSFER]);

		$message = $this->getMessage($this->aliasField('alreadyAssigned'), ['sprintf' => [$staffName, $entity->transfer_from]]);
		$this->Alert->warning($message, ['type' => 'text']);
		$this->Alert->info($this->aliasField('confirmRequest'));
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

	public function onUpdateFieldStatus(Event $event, array $attr, $action, Request $request) {
		$statusOptions = [
			self::APPROVED => __('Approved'),
			self::REJECTED => __('Rejected'),
			self::NEW_REQUEST => __('New')
		];

		$attr['options'] = $statusOptions;

		if ($action == 'edit' || $action == 'add') {
			$attr['options'] = $statusOptions;
		}
		return $attr;
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
		$statusOptions = [
			self::APPROVED => __('Approved'),
			self::REJECTED => __('Rejected'),
			self::NEW_REQUEST => __('New')
		];
		if (array_key_exists($entity->status, $statusOptions)) {
			$name = $statusOptions[$entity->status];
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

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		if ($this->action == 'add') {
			$url = $this->url('add');
			$url['action'] = 'Staff';
			$buttons[1]['url'] = $url;
		}
	}
}
