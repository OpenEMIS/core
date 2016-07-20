-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2467', NOW());


-- Guardian Relations
DROP TABLE IF EXISTS `guardian_relations`;
CREATE TABLE `guardian_relations` LIKE `institution_network_connectivities`;
INSERT INTO `guardian_relations`
SELECT
    `fov`.`id` as `id`,
    `fov`.`name` as `name`,
    `fov`.`order` as `order`,
    `fov`.`visible` as `visible`,
    `fov`.`editable` as `editable`,
    `fov`.`default` as `default`,
    `fov`.`international_code` as `international_code`,
    `fov`.`national_code` as `national_code`,
    `fov`.`modified_user_id` as `modified_user_id`,
    `fov`.`modified` as `modified`,
    `fov`.`created_user_id` as `created_user_id`,
    `fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'GuardianRelations');

UPDATE `field_option_values` set `visible`=0 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'GuardianRelations');

UPDATE `field_options` SET `plugin` = 'Student' WHERE `code` = 'GuardianRelations';


-- Staff Type
DROP TABLE IF EXISTS `staff_types`;
CREATE TABLE `staff_types` LIKE `institution_network_connectivities`;
INSERT INTO `staff_types`
SELECT
    `fov`.`id` as `id`,
    `fov`.`name` as `name`,
    `fov`.`order` as `order`,
    `fov`.`visible` as `visible`,
    `fov`.`editable` as `editable`,
    `fov`.`default` as `default`,
    `fov`.`international_code` as `international_code`,
    `fov`.`national_code` as `national_code`,
    `fov`.`modified_user_id` as `modified_user_id`,
    `fov`.`modified` as `modified`,
    `fov`.`created_user_id` as `created_user_id`,
    `fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'StaffTypes');

UPDATE `field_option_values` set `visible`=0 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'StaffTypes');

UPDATE `field_options` SET `plugin` = 'Staff' WHERE `code` = 'StaffTypes';


-- Staff Leave Type
DROP TABLE IF EXISTS `staff_leave_types`;
CREATE TABLE `staff_leave_types` LIKE `institution_network_connectivities`;
INSERT INTO `staff_leave_types`
SELECT
    `fov`.`id` as `id`,
    `fov`.`name` as `name`,
    `fov`.`order` as `order`,
    `fov`.`visible` as `visible`,
    `fov`.`editable` as `editable`,
    `fov`.`default` as `default`,
    `fov`.`international_code` as `international_code`,
    `fov`.`national_code` as `national_code`,
    `fov`.`modified_user_id` as `modified_user_id`,
    `fov`.`modified` as `modified`,
    `fov`.`created_user_id` as `created_user_id`,
    `fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'StaffLeaveTypes');

UPDATE `field_option_values` set `visible`=0 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'StaffLeaveTypes');

UPDATE `field_options` SET `plugin` = 'Staff' WHERE `code` = 'StaffLeaveTypes';
UPDATE `workflow_models` SET `filter` = 'Staff.StaffLeaveTypes' WHERE `model` = 'Staff.Leaves';
UPDATE `import_mapping` SET `lookup_plugin` = 'Staff' WHERE `model` = 'Institution.Staff' AND `column_name` = 'staff_type_id';