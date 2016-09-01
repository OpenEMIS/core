-- POCOR-3193
-- replace deletd records into institution_subjects table
INSERT INTO `institution_subjects`
SELECT * FROM `z_3193_institution_subjects`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3193';


-- POCOR-3272
-- replace values with original values from backup user contacts table
UPDATE `user_contacts`
INNER JOIN `z_3272_user_contacts`
ON `user_contacts`.`id` = `z_3272_user_contacts`.`id`
SET `user_contacts`.`value` = `z_3272_user_contacts`.`value`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3272';


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
