<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;

class UpdateAssigneeShell extends Shell {
	// Workflow Steps - category
	const TO_DO = 1;
	const IN_PROGRESS = 2;
	const DONE = 3;

	public function initialize() {
		parent::initialize();
		$this->loadModel('Workflow.WorkflowModels');
		$this->loadModel('Security.SecurityGroupUsers');
	}

 	public function main() {
 		if (empty($this->args[0])) {
 			$workflowModelResults = $this->WorkflowModels->find()->all();

 			foreach ($workflowModelResults as $workflowModelEntity) {
 				$this->autoAssignAssignee($workflowModelEntity);
 			}
 		} else {
 			$triggeredModel = $this->args[0];
 			$groupId = $this->args[1];
	 		$userId = $this->args[2];
	 		$roleId = $this->args[3];

	 		$workflowModelEntity = $this->WorkflowModels->find()->where([$this->WorkflowModels->aliasField('model') => $triggeredModel])->first();

	 		$this->autoAssignAssignee($workflowModelEntity);
 		}
	}

	public function autoAssignAssignee(Entity $workflowModelEntity) {
		try {
			$model = TableRegistry::get($workflowModelEntity->model);
			$isSchoolBased = $workflowModelEntity->is_school_based;
			$this->out("Initialize Update Assignee Shell of " . $workflowModelEntity->name);

			$unassignedRecords = $model
				->find()
				->matching('Statuses', function ($q) {
					return $q->where(['Statuses.category <> ' => self::DONE]);
				})
				->where([$model->aliasField('assignee_id') => 0])
				->all();

			$this->out($workflowModelEntity->name. ' - Unassigned Records : ' . $unassignedRecords->count());

			foreach ($unassignedRecords as $key => $unassignedEntity) {
				$stepId = $unassignedEntity->status_id;
				$category = $unassignedEntity->_matchingData['Statuses']->category;
				$createdUserId = $unassignedEntity->created_user_id;

				$params = [
					'is_school_based' => $isSchoolBased,
					'workflow_step_id' => $stepId,
					'category' => $category,
					'created_user_id' => $createdUserId
				];

				if ($unassignedEntity->has('institution_id')) {
					$params['institution_id'] = $unassignedEntity->institution_id;
				}

				$assigneeId = $this->SecurityGroupUsers->getFirstAssignee($params);

				if (!empty($assigneeId)) {
					$this->out($workflowModelEntity->name.' : Affected Record Id: '.$unassignedEntity->id.'; Assignee Id: '.$assigneeId);

					$model->updateAll(
						['assignee_id' => $assigneeId],
						['id' => $unassignedEntity->id]
					);
				} else {
					$this->out($workflowModelEntity->name.' : Affected Record Id: '.$unassignedEntity->id.'; Assignee not found.');
				}
			}

			$this->out("End Processing Update Assignee Shell of ".$workflowModelEntity->name);
		} catch (\Exception $e) {
			$this->out('Update Assignee Shell Exception : ');
			$this->out($e->getMessage());
		}
	}
}
