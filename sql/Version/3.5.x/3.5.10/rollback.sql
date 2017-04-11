-- POCOR-3115
-- code here
-- Staff
DELETE FROM `security_role_functions` WHERE `security_function_id` = 1044;

-- Student
DELETE FROM `security_role_functions` WHERE `security_function_id` = 1043;


-- POCOR-2634
-- custom_field_types
UPDATE `custom_field_types` SET `is_mandatory` = 0 WHERE `code` = 'DROPDOWN';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-2634';


-- POCOR-3093
-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3093';


-- POCOR-3067
-- code here
-- Directoriy module
UPDATE `security_functions` SET `name` = 'Guardians' WHERE id = 7009;
UPDATE `security_functions` SET `order` = 7048 WHERE id = 7009;

UPDATE `security_functions` SET `name` = 'New Guardian Profile' WHERE id = 7047;
UPDATE `security_functions` SET `_view` = NULL WHERE id = 7047;
UPDATE `security_functions` SET `_edit` = NULL WHERE id = 7047;
UPDATE `security_functions` SET `order` = 7047 WHERE id = 7047;

UPDATE `security_functions` SET `order` = 7009 WHERE id = 7009;


-- institutions module
UPDATE `security_functions` SET `name` = 'Guardians' WHERE id = 2010;
UPDATE `security_functions` SET `category` = 'Students - General' WHERE id = 2010;

DELETE FROM `security_functions` WHERE `security_functions`.`id` = 2029;

UPDATE `security_functions` SET `order` = 2002 WHERE id = 2010;


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3067';

-- Guardian Relations
DROP TABLE `guardian_relations`;
UPDATE `field_option_values` set `visible`=1 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'GuardianRelations');
UPDATE `field_options` SET `plugin` = 'FieldOption' WHERE `code` = 'GuardianRelations';


-- Staff Type
DROP TABLE `staff_types`;
UPDATE `field_option_values` set `visible`=1 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'StaffTypes');
UPDATE `field_options` SET `plugin` = 'FieldOption' WHERE `code` = 'StaffTypes';


-- Staff Leave Type
DROP TABLE `staff_leave_types`;
UPDATE `field_option_values` set `visible`=1 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'StaffLeaveTypes');
UPDATE `field_options` SET `plugin` = 'FieldOption' WHERE `code` = 'StaffLeaveTypes';


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2467';


-- 3.5.9
UPDATE config_items SET value = '3.5.9' WHERE code = 'db_version';
