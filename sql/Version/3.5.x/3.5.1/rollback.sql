-- POCOR-2172
-- institution_students
ALTER TABLE `institution_students` DROP INDEX `student_status_id`;

-- config_item_options
UPDATE config_item_options SET `visible` = 1 WHERE `option_type` = 'authentication_type' AND `option` = 'LDAP';

-- staff_statuses
DROP TABLE `staff_statuses`;

UPDATE `institution_staff` INNER JOIN `z_2172_institution_staff` ON `z_2172_institution_staff`.`id` = `institution_staff`.`id`
SET `institution_staff`.`staff_status_id` = `z_2172_institution_staff`.staff_status_id;

DROP TABLE `z_2172_institution_staff`;

UPDATE `field_options` SET `visible`='1' WHERE `plugin` = 'FieldOption' AND `code` = 'StaffStatuses';

-- staff_change_types
DROP TABLE `staff_change_types`;

-- institution_staff_position_profiles
DROP TABLE `institution_staff_position_profiles`;

-- staff_assignments
DROP TABLE `institution_staff_assignments`;

-- labels
DELETE FROM `labels` WHERE `module` = 'StaffTransferRequests';
DELETE FROM `labels` WHERE `module` = 'StaffTransferApprovals';
DELETE FROM `labels` WHERE `module` = 'StaffPositionProfiles';

-- security_functions
DELETE FROM security_functions WHERE `id` IN (1039, 1040, 1041);

-- For staff_position_profiles
-- workflow_models
SET @workflowId := 0;
SELECT `id` INTO @workflowId FROM `workflows` WHERE `code` = 'STAFF-POSITION-PROFILE-01' AND `workflow_model_id` = @modelId;
DELETE FROM `workflow_steps` WHERE `workflow_id` = @workflowId;

DELETE FROM `workflow_actions` WHERE `workflow_actions`.`id` IN (
  SELECT `id` FROM `workflow_steps` WHERE `workflow_id` = @workflowId
);

SET @modelId := 0;
SELECT `id` INTO @modelId FROM `workflow_models` WHERE `model` = 'Institution.StaffPositionProfiles';
DELETE FROM `workflows` WHERE `code` = 'STAFF-POSITION-PROFILE-01' AND `workflow_model_id` = @modelId;

DELETE FROM `workflow_models` WHERE `name` = 'Institutions > Staff > Change in Assignment' AND `model` = 'Institution.StaffPositionProfiles';

DELETE FROM `workflow_statuses_steps` WHERE `workflow_status_id` IN (
  SELECT `id` FROM `workflow_statuses` WHERE `workflow_model_id` = @modelId
);

DELETE FROM `workflow_statuses` WHERE `workflow_model_id` = @modelId;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2172';


-- POCOR-2786
-- security_users
UPDATE `security_users` INNER JOIN `z_2786_security_users` ON `z_2786_security_users`.`id` = `security_users`.`id`
SET `security_users`.`date_of_birth` = `z_2786_security_users`.`date_of_birth`, `security_users`.`gender_id` = `z_2786_security_users`.`gender_id`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2786';


-- 3.5.1
UPDATE config_items SET value = '3.4.18' WHERE code = 'db_version';
