-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1463', NOW());

-- institutions
ALTER TABLE `institution_sites` 
DROP COLUMN `institution_site_area_id`,
CHANGE COLUMN `institution_site_locality_id` `institution_locality_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_type_id` `institution_type_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_ownership_id` `institution_ownership_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_status_id` `institution_status_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_sector_id` `institution_sector_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_provider_id` `institution_provider_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_gender_id` `institution_gender_id` INT(5) NOT NULL COMMENT '' , 
RENAME TO  `institutions` ;

-- security_group_institutions
ALTER TABLE `security_group_institution_sites` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `security_group_institutions` ;

-- institution_custom_field_values
ALTER TABLE `institution_custom_field_values` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' ;

-- institution_custom_table_cells
ALTER TABLE `institution_custom_table_cells` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' ;

-- institution_activities
ALTER TABLE `institution_site_activities` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_activities` ;

-- institution_attachments
DROP TABLE `institution_attachments`;

ALTER TABLE `institution_site_attachments` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_attachments` ;

-- institution_positions
ALTER TABLE `institution_site_positions` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , RENAME TO  `institution_positions` ;
