INSERT INTO institution_class_students
SELECT * FROM z_3941_institution_class_students;

DROP TABLE z_3941_institution_class_students;

INSERT INTO institution_subject_students
SELECT * FROM z_3941_institution_subject_students;

DROP TABLE z_3941_institution_subject_students;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3941';
