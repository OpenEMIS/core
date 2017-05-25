-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3937', NOW());

-- import_mapping
INSERT INTO `import_mapping` (`id`, `model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES
(112, 'Staff.Salaries', 'salary_date', '( DD/MM/YYYY )', 1, 0, NULL, NULL, NULL),
(113, 'Staff.Salaries', 'comment', '(Optional)', 2, 0, NULL, NULL, NULL),
(114, 'Staff.Salaries', 'gross_salary', NULL, 3, 0, NULL, NULL, NULL);

-- security_functions
UPDATE `security_functions`
SET `order` = `order` + 1
WHERE `order` >= 3022
AND `order` < 4000;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES ('3039', 'Import Staff Salaries', 'Staff', 'Institutions', 'Staff - Finance', '3000', NULL, NULL, NULL, NULL, 'ImportSalaries.add|ImportSalaries.template|ImportSalaries.results|ImportSalaries.downloadFailed|ImportSalaries.downloadPassed', '3022', '1', NULL, NULL, NULL, '1', '2017-05-11');

UPDATE `security_functions` SET `_execute` = 'Salaries.excel' WHERE `security_functions`.`id` = 3020;

UPDATE `security_functions`
SET `order` = `order` + 1
WHERE `order` >= 7038
AND `order` < 8000;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES ('7052', 'Import Staff Salaries', 'Directories', 'Directory', 'Staff - Finance', '7000', NULL, NULL, NULL, NULL, 'ImportSalaries.add|ImportSalaries.template|ImportSalaries.results|ImportSalaries.downloadFailed|ImportSalaries.downloadPassed', '7038', '1', NULL, NULL, NULL, '1', '2017-05-11');

UPDATE `security_functions` SET `_execute` = 'StaffSalaries.excel' WHERE `security_functions`.`id` = 7034;