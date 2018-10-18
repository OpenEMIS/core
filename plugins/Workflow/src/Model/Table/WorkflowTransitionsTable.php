<?php
namespace Workflow\Model\Table;

use Cake\Event\Event;
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

	public function trackChanges(Entity $workflowModelEntity, Entity $affectedEntity, $assigneeId=0, $requestDataComment = null){
		$unassigned = '<'.__('Unassigned').'>';

		if ($affectedEntity->has('assignee')) {
			$origAssigneeName = $affectedEntity->assignee->name;
		} else {
			$origAssigneeName = $unassigned;
		}
		if ($assigneeId != 0) {
			$Users = TableRegistry::get('User.Users');
			$assigneeEntity = $Users
				->find()
				->select([
					$Users->aliasField('first_name'),
					$Users->aliasField('middle_name'),
					$Users->aliasField('third_name'),
					$Users->aliasField('last_name'),
					$Users->aliasField('preferred_name')
				])
				->where([$Users->aliasField('id') => $assigneeId])
				->first();
			$newAssigneeName = $assigneeEntity->name;
		} else {
			$newAssigneeName = $unassigned;
		}
		if ($origAssigneeName != $newAssigneeName) {
			// get stepName via contain, _matching, or status_id
			$stepName = '';
			if ($affectedEntity->has('status')) {
				$stepName = $affectedEntity->status->name;
			} elseif (!is_null($affectedEntity->_matchingData) && !is_null($affectedEntity->_matchingData['Statuses'])) {
				$stepName = $affectedEntity->_matchingData['Statuses']->name;
			} elseif ($affectedEntity->has('status_id')) {
				$WorkflowStepsTable = TableRegistry::get('Workflow.WorkflowSteps');
				$statusId = $affectedEntity->status_id;
				$stepEntity = $WorkflowStepsTable
					->find()
					->select([$WorkflowStepsTable->aliasField('name')])
					->where([$WorkflowStepsTable->aliasField('id') => $statusId])
					->first();
				$stepName = $stepEntity->name;
			}
			$comments =  __('From').' '.$origAssigneeName.' '.__('to').' '.$newAssigneeName;
			if (!is_null($requestDataComment)){
				$comments .=  "\n". $requestDataComment;
			}

			$data = [
				'comment' => $comments,
				'prev_workflow_step_name' => $stepName,
				'workflow_step_name' => $stepName,
				'workflow_action_name' => 'Administration - Change Assignee',
				'workflow_model_id' => $workflowModelEntity->id,
				'model_reference' => $affectedEntity->id,
				'created_user_id' => 1,
				'created' => new Time('NOW')
			];

			$entity = $this->newEntity($data);
			$this->save($entity);
		}
	}

	public function onWorkflowAddAfterSave(Event $event, Entity $entity)
	{
		$WorkflowSteps = TableRegistry::get('Workflow.WorkflowSteps');
		$stepEntity = $WorkflowSteps
			->find()
			->matching('Workflows.WorkflowModels')
			->where([$WorkflowSteps->aliasField('id') => $entity->status_id])
			->first();

		$workflowModel = $stepEntity->_matchingData['WorkflowModels'];

		if ($entity->has('action_type') && $entity->action_type == 'imported') {
			$workflowActionName = 'Administration - Record Imported';
		} else {
			$workflowActionName = 'Administration - Record Created';
		}

		$data = [
			'comment' => '',
			'prev_workflow_step_name' => 'New',
			'workflow_step_name' => $stepEntity->name,
			'workflow_action_name' => $workflowActionName,
			'workflow_model_id' => $workflowModel->id,
			'model_reference' => $entity->id,
			'created_user_id' => $entity->created_user_id,
			'created' => $entity->created
		];

		$entity = $this->newEntity($data);
		$this->save($entity);
	}
}
