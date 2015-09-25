-- security_functions
UPDATE `security_functions` SET `_view` = 'Assessments.index|Results.index', `_edit` = 'Results.indexEdit' WHERE `id` = 1015;

-- assessment_item_results
ALTER TABLE `assessment_item_results` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `assessment_item_results` CHANGE `marks` `marks` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `assessment_item_results` CHANGE `student_id` `security_user_id` INT(11) NOT NULL;
ALTER TABLE `assessment_item_results` CHANGE `institution_id` `institution_site_id` INT(11) NOT NULL;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2124';
