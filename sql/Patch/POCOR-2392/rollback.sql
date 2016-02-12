-- institution_infrastructures
ALTER TABLE `institution_infrastructures` DROP `parent_id`;
ALTER TABLE `institution_infrastructures` DROP `lft`;
ALTER TABLE `institution_infrastructures` DROP `rght`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2392';
