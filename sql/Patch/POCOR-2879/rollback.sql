INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-2879', NOW());

ALTER TABLE `institution_subjects`
DROP COLUMN `education_grade_id`;
