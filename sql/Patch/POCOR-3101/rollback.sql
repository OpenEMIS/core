UPDATE institution_students
INNER JOIN z_3101_institution_students
	ON institution_students.id = z_3101_institution_students.id
SET institution_students.student_status_id = z_3101_institution_students.student_status_id;

DROP TABLE z_3101_institution_students;

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3101';