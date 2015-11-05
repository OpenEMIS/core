-- institution_sites
ALTER TABLE `institutions` 
CHANGE COLUMN `institution_locality_id` `institution_site_locality_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_type_id` `institution_site_type_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_ownership_id` `institution_site_ownership_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_status_id` `institution_site_status_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_sector_id` `institution_site_sector_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_provider_id` `institution_site_provider_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_gender_id` `institution_site_gender_id` INT(5) NOT NULL COMMENT '' ,
ADD COLUMN `institution_site_area_id` INT(11) NULL COMMENT '' AFTER `latitude`, 
RENAME TO `institution_sites` ;

-- security_group_institution_sites
ALTER TABLE `security_group_institutions` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `security_group_institution_sites` ;

-- institution_custom_field_values
ALTER TABLE `institution_custom_field_values` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' ;

-- institution_custom_table_cells
ALTER TABLE `institution_custom_table_cells` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' ;

-- institution_site_activities
ALTER TABLE `institution_activities` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , RENAME TO  `institution_site_activities` ;

-- institution_attachments
ALTER TABLE `institution_attachments` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_site_attachments` ;

CREATE TABLE `institution_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) NOT NULL,
  `description` varchar(250) NOT NULL,
  `file_name` varchar(250) NOT NULL,
  `file_content` longblob NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `institution_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1463';