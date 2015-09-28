-- institution_site_programmes
ALTER TABLE `z_2117_institution_site_programmes` 
RENAME TO  `institution_site_programmes` ;

-- institution_site_grades
ALTER TABLE `institution_site_grades` 
ADD COLUMN `institution_site_programme_id` INT(11) NOT NULL DEFAULT 0 AFTER `end_year`;

ALTER TABLE `institution_site_grades` 
CHANGE COLUMN `institution_site_programme_id` `institution_site_programme_id` INT(11) NOT NULL;

UPDATE `institution_site_grades`
LEFT JOIN `z_2117_institution_site_grades` ON `institution_site_grades`.`id` = `z_2117_institution_site_grades`.`id`
  SET `institution_site_grades`.`institution_site_programme_id` = `z_2117_institution_site_grades`.`institution_site_programme_id`
  WHERE `institution_site_grades`.`id` = `z_2117_institution_site_grades`.`id`;
DROP TABLE `z_2117_institution_site_grades`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2117';