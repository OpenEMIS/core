-- workflow_actions
UPDATE workflow_actions
INNER JOIN z_2902_workflow_actions 
	ON z_2902_workflow_actions.id = workflow_actions.id
SET workflow_actions.event_key = z_2902_workflow_actions.event_key;

DROP TABLE z_2902_workflow_actions;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`= 'POCOR-2902';