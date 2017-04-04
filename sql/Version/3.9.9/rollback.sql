-- POCOR-3728
-- alerts Table
DELETE FROM `alerts` WHERE `name` = 'LicenseValidity';
DELETE FROM `alerts` WHERE `name` = 'RetirementWarning';
DELETE FROM `alerts` WHERE `name` = 'StaffEmployment';
DELETE FROM `alerts` WHERE `name` = 'StaffLeave';
DELETE FROM `alerts` WHERE `name` = 'StaffType';

UPDATE `alerts` SET `process_name` = 'AttendanceAlert' WHERE `name` = 'Attendance';

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3728';


-- POCOR-3851
ALTER TABLE `contact_types` DROP `validation_pattern`;

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3851';


-- 3.9.8.1
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.9.8.1' WHERE code = 'db_version';
