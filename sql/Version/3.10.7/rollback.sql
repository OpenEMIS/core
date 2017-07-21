-- POCOR-3877
DROP TABLE `security_role_functions`;

ALTER TABLE `z_3877_security_role_functions`
RENAME TO  `security_role_functions` ;

DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3877';


-- POCOR-4075
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


-- POCOR-3995
-- education_grades_subjects
DROP TABLE IF EXISTS `education_grades_subjects`;
RENAME TABLE `z_3995_education_grades_subjects` TO `education_grades_subjects`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3995';


-- 3.10.6
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.10.6' WHERE code = 'db_version';
