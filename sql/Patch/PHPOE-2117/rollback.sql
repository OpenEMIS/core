-- institution_site_programmes
ALTER TABLE `z_2117_institution_site_programmes` 
RENAME TO  `institution_site_programmes` ;

-- institution_site_grades
ALTER TABLE `institution_site_grades` 
ADD COLUMN `institution_site_programme_id` INT(11) NOT NULL DEFAULT 0 AFTER `end_year`,
ADD INDEX `institution_site_programme_id` (`institution_site_programme_id` ASC);


ALTER TABLE `institution_site_grades` 
CHANGE COLUMN `institution_site_programme_id` `institution_site_programme_id` INT(11) NOT NULL;

UPDATE `institution_site_grades`
LEFT JOIN `z_2117_institution_site_grades` ON `institution_site_grades`.`id` = `z_2117_institution_site_grades`.`id`
  SET `institution_site_grades`.`institution_site_programme_id` = `z_2117_institution_site_grades`.`institution_site_programme_id`
  WHERE `institution_site_grades`.`id` = `z_2117_institution_site_grades`.`id`;
DROP TABLE `z_2117_institution_site_grades`;

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES 
(uuid(), 'InstitutionSiteProgrammes', 'created_user_id', 'Institutions -> Programmes', 'Created By', '1', '0', NOW()),
(uuid(), 'InstitutionSiteProgrammes', 'created', 'Institutions -> Programmes', 'Created On', '1', '0', NOW()),
(uuid(), 'InstitutionSiteProgrammes', 'created_user_id', 'Institutions -> Programmes', 'Created By', '1', '0', NOW()),
(uuid(), 'InstitutionSiteProgrammes', 'education_programme_id', 'Institutions -> Programmes', 'Programme', '1', '0', NOW()),
(uuid(), 'InstitutionSiteProgrammes', 'modified', 'Institutions -> Programmes', 'Modified On', '1', '0', NOW()),
(uuid(), 'InstitutionSiteProgrammes', 'modified_user_id', 'Institutions -> Programmes', 'Modified By', '1', '0', NOW()),
(uuid(), 'InstitutionSiteProgrammes', 'openemis_no', 'Institutions -> Programmes', 'OpenEMIS ID', '1', '0', NOW());

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2117';