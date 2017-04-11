-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2281', NOW());

-- security_functions
UPDATE `security_functions` SET `_execute`='Surveys.excel' WHERE `id`=1025;
UPDATE `security_functions` SET `_execute`='Rubrics.excel' WHERE `id`=1029;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_add`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES (6003, 'Surveys', 'Reports', 'Reports', 'Reports', -1, 'Surveys.index', 'Surveys.add', 'Surveys.download', 6003, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_add`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES (6004, 'InstitutionRubrics', 'Reports', 'Reports', 'Reports', -1, 'InstitutionRubrics.index', 'InstitutionRubrics.add', 'InstitutionRubrics.download', 6004, 1, 1, NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_add`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES (6006, 'Audit', 'Reports', 'Reports', 'Reports', -1, 'Audit.index', 'Audit.add', 'Audit.download', 6006, 1, 1, NOW());