-- workflow_models
DELETE FROM `workflow_models` WHERE `model` = 'Institution.InstitutionSurveys';

-- workflow_steps
ALTER TABLE `workflow_steps` CHANGE `stage` `stage` INT(1) NULL DEFAULT NULL COMMENT '0 -> Open, 1 -> Closed';

-- staff_leaves
ALTER TABLE `staff_leaves` CHANGE `status_id` `status_id` INT(11) NOT NULL;

-- institution_site_surveys
ALTER TABLE `institution_site_surveys` CHANGE `status_id` `status` INT(1) NOT NULL DEFAULT '0' COMMENT '-1 -> Expired, 0 -> New, 1 -> Draft, 2 -> Completed';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1352';
