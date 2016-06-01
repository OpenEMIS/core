-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-2902', NOW());

-- workflow_actions
UPDATE workflow_actions
INNER JOIN workflow_steps WorkflowSteps
	ON WorkflowSteps.id = workflow_actions.workflow_step_id
    AND WorkflowSteps.stage = 0
INNER JOIN workflow_steps NextWorkflowSteps
	ON NextWorkflowSteps.id = workflow_actions.next_workflow_step_id
    AND NextWorkflowSteps.name = 'Closed'
SET workflow_actions.event_key = 'Workflow.onDeleteRecord'
WHERE workflow_actions.action = 1;