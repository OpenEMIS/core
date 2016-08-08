CREATE TABLE IF NOT EXISTS `db_patches` (
  `issue` varchar(15) NOT NULL,
  PRIMARY KEY (`issue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `db_patches` VALUES ('PHPOE-1657');
UPDATE `security_functions` SET `_view` = 'Surveys.index|Surveys.view', `_add` = NULL, `_edit` = 'Surveys.edit', `_delete` = 'Surveys.remove', `_execute` = NULL
WHERE `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Surveys' AND `name` = 'New';

UPDATE `security_functions` SET `_view` = 'Surveys.index|Surveys.view', `_add` = NULL, `_edit` = NULL, `_delete` = 'Surveys.remove', `_execute` = NULL
WHERE `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Surveys' AND `name` = 'Completed';

INSERT INTO `db_patches` VALUES ('PHPOE-1592');
INSERT INTO `labels` (`module`, `field`, `en`, `created_user_id`, `created`) VALUES ('StudentBehaviours', 'openemis_no', 'OpenEMIS ID', '1', NOW());
INSERT INTO `labels` (`module`, `field`, `en`, `created_user_id`, `created`) VALUES ('StaffBehaviours', 'openemis_no', 'OpenEMIS ID', '1', NOW());

INSERT INTO `db_patches` VALUES ('PHPOE-1857');

-- labels
DELETE FROM `labels` WHERE `module` = 'StudentPromotion';

INSERT INTO `labels` (`module`, `field`, `en`, `created_user_id`, `created`) VALUES ('StudentPromotion', 'openemis_no', 'OpenEMIS ID', 1, NOW());
INSERT INTO `labels` (`module`, `field`, `en`, `created_user_id`, `created`) VALUES ('StudentPromotion', 'student_id', 'Student', 1, NOW());
INSERT INTO `labels` (`module`, `field`, `en`, `created_user_id`, `created`) VALUES ('StudentPromotion', 'education_grade_id', 'Next Grade', 1, NOW());

INSERT INTO `db_patches` VALUES ('PHPOE-1878');

-- back table
CREATE TABLE IF NOT EXISTS z_1878_assessment_item_results LIKE assessment_item_results;
INSERT INTO z_1878_assessment_item_results SELECT * FROM assessment_item_results;

-- patch assessment_item_results
Update assessment_item_results as `AssessmentItemResults`
LEFT JOIN assessment_item_results as `TablePatch` ON
	`AssessmentItemResults`.`id` = (`TablePatch`.`id` - 1)
SET `AssessmentItemResults`.`assessment_grading_option_id` = `TablePatch`.`assessment_grading_option_id`
WHERE `TablePatch`.`security_user_id` = 0 AND `TablePatch`.`institution_site_id` = 0 AND `TablePatch`.`academic_period_id` = 0;

DELETE FROM `assessment_item_results` WHERE `security_user_id` = 0 AND `institution_site_id` = 0 AND `academic_period_id` = 0;

UPDATE `config_items` SET `value` = '3.0.9' WHERE `code` = 'db_version';
