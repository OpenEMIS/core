<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class StaffTransfersTable extends ControllerActionTable {

	private $workflowEvents = [
 		[
 			'value' => 'Workflow.onApprove',
			'text' => 'Approval of Changes to Staff Position Profiles',
 			'method' => 'OnApprove'
 		]
 	];

	public function initialize(array $config) {
		$this->table('institution_staff_assignments');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('Positions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
		$this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
		$this->belongsTo('FromInstitutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'previous_institution_id']);
		$this->belongsTo('StaffTypes', ['className' => 'FieldOption.StaffTypes']);
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Workflow.getEvents'] = 'getWorkflowEvents';
		$events['Workflow.beforeTransition'] = 'workflowBeforeTransition';
    	foreach($this->workflowEvents as $event) {
    		$events[$event['value']] = $event['method'];
    	}
		return $events;
	}
	
	public function getWorkflowEvents(Event $event) {
    	foreach ($this->workflowEvents as $key => $attr) {
    		$this->workflowEvents[$key]['text'] = __($attr['text']);
    	}
    	return $this->workflowEvents;
    }

	public function workflowBeforeTransition(Event $event, $requestData) {
		$errors = true;
		$approved = $this->Workflow->getStepsByModelCode($this->registryAlias(), 'APPROVED');
		$nextWorkflowStepId = $requestData['WorkflowTransitions']['workflow_step_id'];
		$id = $requestData['WorkflowTransitions']['model_reference'];
		if (in_array($nextWorkflowStepId, $approved)) {
			$data = $this->get($id)->toArray();
	    	$newEntity = $this->newStaffProfileRecord($data);
	    	if (is_null($newEntity)) {
	    		// $message = ['StaffPositionProfiles.notExists'];
	    		$this->Session->write('Institution.StaffTransfers.errors', $message);
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
	    		$this->Session->write('Institution.StaffTransfers.errors', $message);
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

    public function onApprove(Event $event, $id, Entity $workflowTransitionEntity) {
    	$data = $this->get($id)->toArray();
    	$newEntity = $this->newStaffProfileRecord($data);
    	$InstitutionStaff = TableRegistry::get('Institution.Staff');
    	$InstitutionStaff->save($newEntity);
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

	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('type', ['visible' => false]);
		$this->field('updated', ['visible' => false]);
		$this->field('status_id', ['before' => 'staff_id']);
		$this->field('staff_id', ['before' => 'start_date']);
		$this->field('end_date', ['visible' => false]);
		$this->field('FTE', ['type' => 'select','visible' => ['view' => true, 'edit' => true, 'add' => true]]);
	}

	public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$this->field('id', ['type' => 'hidden', 'value' => $entity->id]);
		$this->field('status_id', ['type' => 'hidden']);
		$this->field('institution_id', ['type' => 'readonly', 'attr' => ['value' => $this->Institutions->get($entity->institution_id)->name], 'value' => $entity->institution_id]);
		$this->field('institution_position_id', ['after' => 'staff_id', 'type' => 'readonly', 'attr' => ['value' => $this->Positions->get($entity->institution_position_id)->name]]);
		$this->field('previous_institution_id', ['type' => 'readonly', 'attr' => ['value' => $this->Institutions->get($entity->previous_institution_id)->name]]);
		$this->field('staff_id', ['type' => 'readonly', 'attr' => ['value' => $this->Users->get($entity->staff_id)->name_with_id]]);
		$this->field('staff_type_id', ['type' => 'readonly', 'attr' => ['value' => $this->StaffTypes->get($entity->staff_type_id)->name]]);
		$fteOptions = ['0.25' => '25%', '0.5' => '50%', '0.75' => '75%', '1' => '100%'];
		$this->field('FTE', ['type' => 'readonly', 'options' => $fteOptions, 'value' => $entity->FTE]);
		
	}

	private function initialiseVariable($entity, $institutionStaffData) {
		$institutionStaff = $institutionStaffData;
		if (is_null($institutionStaff)) {
			return true;
		}
		$approvedStatus = $this->Workflow->getStepsByModelCode($this->registryAlias(), 'APPROVED');
		$staffTransfer = $this->find()
			->where([
				$this->aliasField('staff_id') => $institutionStaff['staff_id'],
				$this->aliasField('previous_institution_id') => $institutionStaff['transfer_from'],
				$this->aliasField('institution_position_id') => $institutionStaff['institution_position_id'],
				$this->aliasField('status_id').' NOT IN ' => $approvedStatus
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
			return false;
		} else {
			return $staffTransfer;
		}
	}

	public function afterAction(Event $event, $extra) {
		// To not allow the institution that requested for the transfer in of staff to approve the transfer request
		if ($this->action == 'view') {
			$entity = $extra['entity'];
			$institutionId = $this->Session->read('Institution.Institutions.id');
			if ($entity->institution_id == $institutionId) {
				$toolbarButtons = $extra['toolbarButtons'];
				if (isset($toolbarButtons['approve'])) {
					unset($toolbarButtons['approve']);
				}
				if (isset($toolbarButtons['reject'])) {
					unset($toolbarButtons['reject']);
				}
				if (isset($toolbarButtons['more'])) {
					unset($toolbarButtons['more']);
				}
			} 
			// else if ($entity->previous_institution_id == $institutionId) {
			// 	$toolbarButtons = $extra['toolbarButtons'];
			// 	if (isset($toolbarButtons['edit'])) {
			// 		unset($toolbarButtons['edit']);
			// 	}
			// 	if (isset($toolbarButtons['remove'])) {
			// 		unset($toolbarButtons['remove']);
			// 	}
			// }
		}
	}

	public function indexBeforeQuery(Event $event, Query $query, $extra) {
		$institutionId = $this->Session->read('Institution.Institutions.id');
		// $query->where([$this->aliasField('previous_institution_id') => $institutionId], [], true);
		// $query->where([$this->aliasField('institution_id') => $institutionId], [], true);
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
}
