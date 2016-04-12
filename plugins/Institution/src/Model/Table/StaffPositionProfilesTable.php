<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class StaffPositionProfilesTable extends ControllerActionTable {
	private $staffChangeTypesList = [];

	private $workflowEvents = [
 		[
 			'value' => 'Workflow.onApprove',
			'text' => 'Approval of Changes to Staff Position Profiles',
 			'method' => 'OnApprove'
 		]
 	];

	public function validationDefault(Validator $validator) {
		return $validator
			->allowEmpty('end_date')
			->requirePresence('FTE')
			->requirePresence('staff_change_type_id')
			->requirePresence('staff_type_id')
			->add('end_date', 'ruleCompareDateReverse', [
				'rule' => ['compareDateReverse', 'start_date', true]
			]);
	}

	public function initialize(array $config) {
		$this->table('institution_staff_position_profiles');
		parent::initialize($config);
		$this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('StaffChangeTypes', ['className' => 'Staff.StaffChangeTypes', 'foreignKey' => 'staff_change_type_id']);
		$this->belongsTo('Institutions',	['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('StaffTypes',		['className' => 'FieldOption.StaffTypes']);
		$this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
		$this->belongsTo('Positions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
		$this->staffChangeTypesList = $this->StaffChangeTypes->findCodeList();
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Workflow.getEvents'] = 'getWorkflowEvents';
		$events['Workflow.beforeTransition'] = 'workflowBeforeTransition';
		$events['Workbench.Model.onGetList'] = 'onGetWorkbenchList';
		foreach($this->workflowEvents as $event) {
			$events[$event['value']] = $event['method'];
		}
		return $events;
	}

	public function addAfterSave(Event $event, $entity, $requestData, ArrayObject $extra) {
		if (!$entity->errors()) {
			$StaffTable = TableRegistry::get('Institution.Staff');
			$url = $this->url('view');
			$url['action'] = 'Staff';
			$url[1] = $entity['institution_staff_id'];
			$event->stopPropagation();
			$this->Session->write('Institution.StaffPositionProfiles.addSuccessful', true);
			return $this->controller->redirect($url);
		}
	}

	public function workflowBeforeTransition(Event $event, $requestData) {
		$errors = true;
		$approved = $this->Workflow->getStepsByModelCode($this->registryAlias(), 'APPROVED');
		$nextWorkflowStepId = $requestData['WorkflowTransitions']['workflow_step_id'];
		$id = $requestData['WorkflowTransitions']['model_reference'];
		if (in_array($nextWorkflowStepId, $approved)) {
			$data = $this->get($id)->toArray();
			$newEntity = $this->patchStaffProfile($data);
			if (is_null($newEntity)) {
				$message = ['StaffPositionProfiles.notExists'];
				$this->Session->write('Institution.StaffPositionProfiles.errors', $message);
			} else if ($newEntity->errors()) {
				$message = [];
				$errors = $newEntity->errors();
				foreach ($errors as $key => $value) {
					$msg = 'Institution.Staff.'.$key;
					if (is_array($value)) {
						foreach ($value as $k => $v) {
							$message[] = $msg.'.'.$k;
						}
					}
				}
				$this->Session->write('Institution.StaffPositionProfiles.errors', $message);
			} else {
				$errors = false;
			}

			if ($errors) {
				$event->stopPropagation();
				$url = $this->url('view');
				return $this->controller->redirect($url);
			}
		}
		
	}

	public function getWorkflowEvents(Event $event) {
		foreach ($this->workflowEvents as $key => $attr) {
			$this->workflowEvents[$key]['text'] = __($attr['text']);
		}
		return $this->workflowEvents;
	}

	public function onApprove(Event $event, $id, Entity $workflowTransitionEntity) {
		$data = $this->get($id)->toArray();
		$newEntity = $this->patchStaffProfile($data);
		$InstitutionStaff = TableRegistry::get('Institution.Staff');
		$InstitutionStaff->save($newEntity);
	}

	private function patchStaffProfile(array $data) {
		$InstitutionStaff = TableRegistry::get('Institution.Staff');
		$newEntity = null;

		// Get the latest staff record entry
		$staffRecord = $InstitutionStaff->find()
			->where([
				$InstitutionStaff->aliasField('id') => $data['institution_staff_id']
			])
			->first();

		// If the record exists
		if (!empty($staffRecord)) {
			unset($data['created']);
			unset($data['created_user_id']);
			unset($data['modified']);
			unset($data['modified_user_id']);
			unset($data['id']);
			$newEntity = $InstitutionStaff->patchEntity($staffRecord, $data);
		}

		return $newEntity;
	}

	public function onGetFTE(Event $event, Entity $entity) {
		$oldValue = ($entity->institution_staff->FTE * 100). '%';
		$newValue = '100%';
		if ($entity->FTE < 1) {	
			$newValue = ($entity->FTE * 100) . '%';
		}

		if ($newValue != $oldValue) {
			return $oldValue.' -> '.$newValue;
		} else {
			return $newValue;
		}	
	}

	public function onGetStartDate(Event $event, Entity $entity) {
		$oldValue = $entity->institution_staff->start_date;
		$newValue = $entity->start_date;
		if ($newValue != $oldValue) {
			return $this->formatDate($oldValue).' -> '.$this->formatDate($newValue);
		} else {
			return $newValue;
		}
	}

	public function onGetEndDate(Event $event, Entity $entity) {
		$oldValue = $entity->institution_staff->end_date;
		$newValue = $entity->end_date;
		if ($newValue != $oldValue) {
			if (!empty($oldValue)) {
				return $this->formatDate($oldValue).' -> '.$this->formatDate($newValue);
			} else {
				return __('Not Specified').' -> '.$this->formatDate($newValue);
			}
		} else {
			return $newValue;
		}
	}

	public function onGetStaffTypeId(Event $event, Entity $entity) {
		$oldValue = $entity->institution_staff->staff_type->name;
		$newValue = $entity->staff_type->name;
		if ($newValue != $oldValue) {
			return __($oldValue).' -> '.__($newValue);
		} else {
			return __($newValue);
		}
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('institution_staff_id', ['visible' => false]);
		$this->field('staff_id', ['before' => 'start_date']);
		$this->field('FTE', ['type' => 'select','visible' => ['view' => true, 'edit' => true, 'add' => true]]);
		$extra['config']['selectedLink'] = ['controller' => 'Institutions', 'action' => 'Staff', 'index'];
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra) {
		$this->Session->delete('Institution.StaffPositionProfiles.viewBackUrl');
		unset($extra['toolbarButtons']['add']);
	}

	public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$toolbarButtons = $extra['toolbarButtons'];
		$toolbarButtons['back']['url'] = [
			'plugin' => 'Institution',
			'controller' => 'Institutions',
			'action' => 'Staff',
			'0' => 'view',
			'1' => $entity->institution_staff_id
		];

		// To investigate
		$this->field('id', ['type' => 'hidden', 'value' => $entity->id]);
		$this->field('status_id', ['type' => 'hidden']);
		$this->field('institution_staff_id', ['visible' => true, 'type' => 'hidden', 'value' => $entity->institution_staff_id]);
		$this->field('institution_id', ['type' => 'readonly', 'attr' => ['value' => $this->Institutions->get($entity->institution_id)->name]]);
		$this->field('staff_id', ['type' => 'readonly', 'attr' => ['value' => $this->Users->get($entity->staff_id)->name_with_id]]);
		$this->field('staff_change_type_id');
		$this->field('staff_type_id', ['type' => 'select']);
		$fteOptions = ['0.25' => '25%', '0.5' => '50%', '0.75' => '75%', '1' => '100%'];
		$this->field('FTE', ['type' => 'select', 'options' => $fteOptions, 'value' => $entity->FTE]);
		$this->field('institution_position_id', ['after' => 'staff_id', 'type' => 'readonly', 'attr' => ['value' => $this->Positions->get($entity->institution_position_id)->name]]);
		$this->field('start_date');
		$this->field('end_date');
	}

	public function onUpdateFieldStaffChangeTypeId(Event $event, array $attr, $action, Request $request) {
		$attr['type'] = 'select';
		$attr['onChangeReload'] = true;
		return $attr;
	}

	public function onUpdateFieldStaffTypeId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$staffChangeTypes = $this->staffChangeTypesList;
			if ($request->data[$this->alias()]['staff_change_type_id'] == $staffChangeTypes['CHANGE_IN_STAFF_TYPE']) {
				$attr['type'] = 'select';
			} else {
				$attr['type'] = 'hidden';
				if ($this->Session->check('Institution.StaffPositionProfiles.staffRecord')) {
					$entity = $this->Session->read('Institution.StaffPositionProfiles.staffRecord');
					$attr['value'] = $entity->staff_type_id;
				}
			}
		}
		return $attr;
	}

	public function onUpdateFieldFTE(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$staffChangeTypes = $this->staffChangeTypesList;
			if (isset($request->data[$this->alias()])) {
				if ($request->data[$this->alias()]['staff_change_type_id'] == $staffChangeTypes['CHANGE_IN_FTE']) {
					$attr['type'] = 'select';
				} else {
					$attr['type'] = 'hidden';
					if ($this->Session->check('Institution.StaffPositionProfiles.staffRecord')) {
						$entity = $this->Session->read('Institution.StaffPositionProfiles.staffRecord');
						$attr['value'] = $entity->FTE;
					}
				}
			}
		}
		return $attr;
	}

	public function onUpdateFieldStartDate(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$staffChangeTypes = $this->staffChangeTypesList;
			if ($request->data[$this->alias()]['staff_change_type_id'] == $staffChangeTypes['END_OF_ASSIGNMENT']) {
				$attr['type'] = 'date';
			} else {
				$attr['type'] = 'hidden';
				if ($this->Session->check('Institution.StaffPositionProfiles.staffRecord')) {
					$entity = $this->Session->read('Institution.StaffPositionProfiles.staffRecord');
					$attr['value'] = $entity->start_date->format('Y-m-d');
				}
			}
		}
		return $attr;
	}

	public function onUpdateFieldEndDate(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$staffChangeTypes = $this->staffChangeTypesList;
			if ($request->data[$this->alias()]['staff_change_type_id'] == $staffChangeTypes['END_OF_ASSIGNMENT']) {
				$attr['type'] = 'date';
			} else {
				$attr['type'] = 'hidden';
				if ($this->Session->check('Institution.StaffPositionProfiles.staffRecord')) {
					$entity = $this->Session->read('Institution.StaffPositionProfiles.staffRecord');
					$attr['value'] = $entity->end_date->format('Y-m-d');
				}
			}
		}
		return $attr;
	}

	public function viewBeforeAction(Event $event, $extra) {
		if (isset($extra['toolbarButtons']['back']) && $this->Session->check('Institution.StaffPositionProfiles.viewBackUrl')) {
			$url = $this->Session->read('Institution.StaffPositionProfiles.viewBackUrl');
			$extra['toolbarButtons']['back']['url'] = $url;
		}

		if ($this->Session->check('Institution.StaffPositionProfiles.errors')) {
			$errors = $this->Session->read('Institution.StaffPositionProfiles.errors');
			$this->Alert->error('StaffPositionProfiles.errorApproval');
			foreach ($errors as $error) {
				$this->Alert->error($error);
			}
			$this->Session->delete('Institution.StaffPositionProfiles.errors');
		}
	}

	public function viewAfterAction(Event $event, Entity $entity, $extra) {
		$StaffTable = TableRegistry::get('Institution.Staff');
		$staffEntity = $StaffTable->find()
			->contain(['StaffTypes'])
			->where([$StaffTable->aliasField('id') => $entity->institution_staff_id])
			->first();
		$entity->institution_staff = $staffEntity;
	}

	private function initialiseVariable($entity) {
		$institutionStaffId = $this->request->query('institution_staff_id');
		if (is_null($institutionStaffId)) {
			return true;
		}
		$InstitutionStaff = TableRegistry::get('Institution.Staff');
		$staff = $InstitutionStaff->get($institutionStaffId);
		$approvedStatus = $this->Workflow->getStepsByModelCode($this->registryAlias(), 'APPROVED');
		$staffPositionProfilesRecord = $this->find()
			->where([
				$this->aliasField('institution_staff_id') => $staff->id,
				$this->aliasField('status_id').' NOT IN ' => $approvedStatus
			])
			->first();
		if (empty($staffPositionProfilesRecord)) {
			$entity->institution_staff_id = $staff->id;
			$entity->staff_id = $staff->staff_id;
			$entity->institution_position_id = $staff->institution_position_id;
			$entity->institution_id = $staff->institution_id;
			$entity->start_date = $staff->start_date;
			$entity->end_date = $staff->end_date;
			$entity->staff_type_id = $staff->staff_type_id;
			$entity->FTE = $staff->FTE;
			$this->Session->write('Institution.StaffPositionProfiles.staffRecord', $staff);
			$this->request->data[$this->alias()]['staff_id'] = $entity->staff_id;
			$this->request->data[$this->alias()]['institution_position_id'] = $entity->institution_position_id;
			$this->request->data[$this->alias()]['institution_id'] = $entity->institution_id;
			$this->request->data[$this->alias()]['staff_change_type_id'] = '';
			return false;
		} else {
			return $staffPositionProfilesRecord;
		}
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$entityClone = clone $entity;
		$this->Session->write('Institution.StaffPositionProfiles.staffRecord', $entityClone);
		$this->request->data[$this->alias()]['staff_change_type_id'] = $entity->staff_change_type_id;
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		$addOperation = $this->initialiseVariable($entity);
		if ($addOperation) {
			$institutionStaffId = $this->request->query('institution_staff_id');
			if (is_null($institutionStaffId)) {
				$url = $this->url('index');
			} else {
				$staffTableViewUrl = $this->url('view');
				$staffTableViewUrl['action'] = 'Staff';
				$staffTableViewUrl[1] = $institutionStaffId;
				$this->Session->write('Institution.StaffPositionProfiles.viewBackUrl', $staffTableViewUrl);
				$url = $this->url('view');
				$url[1] = $addOperation->id;
			}
			$event->stopPropagation();
			return $this->controller->redirect($url);
		}
	}

   // Workbench.Model.onGetList
	public function onGetWorkbenchList(Event $event, $AccessControl, ArrayObject $data) {
		$isAdmin = $AccessControl->isAdmin();

		$statusIds = $event->subject()->Workflow->getStepsByModelCode($this->registryAlias(), 'PENDING');
		$where = [];

		if (empty($statusIds)) {
			// returns empty list if there is no status mapping for workflows
			// otherwise it will return all rows without any conditions which may cause out of memory
			return [];
		} else {
			$where[$this->aliasField('status_id') . ' <> '] = $statusIds;
		}

		if ($isAdmin) {
			return []; // remove this line once workbench pagination is implemented
		} else {
			$userId = $event->subject()->Auth->user('id');
			$institutionIds = $AccessControl->getInstitutionsByUser();

			// Array to store security roles in each Institution
			$institutionRoles = [];
			foreach ($institutionIds as $institutionId) {
				$institutionRoles[$institutionId] = $this->Institutions->getInstitutionRoles($userId, $institutionId);
			}
			// End

			if (empty($institutionIds)) {
				return [];
			} else {
				$where[$this->aliasField('institution_id') . ' IN '] = $institutionIds;
			}

			$resultSet = $this
				->find()
				->contain(['Statuses', 'Users', 'Institutions', 'ModifiedUser', 'CreatedUser'])
				->where($where)
				->order([$this->aliasField('created')])
				->toArray();
			// End

			$WorkflowStepsRoles = TableRegistry::get('Workflow.WorkflowStepsRoles');
			$stepRoles = [];

			foreach ($resultSet as $key => $obj) {
				$institutionId = $obj->institution->id;
				$stepId = $obj->status_id;
				$roles = $institutionRoles[$institutionId];

				// Permission
				$hasAccess = false;

				// Array to store security roles in each Workflow Step
				if (!array_key_exists($stepId, $stepRoles)) {
					$stepRoles[$stepId] = $WorkflowStepsRoles->getRolesByStep($stepId);
				}
				// access is true if user roles exists in step roles
				$hasAccess = count(array_intersect_key($roles, $stepRoles[$stepId])) > 0;
				// End

				if ($hasAccess) {
					$requestTitle = sprintf('%s - %s of %s', $obj->status->name, $obj->user->name, $obj->institution->name);
					$url = [
						'plugin' => 'Institution',
						'controller' => 'Institutions',
						'action' => 'StaffPositionProfiles',
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
						'type' => __('Institution > Staff > Staff Position Profiles')
					];
				}
			}
		}
	}
}
