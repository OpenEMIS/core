INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-2879', NOW());

ALTER TABLE `institution_subjects`
ADD COLUMN `education_grade_id` INT NULL AFTER `institution_id`;

UPDATE institution_subjects
INNER JOIN institution_class_subjects ON institution_subjects.id = institution_class_subjects.institution_subject_id
INNER JOIN institution_class_grades ON institution_class_grades.institution_class_id = institution_class_subjects.institution_class_id
INNER JOIN education_grades_subjects ON education_grades_subjects.education_grade_id = institution_class_grades.education_grade_id AND institution_subjects.education_subject_id = education_grades_subjects.education_subject_id
SET institution_subjects.education_grade_id = education_grades_subjects.education_grade_id;

UPDATE `institution_subjects` SET `education_grade_id` = 0 WHERE `education_grade_id` IS NULL;

ALTER TABLE `institution_subjects`
CHANGE COLUMN `education_grade_id` `education_grade_id` INT(11) NOT NULL ;
