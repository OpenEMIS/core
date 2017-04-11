-- POCOR-2447
-- survey_rules
DROP TABLE `survey_rules`;

-- db_patches
DELETE FROM db_patches WHERE `issue` = 'POCOR-2447';


-- POCOR-2784
-- delete new table and temp

DROP TABLE IF EXISTS `institution_subject_students`;

-- db_rollback

ALTER TABLE `z_2784_institution_subject_students`
RENAME TO  `institution_subject_students`;

-- db_patches

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2784';


-- POCOR-2723
UPDATE security_functions
    SET
    `_view` = REPLACE (`_view`, 'Leave', 'Leaves'),
    `_edit` = REPLACE (`_edit`, 'Leave', 'Leaves'),
    `_add` = REPLACE (`_add`, 'Leave', 'Leaves'),
    `_delete` = REPLACE (`_delete`, 'Leave', 'Leaves'),
    `_execute` = REPLACE (`_execute`, 'Leave', 'Leaves')
WHERE id IN (3016, 7025);

UPDATE security_functions
    SET `name` = 'Leaves'
        WHERE id = 7025;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2723';


-- POCOR-3003
-- No rollback code needed

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3003';


-- POCOR-2451
-- custom_field_types
DELETE FROM `custom_field_types` WHERE `code` = 'REPEATER';

-- custom_modules
UPDATE `custom_modules` SET `supported_field_types` = REPLACE(`supported_field_types`, ',REPEATER', '') WHERE `model` = 'Institution.Institutions';

DELETE FROM `custom_modules` WHERE `code` = 'Institution > Repeater';

-- Restore tables
DROP TABLE IF EXISTS `institution_repeater_surveys`;
DROP TABLE IF EXISTS `institution_repeater_survey_answers`;
DROP TABLE IF EXISTS `institution_repeater_survey_table_cells`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2451';


-- POCOR-3006
-- update back the position_no value
UPDATE `institution_positions`
INNER JOIN `z_3006_institution_positions` ON `institution_positions`.`id` = `z_3006_institution_positions`.`id`
SET `institution_positions`.`position_no` = `z_3006_institution_positions`.`position_no`;

-- remove backup table
DROP TABLE IF EXISTS `z_3006_institution_positions`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3006';


-- POCOR-2992
-- labels
DELETE FROM `labels` WHERE `module` = 'StaffTransferApprovals' AND `field` = 'institution_position_id';
DELETE FROM `labels` WHERE `module` = 'StaffTransferApprovals' AND `field` = 'start_date';
DELETE FROM `labels` WHERE `module` = 'StaffTransferApprovals' AND `field` = 'FTE';
DELETE FROM `labels` WHERE `module` = 'StaffTransferApprovals' AND `field` = 'staff_type_id';
DELETE FROM `labels` WHERE `module` = 'StaffTransferApprovals' AND `field` = 'institution_id';
DELETE FROM `labels` WHERE `module` = 'StaffTransferApprovals' AND `field` = 'previous_institution_id';
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StaffTransferApprovals', 'institution_id', 'Institution -> Staff Transfer Approvals', 'To Be Approved By', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StaffTransferApprovals', 'previous_institution_id', 'Institution -> Staff Transfer Approvals', 'Requested By', 1, 1, NOW());

-- db_patches
DELETE FROM db_patches where `issue` = 'POCOR-2992';


-- 3.5.5
UPDATE config_items SET value = '3.5.5' WHERE code = 'db_version';
