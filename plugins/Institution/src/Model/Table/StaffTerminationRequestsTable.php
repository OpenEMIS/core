<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class StaffTerminationRequestsTable extends ControllerActionTable {

	public function initialize(array $config) {
		$this->table('staff_terminations');
		parent::initialize($config);
		$this->registryAlias('Institution.StaffTerminations');
		$this->attachWorkflow(['model' => 'Institution.StaffTerminations']);
		$this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
		$this->belongsTo('Positions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
		$this->behaviors()->get('ControllerAction')->config(['actions' => ['index' => false]]);
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
		$entity->institution_staff_id = $staff->id;
		$entity->staff_id = $staff->staff_id;
		$entity->institution_position_id = $staff->institution_position_id;
		$entity->institution_id = $staff->institution_id;

		$this->request->data[$this->alias()]['staff_id'] = $entity->staff_id;
		$this->request->data[$this->alias()]['staff_name'] = $entity->staff_id;
		$this->request->data[$this->alias()]['institution_position_id'] = $entity->institution_position_id;
		$this->request->data[$this->alias()]['institution_id'] = $entity->institution_id;
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		$this->initialiseVariable($entity);
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$this->initialiseVariable($entity);
	}
}
