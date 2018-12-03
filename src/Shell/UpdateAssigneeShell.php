<?php
namespace App\Shell;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Console\Shell;

class UpdateAssigneeShell extends Shell
{
	// Workflow Steps - category
	const TO_DO = 1;
	const IN_PROGRESS = 2;
	const DONE = 3;

	public function initialize()
	{
		parent::initialize();
		$this->loadModel('Workflow.WorkflowModels');
		$this->loadModel('Workflow.WorkflowTransitions');
		$this->loadModel('Security.SecurityGroupUsers');
		$this->loadModel('Institution.Institutions');
	}

 	public function main()
 	{
		$id = !empty($this->args[1]) ? $this->args[1] : 0;
		$statusId = !empty($this->args[2]) ? $this->args[2] : 0;
		$groupId = !empty($this->args[3]) ? $this->args[3] : 0;
		$userId = !empty($this->args[4]) ? $this->args[4] : 0;
		$roleId = !empty($this->args[5]) ? $this->args[5] : 0;

		if (empty($this->args[0])) {
			// triggered from SecurityGroupUsers afterSave and afterDelete
			$workflowModelResults = $this->WorkflowModels->find()->all();

			foreach ($workflowModelResults as $workflowModelEntity) {
				$this->autoAssignAssignee($workflowModelEntity, $id, $statusId, $groupId, $userId, $roleId);
			}
		} else {
			// triggered from WorkflowBehavior workflowStepAfterSave
			$triggeredModel = $this->args[0];

			$workflowModelEntity = $this->WorkflowModels->find()->where([$this->WorkflowModels->aliasField('model') => $triggeredModel])->first();
			$this->autoAssignAssignee($workflowModelEntity, $id, $statusId, $groupId, $userId, $roleId);
		}
	}

	public function autoAssignAssignee(Entity $workflowModelEntity, $id=0, $statusId=0, $groupId=0, $userId=0, $roleId=0)
	{
		try {
			$model = TableRegistry::get($workflowModelEntity->model);
			$isSchoolBased = $workflowModelEntity->is_school_based;
			$this->out("Initialize Update Assignee Shell of " . $workflowModelEntity->name);

			$where = [];
			if (!empty($id)) {
				// only update records by status when user change security roles of a step
				$where[$model->aliasField('id')] = $id;
			} else if (!empty($statusId)) {
				// only update records by status when user change security roles of a step
				$where[$model->aliasField('status_id')] = $statusId;
			} else if (!empty($userId)) {
				// only update records by status when user is deleted/user role is updated
				$where[$model->aliasField('assignee_id')] = $userId;
			} else {
				$where[$model->aliasField('assignee_id')] = 0;
			}

			// for school based workflow, only update records of the school with the same security group id where the new staff is added to
			if ($isSchoolBased && !empty($groupId)) {
				$institutionEntity = $this->Institutions->find()->where([$this->Institutions->aliasField('security_group_id') => $groupId])->first();
				if ($institutionEntity) {
					$this->out($workflowModelEntity->name.' : Affected Institution Id: '.$institutionEntity->id);
					$where[$model->aliasField('institution_id')] = $institutionEntity->id;

					$event = $model->dispatchEvent('UpdateAssignee.onSetSchoolBasedConditions', [$institutionEntity, $where], $this);
					if ($event->result) {
						$where = $event->result;
					}
				}
			}

			$unassignedRecords = $model
				->find()
				->contain(['Assignees', 'Statuses.SecurityRoles'])
				->innerJoinWith('Statuses', function ($q) {
					return $q->where(['Statuses.category <> ' => self::DONE]);
				})
				->where($where)
				->all();

			$this->out($workflowModelEntity->name. ' - Unassigned Records : ' . $unassignedRecords->count());

			foreach ($unassignedRecords as $key => $unassignedEntity) {
				$stepId = $unassignedEntity->status_id;
				$category = $unassignedEntity->status->category;
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

				$event = $model->dispatchEvent('UpdateAssignee.onSetCustomAssigneeParams', [$unassignedEntity, $params], $this);
				if ($event->result) {
					$params = $event->result;
				}

				// POCOR-4666: Only reassign if the current assignee does not have any of the configured security roles for the workflow step
				$toReassign = true;
				if ($unassignedEntity->has('assignee_id') && !empty($unassignedEntity->assignee_id)) {
					$currentAssigneeRoles = [];
					$workflowStepRoles = Hash::extract($unassignedEntity->status, 'security_roles.{n}.id');

					if (!empty($workflowStepRoles)) {
						$roleQuery = $this->SecurityGroupUsers->find()
							->where([
								$this->SecurityGroupUsers->aliasField('security_user_id') => $unassignedEntity->assignee_id,
								$this->SecurityGroupUsers->aliasField('security_role_id IN ') => $workflowStepRoles
							]);

						if ($isSchoolBased) {
							if (array_key_exists('institution_id', $params) && !empty($params['institution_id'])) {
								$institutionObj = $this->Institutions->find()
									->contain(['Areas'])
									->where([$this->Institutions->aliasField('id') => $params['institution_id']])
									->first();
								$securityGroupId = $institutionObj->security_group_id;
								$areaObj = $institutionObj->area;

								$schoolBasedRoleQuery = clone $roleQuery;
								$schoolBasedRoles = $schoolBasedRoleQuery
									->where([$this->SecurityGroupUsers->aliasField('security_group_id') => $securityGroupId])
									->toArray();

								$regionBasedRoleQuery = clone $roleQuery;
								$regionBasedRoles = $regionBasedRoleQuery
									->matching('SecurityGroups.Areas', function ($q) use ($areaObj) {
										return $q->where([
										    'Areas.lft <= ' => $areaObj->lft,
										    'Areas.rght >= ' => $areaObj->lft
										]);
									})
									->toArray();

								$currentAssigneeRoles = $schoolBasedRoles + $regionBasedRoles;
							}
						} else {
							$currentAssigneeRoles = $roleQuery->toArray();
						}
					}
					$toReassign = empty($currentAssigneeRoles);
				}

				if ($toReassign) {
					$assigneeId = $this->SecurityGroupUsers->getFirstAssignee($params);

					if (!empty($assigneeId)) {
						$this->out($workflowModelEntity->name.' : Affected Record Id: '.$unassignedEntity->id.'; Assignee Id: '.$assigneeId);
					} else {
						$this->out($workflowModelEntity->name.' : Affected Record Id: '.$unassignedEntity->id.'; Set to unassigned.');
					}

					/* POCOR-3726 - Adding alert to workflow
					- This logic will add the update assignee commment to the workflow transition.
					- Put before the saving so the aftersave will be able to get the latest update assignee comment.
					*/
					$this->WorkflowTransitions->trackChanges($workflowModelEntity, $unassignedEntity, $assigneeId);

					// using save instead of updateAll to trigger aftersave.
					$unassignedEntity->assignee_id = $assigneeId;
					$model->save($unassignedEntity);
					// end of POCOR-3726
				}
			}

			$this->out("End Processing Update Assignee Shell of ".$workflowModelEntity->name);
		} catch (\Exception $e) {
			$this->out('Update Assignee Shell Exception : ');
			$this->out($e->getMessage());
		}
	}
}
