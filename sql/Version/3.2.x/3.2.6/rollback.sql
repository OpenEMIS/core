-- workflow_models
DELETE FROM `workflow_models` WHERE `model` = 'Institution.InstitutionSurveys';

-- workflow_steps
ALTER TABLE `workflow_steps` CHANGE `stage` `stage` INT(1) NULL DEFAULT NULL COMMENT '0 -> Open, 1 -> Closed';

-- workflow_transitions
ALTER TABLE `workflow_transitions` CHANGE `comment` `comment` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

-- staff_leaves
ALTER TABLE `staff_leaves` CHANGE `status_id` `status_id` INT(11) NOT NULL;

-- institution_site_surveys
ALTER TABLE `institution_site_surveys` CHANGE `status_id` `status` INT(1) NOT NULL DEFAULT '0' COMMENT '-1 -> Expired, 0 -> New, 1 -> Draft, 2 -> Completed';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1352';

-- workflow_status_mapping
DROP TABLE `workflow_statuses`;

-- workflow_status_mapping
DROP TABLE `workflow_status_mappings`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2225';

UPDATE `config_items` SET `value` = '3.2.5' WHERE `code` = 'db_version';
