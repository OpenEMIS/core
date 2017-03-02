-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3647', NOW());

-- institution_textbooks
ALTER TABLE `institution_textbooks`
ADD COLUMN `education_grade_id` INT(11) NULL AFTER `academic_period_id`,
ADD INDEX `education_grade_id` (`education_grade_id`);

UPDATE `institution_textbooks`
INNER JOIN `textbooks` ON `institution_textbooks`.`textbook_id` = `textbooks`.`id`
SET `institution_textbooks`.`education_grade_id` = `textbooks`.`education_grade_id`;

ALTER TABLE `institution_textbooks`
CHANGE COLUMN `education_grade_id` `education_grade_id` INT(11) NOT NULL ;
