-- replace date-closed and year-closed with backup values
UPDATE `institutions`
INNER JOIN `z_3319_institutions`
ON `institutions`.`id` = `z_3319_institutions`.`id`
SET `institutions`.`date_closed` = `z_3319_institutions`.`date_closed`, `institutions`.`year_closed` = `z_3319_institutions`.`year_closed`;

-- remove back up table
DROP TABLE`z_3319_institutions`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3319';