-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2208', NOW());

UPDATE labels SET field_name = 'Deletable' WHERE module = 'WorkflowSteps' AND module_name = 'Workflow -> Steps' AND field_name = 'Removable';