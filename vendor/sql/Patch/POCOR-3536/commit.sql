-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3536', NOW());

-- report_progress
ALTER TABLE `report_progress`
 MODIFY COLUMN `name` varchar(200) COLLATE utf8_general_ci NOT NULL;
