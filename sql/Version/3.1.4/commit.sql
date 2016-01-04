-- db_patches
CREATE TABLE IF NOT EXISTS `db_patches` (
  `issue` varchar(15) NOT NULL,
  PRIMARY KEY (`issue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `db_patches` VALUES ('PHPOE-1573');

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `created_user_id`, `created`) VALUES (uuid(), 'InstitutionRubrics', 'institution_site_section_id', 'Institutions -> Rubrics', 'Class', 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `created_user_id`, `created`) VALUES (uuid(), 'InstitutionRubrics', 'institution_site_class_id', 'Institutions -> Rubrics', 'Subject', 1, NOW());

-- institution_site_quality_rubrics
ALTER TABLE `institution_site_quality_rubrics` CHANGE `status` `status` INT(1) NOT NULL DEFAULT '0' COMMENT '-1 -> Expired, 0 -> New, 1 -> Draft, 2 -> Completed';

-- institution_site_quality_rubric_answers
ALTER TABLE `institution_site_quality_rubric_answers` CHANGE `rubric_criteria_option_id` `rubric_criteria_option_id` INT(11) NULL DEFAULT NULL;

-- security_functions
UPDATE `security_functions` SET `name` = 'New', `category` = 'Rubrics', `_view` = 'Rubrics.index|Rubrics.view|NewRubrics.index|NewRubrics.view', `_edit` = 'Rubrics.edit|NewRubrics.edit|RubricAnswers.edit', `_add` = NULL, `_delete` = 'Rubrics.remove|NewRubrics.remove', `_execute` = NULL WHERE `id` = 1026;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES
(1029, 'Completed', 'Institutions', 'Institutions', 'Rubrics', 1000, 'Rubrics.index|Rubrics.view|CompletedRubrics.index|CompletedRubrics.view|RubricAnswers.edit', NULL, NULL, 'Rubrics.remove|CompletedRubrics.remove', NULL, 1029, 1, 1, NOW());

UPDATE `config_items` SET `value` = '3.1.4' WHERE `code` = 'db_version';
