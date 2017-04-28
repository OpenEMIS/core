-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3728', NOW());

-- alerts Table
UPDATE `alerts` SET `process_name` = 'AlertAttendance' WHERE `name` = 'Attendance';

INSERT INTO `alerts` (`name`, `process_name`, `process_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES  ('LicenseValidity', 'AlertLicenseValidity', NULL, NULL, NULL, '1', NOW()),
        ('RetirementWarning', 'AlertRetirementWarning', NULL, NULL, NULL, '1', NOW()),
        ('StaffEmployment', 'AlertStaffEmployment', NULL, NULL, NULL, '1', NOW()),
        ('StaffLeave', 'AlertStaffLeave', NULL, NULL, NULL, '1', NOW()),
        ('StaffType', 'AlertStaffType', NULL, NULL, NULL, '1', NOW());

-- alert_rules Table
ALTER TABLE `alert_rules` CHANGE `threshold` `threshold` VARCHAR(100) NOT NULL;
