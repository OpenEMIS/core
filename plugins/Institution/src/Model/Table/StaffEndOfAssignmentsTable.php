<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class StaffEndOfAssignmentsTable extends ControllerActionTable {

	private $workflowEvents = [
 		[
 			'value' => 'Workflow.onApprove',
			'text' => 'Approval of Staff Termination',
 			'method' => 'OnApprove'
 		]
 	];

	public function initialize(array $config) {
		$this->table('staff_terminations');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
		$this->belongsTo('Positions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);

		// $this->behaviors()->get('ControllerAction')->config(['actions' => ['add' => false]]);
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Workflow.getEvents'] = 'getWorkflowEvents';
    	foreach ($this->workflowEvents as $event) {
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

    public function onApprove(Event $event, $id, Entity $workflowTransitionEntity) {
    	$entity = $this->get($id);
    	$effectiveDate = $entity->effectiveDate;
    	if ($effectiveDate->isPast()) {
    		$this->updateEndOfAssignment($entity);
    	}
    }

    public function updateEndOfAssignment($entity) {
    	$StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
    	$InstitutionStaff = TableRegistry::get('Institution.Staff');
    	$statuses = $StaffStatuses->findCodeList();

    	// Get the latest staff record entry
    	$staffRecord = $InstitutionStaff->find()
    		->where([
    			$InstitutionStaff->aliasField('staff_id') => $entity->staff_id,
    			$InstitutionStaff->aliasField('institution_position_id') => $entity->institution_position_id,
    			$InstitutionStaff->aliasField('staff_status_id') => $statuses['ASSIGNED']
    		])
    		->order([$InstitutionStaff->aliasField('created') => 'DESC'])
    		->first();

    	if (!empty($staffRecord)) {
    		// Staff end date logic should be handled on the staff table page and 
    		// should not be modified in this module
    		if (empty($staffRecord->end_date)) {
    			$staffRecord->end_date = $entity->effective_date;
    			$InstitutionStaff->save($staffRecord);
    		} else if ($staffRecord->end_date->lt($entity->effective_date)) {
				$staffRecord->end_date = $entity->effective_date;
    			$InstitutionStaff->save($staffRecord);
    		}
    	}
    }

    public function onLoginUpdateAssignment() {
    	$$this->find()->where([$this->aliasField('updated') => 1]);
    }

	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('updated', ['visible' => false]);
		$this->field('staff_id', ['before' => 'start_date']);

		$extra['config']['selectedLink'] = ['controller' => 'Institutions', 'action' => 'Staff', 'index'];
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra) {
		unset($extra['toolbarButtons']['add']);
	}

	public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$toolbarButtons = $extra['toolbarButtons'];
		$toolbarButtons['back']['url'] = [
			'plugin' => 'Institution',
			'controller' => 'Institutions',
			'action' => 'StaffUser',
			'0' => 'view',
			'1' => $entity->staff_id,
			'id' => $entity->institution_staff_id
		];

		// To investigate
		$this->field('id', ['type' => 'hidden', 'value' => $entity->id]);

		$this->field('updated', ['visible' => false]);
		$this->field('staff_id', ['type' => 'readonly', 'attr' => ['value' => $this->Users->get($entity->staff_id)->name_with_id]]);
		$this->field('institution_position_id', ['after' => 'staff_id', 'type' => 'readonly', 'attr' => ['value' => $this->Positions->get($entity->institution_position_id)->name]]);
	}

	private function initialiseVariable($entity) {
		
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$id = $this->Session->read($this->registryAlias().'.id');
		$InstitutionStaff = TableRegistry::get('Institution.Staff');
		$staff = $InstitutionStaff->get($id);
		$StaffEndOfAssignmentRecord = $this->find()
			->where([
				$this->aliasField('staff_id') => $staff->staff_id, 
				$this->aliasField('institution_position_id') => $staff->institution_position_id
			])
			->first();

		if (empty($StaffEndOfAssignmentRecord)) {
			$entity->institution_staff_id = $staff->id;
			$entity->staff_id = $staff->staff_id;
			$entity->institution_position_id = $staff->institution_position_id;
			$entity->institution_id = $staff->institution_id;
			$this->request->data[$this->alias()]['staff_id'] = $entity->staff_id;
			$this->request->data[$this->alias()]['institution_position_id'] = $entity->institution_position_id;
			$this->request->data[$this->alias()]['institution_id'] = $entity->institution_id;

			return true;
		} else {
			return false;
		}
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		$addOperation = false;
		if ($this->Session->check($this->registryAlias().'.id')) {
			$addOperation = $this->initialiseVariable($entity);
		}
		if (!$addOperation) {
			$event->stopPropagation();
			$url = $this->url('index');
			return $this->controller->redirect($url);
		}
	}
}
