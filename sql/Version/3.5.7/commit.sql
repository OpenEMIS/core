-- POCOR-2780
--

INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2780', NOW());

CREATE TABLE `z_2780_import_mapping` LIKE `import_mapping`;
INSERT INTO `z_2780_import_mapping`
SELECT *
FROM `import_mapping`;

INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) VALUES
('Institution.Staff', 'institution_position_id', 'Code', 1, 2, 'Institution', 'InstitutionPositions', 'position_no'),
('Institution.Staff', 'start_date', '( DD/MM/YYYY )', 2, 0, NULL, NULL, NULL),
('Institution.Staff', 'position_type', 'Code (Optional)', 3, 3, NULL, 'PositionTypes', 'id'),
('Institution.Staff', 'FTE', '(Not Required if Position Type is Full Time)', 4, 3, NULL, 'FTE', 'value'),
('Institution.Staff', 'staff_type_id', 'Code', 5, 1, 'FieldOption', 'StaffTypes', 'code'),
('Institution.Staff', 'staff_id', 'OpenEMIS ID', 6, 2, 'Staff', 'Staff', 'openemis_no')
;

CREATE TABLE `z_2780_security_functions` LIKE `security_functions`;
INSERT INTO `z_2780_security_functions`
SELECT *
FROM `security_functions`;

INSERT INTO `security_functions` (`name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Import Staff', 'Institutions', 'Institutions', 'Staff', 1016, NULL, NULL, NULL, NULL, 'ImportStaff.add|ImportStaff.template|ImportStaff.results|ImportStaff.downloadFailed|ImportStaff.downloadPassed', 1042, 1, NULL, NULL, 1, NOW());


-- POCOR-2820
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2820', NOW());


-- code here
ALTER TABLE `institution_student_admission`
        ADD COLUMN `institution_class_id` int(11) DEFAULT NULL after `education_grade_id`,
        ADD INDEX `institution_class_id` (`institution_class_id`);


-- POCOR-3026
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3026', NOW());


-- code here
UPDATE `security_functions` SET _view = 'Assessments.index|Results.index|Assessments.view' WHERE id = 1015;


-- POCOR-2255
--
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2255', NOW());

ALTER TABLE `institution_fee_types` CHANGE `amount` `amount` DECIMAL(15,2) NOT NULL;
ALTER TABLE `institution_fees` CHANGE `total` `total` DECIMAL(50,2) NULL DEFAULT NULL;
ALTER TABLE `student_fees` CHANGE `amount` `amount` DECIMAL(50,2) NOT NULL;

DROP TABLE IF EXISTS `fee_types`;
CREATE TABLE `fee_types` LIKE `institution_network_connectivities`;
INSERT INTO `fee_types`
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
WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'FeeTypes');
UPDATE `field_option_values` as `fov` set `fov`.`visible`=0 WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'FeeTypes');


-- POCOR-2734
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-2734', NOW());

-- security_group_users
-- Re-run patch from POCOR-3003
UPDATE security_group_users
JOIN institution_staff s ON s.security_group_user_id = security_group_users.id
JOIN institution_positions p ON p.id = s.institution_position_id
JOIN staff_position_titles t
    ON t.id = p.staff_position_title_id
    AND t.security_role_id <> security_group_users.security_role_id
SET security_group_users.security_role_id = t.security_role_id;


-- POCOR-2376
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-2376', NOW());

-- institution_student_admission
ALTER TABLE `institution_student_admission`
ADD COLUMN `new_education_grade_id` INT(11) NULL COMMENT '' AFTER `education_grade_id`,
ADD INDEX `new_education_grade_id` (`new_education_grade_id`);

UPDATE `institution_student_admission`
SET `new_education_grade_id` = `education_grade_id`
WHERE `type` = 2;

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'TransferApprovals', 'new_education_grade_id', 'Institutions -> Transfer Approvals', 'Education Grade', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'TransferRequests', 'new_education_grade_id', 'Institutions -> Transfer Requests', 'Education Grade', 1, 1, NOW());


-- POCOR-2874
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-2874', NOW());

 -- remove orphan
DELETE FROM `staff_custom_field_values`
WHERE NOT EXISTS (
        SELECT 1 FROM `staff_custom_fields`
                WHERE `staff_custom_fields`.`id` = `staff_custom_field_values`.`staff_custom_field_id`
        );


-- 3.5.7
UPDATE config_items SET value = '3.5.7' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
