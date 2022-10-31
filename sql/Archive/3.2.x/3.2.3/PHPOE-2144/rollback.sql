
-- institution_custom_field_values
ALTER TABLE `institution_custom_field_values` 
DROP INDEX `institution_site_id`;

ALTER TABLE `institution_custom_field_values` 
DROP INDEX `institution_custom_field_id` ;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2144';