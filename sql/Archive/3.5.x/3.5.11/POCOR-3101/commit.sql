INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3101', NOW());

CREATE TABLE `z_3101_institution_students` LIKE `institution_students`;

-- Backup affected student record
INSERT INTO `z_3101_institution_students`
SELECT intStud2.*
FROM institution_students intStud2
INNER JOIN (
	SELECT intStud1.id, intStud1.created, intStud1.student_id, intStud1.institution_id, intStud1.academic_period_id
	FROM institution_students intStud1
		INNER JOIN (
			SELECT student_id
			FROM institution_students
			WHERE institution_students.student_status_id = 
			(
				SELECT id 
				FROM student_statuses 
				WHERE code = 'CURRENT'
			)
			GROUP BY student_id
			HAVING COUNT(student_id) > 1
		) dup
			ON intStud1.student_id = dup.student_id
) stud
	ON stud.student_id = intStud2.student_id 
    AND intStud2.created < stud.created
    AND stud.institution_id = intStud2.institution_id
    AND stud.academic_period_id = intStud2.academic_period_id
WHERE intStud2.student_status_id = 
(
	SELECT id 
	FROM student_statuses 
	WHERE code = 'CURRENT'
)
GROUP BY intStud2.id;

-- Patch enrolled records for each student that has a count of more than 1
UPDATE institution_students
INNER JOIN z_3101_institution_students 
	ON institution_students.id = z_3101_institution_students.id
SET institution_students.student_status_id = (
	SELECT id 
	FROM student_statuses 
	WHERE code = 'TRANSFERRED'
);