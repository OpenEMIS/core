-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3728', NOW());

-- alerts Table
INSERT INTO `alerts` (`name`, `process_name`, `process_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES  ('LicenseValidity', 'LicenseValidityAlert', NULL, NULL, NULL, '1', NOW()),
        ('StaffLeave', 'StaffLeaveAlert', NULL, NULL, NULL, '1', NOW()),
        ('Employment', 'EmploymentAlert', NULL, NULL, NULL, '1', NOW()),
        ('RetirementWarning', 'RetirementWarningAlert', NULL, NULL, NULL, '1', NOW());

-- alert_rules Table
ALTER TABLE `alert_rules` CHANGE `threshold` `threshold` VARCHAR(100) NOT NULL;
