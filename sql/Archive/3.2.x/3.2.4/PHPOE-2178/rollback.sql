UPDATE `institution_sites`
INNER JOIN `z_2178_institution_sites` ON `z_2178_institution_sites`.`id` = `institution_sites`.`id`
SET 
	`institution_sites`.`date_opened` = `z_2178_institution_sites`.`date_opened`,
	`institution_sites`.`year_opened` = `z_2178_institution_sites`.`year_opened`
WHERE `z_2178_institution_sites`.`id` = `institution_sites`.`id`;

DROP TABLE `z_2178_institution_sites`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2178';
