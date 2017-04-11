-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2144');

-- institution_custom_field_values
ALTER TABLE `institution_custom_field_values` 
ADD INDEX `institution_custom_field_id` (`institution_custom_field_id`);

ALTER TABLE `institution_custom_field_values` 
ADD INDEX `institution_site_id` (`institution_site_id`);
