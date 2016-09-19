-- institution_infrastructures
UPDATE `institution_infrastructures`
INNER JOIN `z_3332_institution_infrastructures` ON `z_3332_institution_infrastructures`.`id` = `institution_infrastructures`.`id`
SET `institution_infrastructures`.`code` = `z_3332_institution_infrastructures`.`code`;

DROP TABLE `z_3332_institution_infrastructures`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3332';
