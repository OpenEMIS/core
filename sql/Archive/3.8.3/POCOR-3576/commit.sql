-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3576', NOW());

-- excel_templates
DROP TABLE IF EXISTS `excel_templates`;
CREATE TABLE IF NOT EXISTS `excel_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module` varchar(100) NOT NULL,
  `file_name` varchar(250) DEFAULT NULL,
  `file_content` longblob,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains excel template for a specific report';

-- Labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('ad8fa33a-c0d8-11e6-90e8-525400b263eb', 'ExcelTemplates', 'file_content', 'CustomExcels -> ExcelTemplates', 'Attachment', NULL, NULL, 1, NULL, NULL, 1, NOW());

-- security_functions
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(5059, 'Excel Templates', 'CustomExcels', 'Administration', 'CustomExcels', '5000', 'ExcelTemplates.index|ExcelTemplates.view', 'ExcelTemplates.edit', NULL, NULL, 'ExcelTemplates.download', 5059, 1, NULL, NULL, NULL, 1, NOW());

-- assessment_item_results
RENAME TABLE `assessment_item_results` TO `z_3576_assessment_item_results`;

DROP TABLE IF EXISTS `assessment_item_results`;
CREATE TABLE IF NOT EXISTS `assessment_item_results` (
  `id` char(36) NOT NULL,
  `marks` decimal(6,2) DEFAULT NULL,
  `assessment_grading_option_id` int(11) DEFAULT NULL,
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `assessment_id` int(11) NOT NULL COMMENT 'links to assessments.id',
  `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id',
  `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `assessment_period_id` int(11) NOT NULL COMMENT 'links to assessment_periods.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`student_id`,`assessment_id`,`education_subject_id`,`institution_id`,`academic_period_id`,`assessment_period_id`),
  INDEX `assessment_grading_option_id` (`assessment_grading_option_id`),
  INDEX `student_id` (`student_id`),
  INDEX `assessment_id` (`assessment_id`),
  INDEX `education_subject_id` (`education_subject_id`),
  INDEX `institution_id` (`institution_id`),
  INDEX `academic_period_id` (`academic_period_id`),
  INDEX `assessment_period_id` (`assessment_period_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all the assessment results for an individual student in an institution' PARTITION BY HASH(`academic_period_id`) PARTITIONS 8;

INSERT INTO `assessment_item_results` SELECT * FROM `z_3576_assessment_item_results`;

-- assessment_items_grading_types
ALTER TABLE `assessment_items_grading_types`
	ADD INDEX (`assessment_grading_type_id`),
	ADD INDEX (`assessment_id`),
	ADD INDEX (`education_subject_id`),
	ADD INDEX (`assessment_period_id`);

-- examination_centres_institutions
ALTER TABLE `examination_centres_institutions`
	ADD INDEX (`examination_centre_id`),
	ADD INDEX (`institution_id`);

-- examination_centres_invigilators
ALTER TABLE `examination_centres_invigilators`
	ADD INDEX (`examination_centre_id`),
	ADD INDEX (`invigilator_id`);

-- examination_centre_rooms_invigilators
ALTER TABLE `examination_centre_rooms_invigilators`
	ADD INDEX (`examination_centre_room_id`),
	ADD INDEX (`invigilator_id`);

-- examination_centre_special_needs
ALTER TABLE `examination_centre_special_needs`
	ADD INDEX (`examination_centre_id`),
	ADD INDEX (`special_need_type_id`);

-- examination_centre_students
ALTER TABLE `examination_centre_students`
	ADD INDEX (`examination_centre_id`),
	ADD INDEX (`student_id`),
	ADD INDEX (`education_subject_id`);

-- examination_centre_subjects
ALTER TABLE `examination_centre_subjects`
	ADD INDEX (`examination_centre_id`),
	ADD INDEX (`education_subject_id`);

-- examination_items
ALTER TABLE `examination_items`
	ADD INDEX (`examination_id`),
	ADD INDEX (`education_subject_id`);

-- examination_item_results
ALTER TABLE `examination_item_results`
	ADD INDEX (`academic_period_id`),
	ADD INDEX (`examination_id`),
	ADD INDEX (`education_subject_id`),
	ADD INDEX (`student_id`);
