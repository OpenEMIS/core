-- security_functions
UPDATE `security_functions` SET `_view` = 'Assessments.index|Results.index', `_edit` = 'Results.indexEdit' WHERE `id` = 1015;

-- assessment_item_results
ALTER TABLE `assessment_item_results` CHANGE `student_id` `security_user_id` INT(11) NOT NULL;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2124';
