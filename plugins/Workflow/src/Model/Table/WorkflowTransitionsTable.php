<?php
namespace Workflow\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use App\Model\Table\AppTable;

class WorkflowTransitionsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('WorkflowRecords', ['className' => 'Workflow.WorkflowRecords']);
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		// Update workflow_step_id in workflow_records.
		$this->WorkflowRecords->updateAll(
			['workflow_step_id' => $entity->workflow_step_id],
			['id' => $entity->workflow_record_id]
		);

		return true;
	}
}
