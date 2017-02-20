-- institution_textbooks
ALTER TABLE `institution_textbooks`
DROP COLUMN `education_grade_id`,
DROP INDEX `education_grade_id` ;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3647';
