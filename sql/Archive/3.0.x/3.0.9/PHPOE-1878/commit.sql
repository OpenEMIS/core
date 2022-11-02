-- db_patches
CREATE TABLE IF NOT EXISTS `db_patches` (
  `issue` varchar(15) NOT NULL,
  PRIMARY KEY (`issue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
