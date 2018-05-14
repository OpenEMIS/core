<?php
namespace Workflow\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\I18n\Time;
use App\Model\Table\AppTable;

class WorkflowTransitionsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('WorkflowModels', ['className' => 'Workflow.WorkflowModels']);
	}

	public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Workflow.add.afterSave'] = 'onWorkflowAddAfterSave';
        return $events;
    }

	public function trackChanges(Entity $workflowModelEntity, Entity $affectedEntity, $assigneeId=0){
		$unassigned = '<'.__('Unassigned').'>';

		if ($affectedEntity->has('assignee')) {
			$origAssigneeName = $affectedEntity->assignee->name;
		} else {
			$origAssigneeName = $unassigned;
		}

		if ($assigneeId != 0) {
			$Users = TableRegistry::get('User.Users');
			$newAssigneeName = $Users->get($assigneeId)->name;
		} else {
			$newAssigneeName = $unassigned;
		}
		if ($origAssigneeName != $newAssigneeName) {
			$stepName = $affectedEntity->_matchingData['Statuses']->name;
			$data = [
				'comment' => __('From').' '.$origAssigneeName.' '.__('to').' '.$newAssigneeName,
				'prev_workflow_step_name' => $stepName,
				'workflow_step_name' => $stepName,
				'workflow_action_name' => __('Administration - Change Assignee'),
				'workflow_model_id' => $workflowModelEntity->id,
				'model_reference' => $affectedEntity->id,
				'created_user_id' => 1,
				'created' => new Time('NOW')
			];

			$entity = $this->newEntity($data);
			$this->save($entity);
		}
	}

	// public function createFirstTransitionRecord(Entity $entity)
	public function onWorkflowAddAfterSave(Entity $entity)
	{
		$WorkflowSteps = TableRegistry::get('Workflow.WorkflowSteps');
		$stepEntity = $WorkflowSteps
			->find()
            ->matching('Workflows.WorkflowModels')
			->where([$WorkflowSteps->aliasField('id') => $entity->status_id])
			->first();

		$workflowModel = $stepEntity->_matchingData['WorkflowModels'];

		$data = [
			'comment' => '',
			'prev_workflow_step_name' => __('New'),
			'workflow_step_name' => $stepEntity->name,
			'workflow_action_name' => __('Administration - Record Created'),
			'workflow_model_id' => $workflowModel->id,
			'model_reference' => $entity->id,
			'created_user_id' => $entity->created_user_id,
			'created' => new Time('NOW')
		];

		$entity = $this->newEntity($data);
		$this->save($entity);
	}
}
