-- code here
ALTER TABLE `identity_types`
	DROP COLUMN `validation_pattern`;


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3407';
