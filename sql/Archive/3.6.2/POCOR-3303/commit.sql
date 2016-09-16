-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3303', NOW());

-- security_functions
UPDATE `security_functions` SET `_execute` = 'Assessments.excel|ClassStudents.excel' WHERE `id` = 1015;