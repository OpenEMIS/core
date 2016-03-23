<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class StaffTerminationsTable extends ControllerActionTable {

	public function initialize(array $config) {
		$this->table('staff_terminations');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
		$this->belongsTo('Positions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('type', ['visible' => false]);
		$this->field('updated', ['visible' => false]);
		$this->field('staff_id', ['before' => 'start_date']);
	}

	public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$this->field('updated', ['visible' => false]);
		$this->field('staff_id', ['type' => 'readonly', 'attr' => ['value' => $this->Users->get($entity->staff_id)->name_with_id]]);
		$this->field('institution_position_id', ['after' => 'staff_id', 'type' => 'readonly', 'attr' => ['value' => $this->Positions->get($entity->institution_position_id)->name]]);
	}
}
