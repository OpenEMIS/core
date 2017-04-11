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
UPDATE `workflow_models` SET `filter` = 'FieldOption.StaffLeaveTypes' WHERE `model` = 'Staff.Leaves';
UPDATE `import_mapping` SET `lookup_plugin` = 'FieldOption' WHERE `model` = 'Institution.Staff' AND `column_name` = 'staff_type_id';


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2467';