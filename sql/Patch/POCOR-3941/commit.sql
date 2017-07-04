-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3941', NOW());

-- patch wrong modified dates
UPDATE `institution_class_students` SET `modified` = '1970-01-01 00:00:00' WHERE `modified` < '0001-01-01 00:00:00';

-- institution_class_students
CREATE TABLE `z_3941_institution_class_students` LIKE `institution_class_students`;

INSERT INTO `z_3941_institution_class_students`
SELECT `institution_class_students`.*
FROM `institution_class_students`
LEFT JOIN `institution_class_grades`
    ON `institution_class_students`.`institution_class_id` = `institution_class_grades`.`institution_class_id`
    AND `institution_class_grades`.`education_grade_id` = `institution_class_students`.`education_grade_id`
WHERE `institution_class_grades`.`id` IS NULL;

DELETE `institution_class_students`.*
FROM `institution_class_students`
LEFT JOIN `institution_class_grades`
    ON `institution_class_students`.`institution_class_id` = `institution_class_grades`.`institution_class_id`
    AND `institution_class_grades`.`education_grade_id` = `institution_class_students`.`education_grade_id`
WHERE `institution_class_grades`.`id` IS NULL;

-- institution_subject_students
CREATE TABLE `z_3941_institution_subject_students` LIKE `institution_subject_students`;

INSERT INTO `z_3941_institution_subject_students`
SELECT `institution_subject_students`.*
FROM `institution_subject_students`
LEFT JOIN `institution_class_grades`
    ON `institution_subject_students`.`institution_class_id` = `institution_class_grades`.`institution_class_id`
    AND `institution_class_grades`.`education_grade_id` = `institution_subject_students`.`education_grade_id`
where `institution_class_grades`.`id` IS NULL;

DELETE `institution_subject_students`.*
FROM `institution_subject_students`
LEFT JOIN `institution_class_grades` ON `institution_subject_students`.`institution_class_id` = `institution_class_grades`.`institution_class_id` AND `institution_class_grades`.`education_grade_id` = `institution_subject_students`.`education_grade_id`
where `institution_class_grades`.`id` IS NULL;
