-- remove `institution_sector_id` column from `institution_providers` table
ALTER TABLE `institution_providers`
DROP COLUMN `institution_sector_id`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3357';