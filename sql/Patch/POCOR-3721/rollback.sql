-- license_classifications
DROP TABLE IF EXISTS `license_classifications`;

-- staff_licenses_classifications
DROP TABLE IF EXISTS `staff_licenses_classifications`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3721';
