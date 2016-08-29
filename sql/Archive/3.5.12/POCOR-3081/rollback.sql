UPDATE `institution_students`
INNER JOIN `z_3081_institution_students`
	ON `institution_students`.`id` = `z_3081_institution_students`.`id`
SET `institution_students`.`end_date` = `z_3081_institution_students`.`end_date`;

DROP TABLE `z_3081_institution_students`;

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3081';