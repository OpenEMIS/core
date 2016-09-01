-- POCOR-3340
-- workflow_actions
UPDATE `workflow_actions`
INNER JOIN `z_3340_workflow_actions` ON `z_3340_workflow_actions`.`id` = `workflow_actions`.`id`
SET `workflow_actions`.`event_key` = `z_3340_workflow_actions`.`event_key`;

DROP TABLE `z_3340_workflow_actions`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3340';


-- POCOR-3138
-- security_user
ALTER TABLE `security_users` DROP `identity_number`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3138';


-- POCOR-3338
-- workflow_actions
UPDATE `workflow_actions`
INNER JOIN `z_3338_workflow_actions` ON `z_3338_workflow_actions`.`id` = `workflow_actions`.`id`
SET `workflow_actions`.`event_key` = `z_3338_workflow_actions`.`event_key`;

DROP TABLE `z_3338_workflow_actions`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3338';


-- 3.6.2
UPDATE config_items SET value = '3.6.2' WHERE code = 'db_version';
