-- POCOR-2208
-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2208', NOW());

UPDATE labels SET field_name = 'Deletable' WHERE module = 'WorkflowSteps' AND module_name = 'Workflow -> Steps' AND field_name = 'Removable';

-- 3.4.16
-- db_version
UPDATE config_items SET value = '3.4.16' WHERE code = 'db_version';
