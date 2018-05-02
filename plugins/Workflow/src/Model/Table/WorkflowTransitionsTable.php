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

	public function trackChanges(Entity $workflowModelEntity, Entity $affectedEntity, $assigneeId=0) {
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

			// get stepName via contain, _matching, or status_id
			$stepName = '';
			if ($affectedEntity->has('status')) {
				$stepName = $affectedEntity->status->name;
			} elseif (!is_null($affectedEntity->_matchingData) && !is_null($affectedEntity->_matchingData['Statuses'])) {
				$stepName = $affectedEntity->_matchingData['Statuses']->name;
			} elseif ($affectedEntity->has('status_id')) {
				$WorkflowStepsTable = TableRegistry::get('Workflow.WorkflowSteps');
				$statusId = $affectedEntity->status_id;
				$stepName = $WorkflowStepsTable->get($statusId)->name;
			}

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
}
