-- reports
ALTER TABLE `reports`
 MODIFY COLUMN `excel_template_name` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
 MODIFY COLUMN `excel_template` longblob NOT NULL,
 MODIFY COLUMN `format` int(1) NOT NULL DEFAULT 1 COMMENT '1 -> Excel';

-- report_progress
ALTER TABLE `report_progress`
 DROP COLUMN `sql`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-4075';
