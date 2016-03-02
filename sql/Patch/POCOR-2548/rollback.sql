-- institutions
UPDATE `institutions`
INNER JOIN `z_2548_institutions` ON `institutions`.`id` = `z_2548_institutions`.`id`
SET `institutions` = `z_2548_institutions`.`date_opened`;

DROP TABLE `z_2548_institutions`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2548';