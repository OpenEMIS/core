-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3701', NOW());

-- assessments
RENAME TABLE `assessments` TO `z_3701_assessments`;

DROP TABLE IF EXISTS `assessments`;
CREATE TABLE IF NOT EXISTS `assessments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `excel_template_name` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `excel_template` longblob,
  `type` int(1) NOT NULL DEFAULT '1' COMMENT '1 -> Non-official, 2 -> Official',
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `academic_period_id` (`academic_period_id`),
  INDEX `education_grade_id` (`education_grade_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the assessment template for a specific grade';

INSERT INTO `assessments` (`id`, `code`, `name`, `description`, `excel_template_name`, `excel_template`, `type`, `academic_period_id`, `education_grade_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `Assessments`.`id`, `Assessments`.`code`, `Assessments`.`name`, `Assessments`.`description`, `ExcelTemplates`.`file_name`, `ExcelTemplates`.`file_content`, `Assessments`.`type`, `Assessments`.`academic_period_id`, `Assessments`.`education_grade_id`, `Assessments`.`modified_user_id`, `Assessments`.`modified`, `Assessments`.`created_user_id`, `Assessments`.`created`
FROM `z_3701_assessments` AS `Assessments` LEFT JOIN `excel_templates` AS `ExcelTemplates` ON 1 = 1;

-- security_functions
UPDATE `security_functions` SET `_execute` = 'Assessments.download' WHERE `id` = 5010;

-- excel_templates
RENAME TABLE `excel_templates` TO `z_3701_excel_templates`;

-- labels
DELETE FROM `labels` WHERE `id` = 'ad8fa33a-c0d8-11e6-90e8-525400b263eb';

-- security_functions
DELETE FROM `security_functions` WHERE `id` = 5059;
