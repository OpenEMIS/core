-- security_users
UPDATE `security_users`
INNER JOIN `z_2601_security_users` ON `z_2601_security_users`.`id` = `security_users`.`id`
SET `security_users`.`address_area_id` = `z_2601_security_users`.`address_area_id`, 
`security_users`.`birthplace_area_id` = `z_2601_security_users`.`birthplace_area_id`;

DROP TABLE `z_2601_security_users`;

-- institutions
UPDATE `institutions`
INNER JOIN `z_2601_institutions` ON `z_2601_institutions`.`id` = `institutions`.`id`
SET `institutions`.`area_id` = `z_2601_institutions`.`area_id`, 
`institutions`.`area_administrative_id` = `z_2601_institutions`.`area_administrative_id`; 

DROP TABLE `z_2601_institutions`;

-- security_group_areas
DELETE FROM `security_group_areas`;

INSERT INTO `security_group_areas`
SELECT * FROM `z_2601_security_group_areas`;

DROP TABLE `z_2601_security_group_areas`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2601';