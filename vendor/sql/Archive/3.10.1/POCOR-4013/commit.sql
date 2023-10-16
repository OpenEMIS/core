-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-4013', NOW());

-- security_functions
UPDATE `security_functions` SET `_execute` = 'Assessments.excel|resultsExport' WHERE `id` = 1015;
