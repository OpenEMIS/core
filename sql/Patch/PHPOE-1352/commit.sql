-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1352');

-- workflow_models
INSERT INTO `workflow_models` (`name`, `model`, `filter`, `created_user_id`, `created`) VALUES
('Institutions > Survey > Forms', 'Institution.InstitutionSurveys', 'Survey.SurveyForms', 1, '0000-00-00 00:00:00');

-- workflow_steps
ALTER TABLE `workflow_steps` CHANGE `stage` `stage` INT(1) NULL DEFAULT NULL COMMENT '0 -> Open, 1 -> Pending For Approval, 2 -> Closed';

-- staff_leaves
ALTER TABLE `staff_leaves` CHANGE `status_id` `status_id` INT(11) NOT NULL COMMENT 'links to workflow_steps.id';

-- institution_site_surveys
ALTER TABLE `institution_site_surveys` CHANGE `status` `status_id` INT(11) NOT NULL COMMENT 'links to workflow_steps.id';
