<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;

class UpdateAssigneeShell extends Shell {
	// Workflow Steps - category
	const TO_DO = 1;
	const IN_PROGRESS = 2;
	const DONE = 3;

	public function initialize() {
		parent::initialize();
	}

 	public function main() {
 		$triggeredModel = $this->args[0];
 		$groupId = $this->args[1];
 		$userId = $this->args[2];
 		$roleId = $this->args[3];

 		$WorkflowModels = TableRegistry::get('Workflow.WorkflowModels');
 		$workflowModelEntity = $WorkflowModels->find()->where([$WorkflowModels->aliasField('model') => $triggeredModel])->first();
 		$isSchoolBased = $workflowModelEntity->is_school_based;

		try {
			$model = TableRegistry::get($workflowModelEntity->model);
			$this->out("Initialize Update Assignee Shell of " . $workflowModelEntity->name);

			$unassignedRecords = $model
				->find()
				->matching('Statuses', function ($q) {
					return $q->where(['Statuses.category <> ' => self::DONE]);
				})
				->where([$model->aliasField('assignee_id') => 0])
				->all();

			Log::write('debug', $workflowModelEntity->name. ' - Unassigned Records:');
			Log::write('debug', $unassignedRecords);

			$SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
			foreach ($unassignedRecords as $key => $unassignedEntity) {
				$stepId = $unassignedEntity->status_id;

				$params = [
					'is_school_based' => $isSchoolBased,
					'workflow_step_id' => $stepId
				];

				if ($unassignedEntity->has('institution_id')) {
					$params['institution_id'] = $unassignedEntity->institution_id;
				}

				$assigneeId = $SecurityGroupUsers->getFirstAssignee($params);

				if (!empty($assigneeId)) {
					$this->out($workflowModelEntity->name.' > Affected Record Id: '.$unassignedEntity->id.'; Assignee Id: '.$assigneeId);

					$model->updateAll(
						['assignee_id' => $assigneeId],
						['id' => $unassignedEntity->id]
					);
				}
			}

			$this->out("End Processing Update Assignee Shell of ".$workflowModelEntity->name);
		} catch (\Exception $e) {
			$this->out('Update Assignee Shell Exception : ');
			$this->out($e->getMessage());
		}
	}
}
