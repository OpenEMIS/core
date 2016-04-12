<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;
use Institution\Model\Table\StaffTransfer;

class StaffTransferRequestsTable extends StaffTransfer {
	public function initialize(array $config) {
		parent::initialize($config);
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

	public function indexBeforeQuery(Event $event, Query $query, $extra) {
		$query->where([$this->aliasField('type') => self::TRANSFER]);
	}

	public function editBeforeQuery(Event $event, Query $query, $extra) {
		$query->contain(['Users', 'Institutions', 'PreviousInstitutions', 'Positions']);
	}

	public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		parent::editAfterAction($event, $entity, $extra);
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

	// Assign of staff
	public function editOnAssign(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$staffDetail = $data[$this->alias()];
		$transferRecord = $data[$this->alias()];
		unset($staffDetail['id']);
		unset($staffDetail['previous_institution_id']);
		unset($staffDetail['comment']);
		$StaffTable = TableRegistry::get('Institution.Staff');
		$newStaffEntity = $StaffTable->newEntity($staffDetail);
		if ($newStaffEntity->errors()) {
			$message = [];
			$errors = $newStaffEntity->errors();
			foreach ($errors as $key => $value) {
				$msg = 'Institution.Staff.'.$key;
				if (is_array($value)) {
					foreach ($value as $k => $v) {
						$message[] = $msg.'.'.$k;
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
				$this->Alert->error($error);
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
