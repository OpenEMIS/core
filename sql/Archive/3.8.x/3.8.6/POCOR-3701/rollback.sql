-- assessments
DROP TABLE IF EXISTS `assessments`;

RENAME TABLE `z_3701_assessments` TO `assessments`;

-- security_functions
UPDATE `security_functions` SET `_execute` = NULL WHERE `id` = 5010;

-- excel_templates
RENAME TABLE `z_3701_excel_templates` TO `excel_templates`;

-- Labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('ad8fa33a-c0d8-11e6-90e8-525400b263eb', 'ExcelTemplates', 'file_content', 'CustomExcels -> ExcelTemplates', 'Attachment', NULL, NULL, 1, NULL, NULL, 1, NOW());

-- security_functions
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(5059, 'Excel Templates', 'CustomExcels', 'Administration', 'CustomExcels', '5000', 'ExcelTemplates.index|ExcelTemplates.view', 'ExcelTemplates.edit', NULL, NULL, 'ExcelTemplates.download', 5059, 1, NULL, NULL, NULL, 1, NOW());

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3701';
