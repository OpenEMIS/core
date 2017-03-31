-- alerts Table
DELETE FROM `alerts` WHERE `name` = 'LicenseValidity';
DELETE FROM `alerts` WHERE `name` = 'RetirementWarning';
DELETE FROM `alerts` WHERE `name` = 'StaffEmployment';
DELETE FROM `alerts` WHERE `name` = 'StaffLeave';
DELETE FROM `alerts` WHERE `name` = 'StaffType';

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3728';
