-- POCOR-3701
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


-- POCOR-3672
-- security_user_logins
ALTER TABLE `security_user_logins`
DROP COLUMN `ip_address`,
DROP COLUMN `session_id`;

-- single_logout
DROP TABLE `single_logout`;

-- config_product_list
UPDATE `config_product_lists`
INNER JOIN `z_3672_config_product_lists` ON `config_product_lists`.`id` = `z_3672_config_product_lists`.`id`
SET `config_product_lists`.`url` = `z_3672_config_product_lists`.`url`;

DROP TABLE `z_3672_config_product_lists`;

-- system_patches
DELETE FROM `system_patches` WHERE 'issue' = 'POCOR-3672';


-- POCOR-2828
-- security_functions
UPDATE `security_functions` SET `_add`='StaffUser.add' WHERE `id`='1044';
UPDATE `security_functions` SET `_add`='Staff.add' WHERE `id`='1016';
UPDATE `security_functions` SET `_view`='index|view', `_edit`='edit', `_add`='add', `_delete`='remove' WHERE `id`='7000';

DELETE FROM `external_data_source_attributes` WHERE `attribute_field` = 'address_mapping';
DELETE FROM `external_data_source_attributes` WHERE `attribute_field` = 'postal_mapping';

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-2828';


-- 3.8.5.1
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.8.5.1' WHERE code = 'db_version';
