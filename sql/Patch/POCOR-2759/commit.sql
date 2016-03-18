-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2759', NOW());

-- Backup tables
CREATE TABLE `z_2759_assessments` LIKE  `assessments`;
INSERT INTO `z_2759_assessments` SELECT * FROM `assessments` WHERE 1;

CREATE TABLE `z_2759_assessment_items` LIKE  `assessment_items`;
INSERT INTO `z_2759_assessment_items` SELECT * FROM `assessment_items` WHERE 1;

CREATE TABLE `z_2759_assessment_item_results` LIKE  `assessment_item_results`;
INSERT INTO `z_2759_assessment_item_results` SELECT * FROM `assessment_item_results` WHERE 1;

CREATE TABLE `z_2759_assessment_grading_types` LIKE  `assessment_grading_types`;
INSERT INTO `z_2759_assessment_grading_types` SELECT * FROM `assessment_grading_types` WHERE 1;

RENAME TABLE `assessment_statuses` TO `z_2759_assessment_statuses`;
RENAME TABLE `assessment_status_periods` TO `z_2759_assessment_status_periods`;
RENAME TABLE `institution_assessments` TO `z_2759_institution_assessments`;

-- assessments
ALTER TABLE `assessments` ADD `academic_period_id` INT(11) NOT NULL AFTER `type`;
ALTER TABLE `assessments` DROP `order`;
ALTER TABLE `assessments` DROP `visible`;

-- assessment_items
ALTER TABLE `assessment_items` ADD `weight` DECIMAL(6,2) NULL DEFAULT NULL AFTER `id`;
ALTER TABLE `assessment_items` DROP `pass_mark`;
ALTER TABLE `assessment_items` DROP `max`;
ALTER TABLE `assessment_items` DROP `result_type`;

-- assessment_item_results
ALTER TABLE `assessment_item_results` ADD `assessment_id` INT(11) NOT NULL AFTER `assessment_item_id`;
ALTER TABLE `assessment_item_results` ADD `education_subject_id` INT(11) NOT NULL AFTER `assessment_id`;
ALTER TABLE `assessment_item_results` ADD `assessment_period_id` INT(11) NOT NULL AFTER `academic_period_id`;
ALTER TABLE `assessment_item_results` CHANGE `marks` `marks` DECIMAL(6,2) NULL DEFAULT NULL;
ALTER TABLE `assessment_item_results` DROP PRIMARY KEY;
ALTER TABLE `assessment_item_results`
  ADD PRIMARY KEY (`assessment_id`, `education_subject_id`, `student_id`, `institution_id`, `academic_period_id`, `assessment_period_id`),
  ADD KEY `assessment_id` (`assessment_id`),
  ADD KEY `education_subject_id` (`education_subject_id`),
  ADD KEY `assessment_period_id` (`assessment_period_id`);
ALTER TABLE `assessment_item_results` PARTITION BY HASH(`academic_period_id`) PARTITIONS 8;

-- assessment_grading_types
ALTER TABLE `assessment_grading_types` ADD `pass_mark` INT(5) NOT NULL AFTER `name`;
ALTER TABLE `assessment_grading_types` ADD `max` INT(5) NOT NULL AFTER `pass_mark`;
ALTER TABLE `assessment_grading_types` ADD `result_type` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `max`;
ALTER TABLE `assessment_grading_types` DROP `order`;

-- assessment_periods
DROP TABLE IF EXISTS `assessment_periods`;
CREATE TABLE IF NOT EXISTS `assessment_periods` (
  `id` char(36) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `date_enabled` date NOT NULL,
  `date_disabled` date NOT NULL,
  `weight` decimal(6,2) NULL DEFAULT NULL,
  `assessment_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `assessment_periods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assessment_id` (`assessment_id`);


ALTER TABLE `assessment_periods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
