-- db_patches
CREATE TABLE IF NOT EXISTS `db_patches` (
  `issue` varchar(15) NOT NULL,
  PRIMARY KEY (`issue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `db_patches` VALUES ('PHPOE-1573');

-- labels
INSERT INTO `labels` (`module`, `field`, `en`, `created_user_id`, `created`) VALUES ('InstitutionRubrics', 'institution_site_section_id', 'Class', 1, NOW());
INSERT INTO `labels` (`module`, `field`, `en`, `created_user_id`, `created`) VALUES ('InstitutionRubrics', 'institution_site_class_id', 'Subject', 1, NOW());

-- institution_site_quality_rubric_answers
ALTER TABLE `institution_site_quality_rubric_answers` CHANGE `rubric_criteria_option_id` `rubric_criteria_option_id` INT(11) NULL DEFAULT NULL;
