-- institutions
ALTER TABLE `institutions`
DROP COLUMN `is_academic`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3302';
