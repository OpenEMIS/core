-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1352');

-- workflow_models
INSERT INTO `workflow_models` (`name`, `model`, `filter`, `created_user_id`, `created`) VALUES
('Institution - Survey', 'Institution.InstitutionSurveys', NULL, 1, '0000-00-00 00:00:00');
