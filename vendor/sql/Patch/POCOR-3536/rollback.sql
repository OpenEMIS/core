-- report_progress
ALTER TABLE `report_progress`
 MODIFY COLUMN `name` varchar(100) COLLATE utf8_general_ci NOT NULL;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3536';
