INSERT INTO `db_patches` VALUES ('PHPOE-2117');

-- institution_site_programmes
ALTER TABLE `institution_site_programmes` 
RENAME TO  `z_2117_institution_site_programmes` ;

-- institution_site_grades
CREATE TABLE `z_2117_institution_site_grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `institution_site_programme_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=179 DEFAULT CHARSET=utf8;

INSERT INTO `z_2117_institution_site_grades` (`id`, `institution_site_programme_id`)
SELECT `id`, `institution_site_programme_id` FROM `institution_site_grades`;

ALTER TABLE `institution_site_grades` 
DROP COLUMN `institution_site_programme_id`,
DROP INDEX `institution_site_programme_id` ;

-- labels
DELETE FROM `labels` WHERE `module`='InstitutionSiteProgrammes';