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
use Cake\I18n\Date;

use App\Model\Table\ControllerActionTable;
use Institution\Model\Table\StaffTransfer;

class StaffTransferApprovalsTable extends StaffTransfer {
	// Transfer Type
	const FULL_TRANSFER = 1;
	const PARTIAL_TRANSFER = 2;

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

	public function validationDefault(Validator $validation) {
		return $validation->requirePresence('transfer_type');
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
		$this->request->data['entity'] = $entity;
		$this->entity = $entity;
	}

	public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		parent::editAfterAction($event, $entity, $extra);
		if ($entity->status == self::APPROVED) {
			$url = $this->url('index');
			if (isset($url[1])) {
				unset($url[1]);
			}
			return $this->controller->redirect($url);
		}

		$staffType = $this->StaffTypes->get($entity->staff_type_id)->name;
		if (!$entity->start_date instanceof Time || !$entity->start_date instanceof Date) {
			$entity->start_date = Time::parse($entity->start_date);
		}
		$startDate = $this->formatDate($entity->start_date);
		
		$this->field('institution_position_id', ['type' => 'hidden']);
		$this->field('staff_type_id', ['type' => 'hidden']);		
		$this->field('FTE', ['type' => 'hidden']);
		$this->field('start_date', ['type' => 'hidden']);
		

		$staffId = $entity->staff_id;

		$institutionId = $entity->previous_institution_id;
		$InstitutionStaff = TableRegistry::get('Institution.Staff');
		$staffRecord = $InstitutionStaff->find()
			->contain(['Positions', 'StaffTypes'])
			->where([
				$InstitutionStaff->aliasField('institution_id') => $institutionId,
				$InstitutionStaff->aliasField('staff_id') => $staffId,
				'OR' => [
					[$InstitutionStaff->aliasField('end_date').' >= ' => new Date()],
					[$InstitutionStaff->aliasField('end_date').' IS NULL']
				]
			])
			->order([$InstitutionStaff->aliasField('created') => 'DESC'])
			->first();

		if (!is_null($staffRecord)) {
			$this->field('transfer_type');
			$this->field('current_institution_position_id', ['before' => 'transfer_type', 'type' => 'disabled', 'attr' => ['value' => $staffRecord->position->name]]);
			$this->field('current_FTE', ['after' => 'current_institution_position_id', 'type' => 'disabled', 'attr' => ['value' => $staffRecord->FTE]]);
			$this->field('current_staff_type', ['after' => 'current_FTE', 'type' => 'disabled', 'attr' => ['value' => $staffRecord->staff_type->name]]);
			$this->field('current_start_date', ['after' => 'current_staff_type', 'type' => 'disabled', 'attr' => ['value' => $this->formatDate($staffRecord->start_date)]]);
			$this->field('new_FTE', ['currentFTE' => $staffRecord->FTE]);
			$this->field('new_staff_type_id', ['attr' => ['value' => $staffRecord->staff_type_id], 'select' => false]);
			$this->field('staff_end_date', ['type' => 'date', 'value' => new Date(), 
				'date_options' => ['startDate' => $staffRecord->start_date->format('d-m-Y')]]);
			$this->field('effective_date', ['after' => 'new_staff_type_id', 'type' => 'date', 'value' => new Date(), 
				'date_options' => ['startDate' => $staffRecord->start_date->format('d-m-Y')]]);
		} else {
			$this->Alert->info($this->aliasField('staffEndOfAssignment'));
		}
		if ($entity->status != self::PENDING) {
			$this->field('comment', ['attr' => [ 'disabled' => 'true']]);
		}
	}

	public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra) {
		$entity->comment = $data[$this->alias()]['comment'];
		$extra['patchEntity'] = false; // to prevent patching and validation
	}

	// Approval of application
	public function editBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra) {
		$staffId = $entity->staff_id;
		$startDate = $entity->start_date;
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$InstitutionStaff = TableRegistry::get('Institution.Staff');
		$staffRecord = $InstitutionStaff->find()
			->where([
				$InstitutionStaff->aliasField('institution_id') => $institutionId,
				$InstitutionStaff->aliasField('staff_id') => $staffId,
				'OR' => [
					[$InstitutionStaff->aliasField('end_date').' >= ' => new Date()],
					[$InstitutionStaff->aliasField('end_date').' IS NULL']
				]
			])
			->order([$InstitutionStaff->aliasField('created') => 'DESC'])
			->first();
		if (empty($data[$this->alias()]['transfer_type']) && !is_null($staffRecord)) {
			$extra[$this->aliasField('notice')] = $this->aliasField('transferType'); 
		} else {
			$error = false;

			if (!is_null($staffRecord) && $data[$this->alias()]['transfer_type'] == self::PARTIAL_TRANSFER) {
				if (empty($data[$this->alias()]['effective_date'])) {
					$extra[$this->aliasField('notice')] = $this->aliasField('effectiveDate');
					$error = true;
				}

				if (empty($data[$this->alias()]['new_FTE'])) {
					$extra[$this->aliasField('notice')] = $this->aliasField('newFTE');
					$error = true;
				}
			} 

			if (!$error) {
				$entity->staffRecord = $staffRecord;
				$process = function($model, $entity) {
					$entity->status = self::APPROVED;
					return $model->save($entity);
				};
				return $process;
			}
		}
		
	}

	public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra) {
		if (isset($extra[$this->aliasField('notice')])) {
			$this->Alert->error($extra[$this->aliasField('notice')], ['reset' => true]);
			return $this->controller->redirect($this->url('edit'));
		}
		$staffRecord = $entity->staffRecord;
		if (!is_null($staffRecord)) {
			$transferType = $requestData[$this->alias()]['transfer_type'];
			$InstitutionStaff = TableRegistry::get('Institution.Staff');
			if ($transferType == self::FULL_TRANSFER) {
				$staffEndDate = $requestData[$this->alias()]['staff_end_date'];
				$staffRecord->end_date = new Time($requestData[$this->alias()]['staff_end_date']);
				$InstitutionStaff->save($staffRecord);
			} else if ($transferType == self::PARTIAL_TRANSFER){
				$staffRecord->FTE = $requestData[$this->alias()]['new_FTE'];
				$staffRecord->staff_type_id = $requestData[$this->alias()]['new_staff_type_id'];
                // $staffRecord is an existing entity
                // this section of code uses InstitutionStaff afterSave logic, newFTE to save a newEntity
                // POCOR-2907 - unsetting security_group_user_id so a new security_group_user_id can be created and used as a foreign key
                unset($staffRecord->security_group_user_id);
				$InstitutionStaff->save($staffRecord);
			}
		}
	}

	public function indexBeforeQuery(Event $event, Query $query, $extra) {
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$statusToshow = [self::PENDING, self::REJECTED];
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
			if ($this->request->data[$this->alias()]['status'] == self::PENDING || !($this->AccessControl->check(['Institutions', 'StaffTransferApprovals', 'edit']))) {
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

			$where = [$this->aliasField('status') => self::PENDING, $this->aliasField('type') => self::TRANSFER];
			if (!$AccessControl->isAdmin()) {
				$userId = $event->subject()->Auth->user('id');
				foreach ($institutionIds as $key => $val) {
					$roles = $this->Institutions->getInstitutionRoles($userId, $val);
					if (!$AccessControl->check(['Institutions', 'StaffTransferApprovals', 'edit'], $roles)) {
						unset($institutionIds[$key]);
					}
				}
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
				$requestTitle = sprintf('Transfer of staff (%s) from %s to %s', $obj->user->name_with_id, $obj->previous_institution->name, $obj->institution->name);
				$url = [
					'plugin' => 'Institution',
					'controller' => 'Institutions',
					'action' => 'StaffTransferApprovals',
					'edit',
					$obj->id,
					'institution_id' => $obj->previous_institution_id
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

	public function onUpdateFieldTransferType(Event $event, array $attr, $action, Request $request) {
		$options = [self::FULL_TRANSFER => 'Full Transfer', self::PARTIAL_TRANSFER => 'Partial Transfer'];
		$attr['options'] = $options;
		$attr['onChangeReload'] = true;
		if (!isset($request->data[$this->alias()]['transfer_type'])) {
			$request->data[$this->alias()]['transfer_type'] = '';	
		}
		
		return $attr;
	}

	public function onUpdateFieldCurrentFTE(Event $event, array $attr, $action, Request $request) {
		$fteOptions = ['0.25' => '25%', '0.5' => '50%', '0.75' => '75%', '1' => '100%'];
		$val = $attr['attr']['value'];
		$attr['attr']['value'] = $fteOptions[strval($val)];
		return $attr;
	}

	public function onUpdateFieldNewFTE(Event $event, array $attr, $action, Request $request) {
		$fteOptions = ['0.25' => '25%', '0.5' => '50%', '0.75' => '75%', '1' => '100%'];
		if (isset($fteOptions[strval($attr['currentFTE'])])) {
			unset($fteOptions[strval($attr['currentFTE'])]);
		}
		$transferType = $request->data[$this->alias()]['transfer_type'];
		
		if ($transferType == self::PARTIAL_TRANSFER) {
			$attr['visible'] = true;
			$attr['options'] = $fteOptions;
			$attr['type'] = 'select';
		} else {
			$attr['visible'] = false;
		}

		return $attr;
	}

	public function onUpdateFieldNewStaffTypeId(Event $event, array $attr, $action, Request $request) {
		$transferType = $request->data[$this->alias()]['transfer_type'];
		
		if ($transferType == self::PARTIAL_TRANSFER) {
			$StaffTypes = TableRegistry::get('FieldOption.StaffTypes');
			$options = $StaffTypes->getList()->toArray();
			$attr['visible'] = true;
			$attr['type'] = 'select';
			$attr['options'] = $options;
		} else {
			$attr['visible'] = false;
		}

		return $attr;
	}

	public function onUpdateFieldStaffEndDate(Event $event, array $attr, $action, Request $request) {
		$transferType = $request->data[$this->alias()]['transfer_type'];
		if ($transferType == self::FULL_TRANSFER) {	
			$attr['visible'] = true;
		} else {
			$attr['visible'] = false;
		}
		return $attr;
	}

	public function onUpdateFieldEffectiveDate(Event $event, array $attr, $action, Request $request) {
		$transferType = $request->data[$this->alias()]['transfer_type'];
		
		if ($transferType == self::PARTIAL_TRANSFER) {
			$StaffTypes = TableRegistry::get('FieldOption.StaffTypes');
			$options = $StaffTypes->getList()->toArray();
			$attr['visible'] = true;
			$attr['type'] = 'date';
			$attr['value'] = new Date();
		} else {
			$attr['visible'] = false;
		}
		return $attr;
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

	public function viewAfterAction(Event $event, Entity $entity, $extra) {
		$toolbarButtons = $extra['toolbarButtons'];
		if ($entity->status == self::APPROVED) {
			if (isset($toolbarButtons['edit'])) {
				unset($toolbarButtons['edit']);
			}
			if (isset($toolbarButtons['remove'])) {
				unset($toolbarButtons['remove']);
			}
		}
	}
}
