<?php 
namespace Institution\Model\Behavior;

use ArrayObject;

use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;

class WorkflowBehavior extends Behavior
{
	private $workflowEvents = [
 		[
 			'value' => 'Workflow.onAssignBack',
			'text' => 'Assign Back to Creator',
			'description' => 'Performing this action will assign the current record back to creator.',
 			'method' => 'onAssignBack'
 		]
 	];

	public function initialize(array $config)
	{
		parent::initialize($config);

		$this->_table->belongsToMany('LinkedWorkflows', [
			'className' => 'Institution.InstitutionWorkflows',
			'joinTable' => 'institution_workflows_records',
			'foreignKey' => 'record_id',
			'targetForeignKey' => 'institution_workflow_id',
			'through' => 'Institution.InstitutionWorkflowsRecords',
			'dependent' => true
		]);
	}

	public function implementedEvents()
	{
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.viewEdit.beforeQuery'] = 'viewEditBeforeQuery';
		$events['ControllerAction.Model.view.afterAction'] = 'viewAfterAction';
		$events['Workflow.getEvents'] = 'getWorkflowEvents';
		foreach($this->workflowEvents as $event) {
			$events[$event['value']] = $event['method'];
		}
		return $events;
	}

	private function getWorkflowEvents(Event $event, ArrayObject $eventsObject)
	{
		foreach ($this->workflowEvents as $key => $attr) {
			$attr['text'] = __($attr['text']);
			$attr['description'] = __($attr['description']);
			$eventsObject[] = $attr;
		}
	}

	public function onGetLinkedWorkflows(Event $event, Entity $entity)
	{
		$linkedWorkflows = [];
		if ($entity->has('linked_workflows')) {
			foreach ($entity->linked_workflows as $key => $obj) {
				$linkedWorkflows[] = $obj['title'];
			}
		}

		return !empty($linkedWorkflows) ? implode(", ", $linkedWorkflows) : '';
	}

	public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
    	$query->contain(['LinkedWorkflows']);
    }

	public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
	{
		$model = $this->_table;
		$model->field('linked_workflows', ['type' => 'chosenSelect', 'entity' => $entity]);
	}
}
