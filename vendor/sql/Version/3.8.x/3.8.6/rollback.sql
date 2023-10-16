-- POCOR-3550
-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3550';


-- POCOR-3711
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3711', NOW());

UPDATE `security_functions` SET `order` = `order` + 1 WHERE `id` < 2000 and `order` > 1016;
UPDATE `security_functions` SET `order` = 1017 WHERE id = 1016;
UPDATE `security_functions` SET `order` = 1018 WHERE id = 1044;
UPDATE `security_functions` SET `order` = 1019 WHERE id = 1003;

UPDATE `security_functions`
SET
`controller` = 'Institutions',
`_view` = 'Staff.index|Staff.view',
`_edit` = 'Staff.edit',
`_add` = 'Staff.add|getInstitutionPositions',
`_delete` = 'Staff.remove',
`_execute` = 'Staff.excel'
WHERE `id` = 1016;

UPDATE `security_functions`
SET
`name` = 'Overview',
`controller` = 'Institutions',
`_view` = 'StaffUser.view',
`_edit` = 'StaffUser.edit|StaffUser.pull',
`_add` = NULL,
`_delete` = NULL,
`_execute` = 'StaffUser.excel'
WHERE `id` = 3000;
shasanuddin@ip-192-168-0-72 [11:30:57] /var/www/html/openemis.org/dmo-dev/core ]$
shasanuddin@ip-192-168-0-72 [11:31:02] /var/www/html/openemis.org/dmo-dev/core ]$ vi sql/Version/3.8.6/
shasanuddin@ip-192-168-0-72 [11:31:09] /var/www/html/openemis.org/dmo-dev/core ]$ vi sql/Version/3.8.6/commit.sql
shasanuddin@ip-192-168-0-72 [11:31:16] /var/www/html/openemis.org/dmo-dev/core ]$
shasanuddin@ip-192-168-0-72 [11:31:17] /var/www/html/openemis.org/dmo-dev/core ]$ cat /var/www/html/openemis.org/dmo-dev/core/sql/Patch/POCOR-3711/rollback.sql
UPDATE `security_functions` SET `order` = `order` - 1 WHERE `id` < 2000 and `order` > 1016;
UPDATE `security_functions` SET `order` = 1017 WHERE `id` = 1016;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3711';


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


-- POCOR-2828
-- security_functions
UPDATE `security_functions` SET `_add`='StaffUser.add' WHERE `id`='1044';
UPDATE `security_functions` SET `_add`='Staff.add' WHERE `id`='1016';
UPDATE `security_functions` SET `_view`='index|view', `_edit`='edit', `_add`='add', `_delete`='remove' WHERE `id`='7000';

DELETE FROM `external_data_source_attributes` WHERE `attribute_field` = 'address_mapping';
DELETE FROM `external_data_source_attributes` WHERE `attribute_field` = 'postal_mapping';

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-2828';


-- 3.8.5.2
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.8.5.2' WHERE code = 'db_version';
