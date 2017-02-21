-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3193', NOW());

-- create backup institution_subject table
CREATE TABLE `z_3193_institution_subjects` LIKE `institution_subjects`;

INSERT INTO `z_3193_institution_subjects`
SELECT * FROM `institution_subjects` s
WHERE NOT EXISTS
(
    SELECT c.`institution_subject_id`
    FROM `institution_class_subjects` c
    WHERE s.`id` = c.`institution_subject_id`
);

-- delete orphan records from institution subjects
DELETE s
FROM `institution_subjects` s
WHERE NOT EXISTS
(
    SELECT c.`institution_subject_id`
    FROM `institution_class_subjects` c
    WHERE s.`id` = c.`institution_subject_id`
);