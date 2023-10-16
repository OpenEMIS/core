-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-4075', NOW());

-- reports
ALTER TABLE `reports`
 MODIFY COLUMN `excel_template_name` varchar(250) COLLATE utf8mb4_unicode_ci NULL,
 MODIFY COLUMN `excel_template` longblob NULL,
 MODIFY COLUMN `format` int(1) NOT NULL DEFAULT 1 COMMENT '1 -> CSV, 2 -> XLSX';

-- report_progress
ALTER TABLE `report_progress`
 ADD COLUMN `sql` text COLLATE utf8_general_ci NULL AFTER `params`;
