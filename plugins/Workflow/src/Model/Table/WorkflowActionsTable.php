<?php
namespace Workflow\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;

class WorkflowActionsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('WorkflowSteps', ['className' => 'Workflow.WorkflowSteps']);
		$this->belongsTo('NextWorkflowSteps', [
			'className' => 'Workflow.WorkflowSteps',
			'foreignKey' => 'next_workflow_step_id'
		]);
		$this->belongsTo('WorkflowEvents', ['className' => 'Workflow.WorkflowEvents']);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->requirePresence('name')
			->notEmpty('name', 'Please enter a name.');

		return $validator;
	}
}
