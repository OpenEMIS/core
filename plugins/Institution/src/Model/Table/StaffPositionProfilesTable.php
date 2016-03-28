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
			->add('end_date', 'ruleCompareDateReverse', [
		        'rule' => ['compareDateReverse', 'start_date', true]
	    	]);
	}

	public function initialize(array $config) {
		$this->table('institution_staff_position_profiles');
		parent::initialize($config);
		$this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('Institutions',	['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('StaffTypes',		['className' => 'FieldOption.StaffTypes']);
		$this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
		$this->belongsTo('Positions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
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
		$value = '100%';
		if ($entity->FTE < 1) {
			$value = ($entity->FTE * 100) . '%';
		}
		return $value;
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
		$this->field('staff_type_id', ['type' => 'select']);
		$fteOptions = ['0.25' => '25%', '0.5' => '50%', '0.75' => '75%', '1' => '100%'];
		$this->field('FTE', ['type' => 'select', 'options' => $fteOptions, 'value' => $entity->FTE]);
		$this->field('institution_position_id', ['after' => 'staff_id', 'type' => 'readonly', 'attr' => ['value' => $this->Positions->get($entity->institution_position_id)->name]]);
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
			$this->request->data[$this->alias()]['staff_id'] = $entity->staff_id;
			$this->request->data[$this->alias()]['institution_position_id'] = $entity->institution_position_id;
			$this->request->data[$this->alias()]['institution_id'] = $entity->institution_id;
			return false;
		} else {
			return $staffPositionProfilesRecord;
		}
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
}
