-- POCOR-4081
DROP TABLE `deleted_records`;

ALTER TABLE `z_4081_deleted_records`
RENAME TO `deleted_records`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-4081';


-- POCOR-4079
-- education_subjects_field_of_studies
DROP TABLE IF EXISTS `education_subjects_field_of_studies`;

-- staff_qualifications
DROP TABLE IF EXISTS `staff_qualifications`;
RENAME TABLE `z_4079_staff_qualifications` TO `staff_qualifications`;

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-4079';


-- POCOR-3941
INSERT INTO institution_class_students
SELECT * FROM z_3941_institution_class_students;

DROP TABLE z_3941_institution_class_students;

INSERT INTO institution_subject_students
SELECT * FROM z_3941_institution_subject_students;

DROP TABLE z_3941_institution_subject_students;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3941';


-- 3.10.5.1
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.10.5.1' WHERE code = 'db_version';
