<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Network\Request;
use Cake\I18n\Time;

use App\Model\Table\ControllerActionTable;
use Institution\Model\Table\StaffTransfer;

class StaffTransferApprovalsTable extends StaffTransfer {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->behaviors()->get('ControllerAction')->config([
			'actions' => ['add' => false, 'remove' => false]
		]);
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Workbench.Model.onGetList'] = 'onGetWorkbenchList';

		return $events;
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		parent::beforeAction($event, $extra);
		
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
		$this->fields['institution_id']['type'] = 'integer';
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$this->request->data[$this->alias()]['status'] = $entity->status;
	}

	public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		parent::editAfterAction($event, $entity, $extra);

		$staffType = $this->StaffTypes->get($entity->staff_type_id)->name;
		$startDate = $this->formatDate($entity->start_date);

		$this->field('staff_type_id', ['type' => 'readonly', 'attr' => ['value' => __($staffType)]]);		
		$this->field('FTE', ['type' => 'readonly']);
		$this->field('start_date', ['type' => 'readonly', 'attr' => ['value' => $startDate]]);
		if ($entity->status != self::NEW_REQUEST) {
			$this->field('comment', ['attr' => [ 'disabled' => 'true']]);
		}
	}

	public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra) {
		$entity->comment = $data[$this->alias()]['comment'];
		$extra['patchEntity'] = false; // to prevent patching and validation
	}

	// Approval of application
	public function editBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
		$process = function($model, $entity) {
			$entity->status = self::APPROVED;
			return $model->save($entity);
		};
		return $process;
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
