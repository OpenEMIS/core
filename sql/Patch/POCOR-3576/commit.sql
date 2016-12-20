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
ALTER TABLE `assessment_item_results`
	ADD INDEX (`student_id`),
	ADD INDEX (`assessment_id`),
	ADD INDEX (`education_subject_id`),
	ADD INDEX (`institution_id`),
	ADD INDEX (`academic_period_id`),
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
