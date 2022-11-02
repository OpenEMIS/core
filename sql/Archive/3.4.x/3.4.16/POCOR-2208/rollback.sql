UPDATE labels SET field_name = 'Removable' WHERE module = 'WorkflowSteps' AND module_name = 'Workflow -> Steps' AND field_name = 'Deletable';

-- db_patches
DELETE FROM `db_patches` WHERE  `issue` = 'POCOR-2208';