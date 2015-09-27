-- institution_site_programmes
ALTER TABLE `z_2117_institution_site_programmes` 
RENAME TO  `institution_site_programmes` ;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2117';