INSERT INTO `db_patches` VALUES ('PHPOE-2117');

-- institution_site_programmes
ALTER TABLE `institution_site_programmes` 
RENAME TO  `z_2117_institution_site_programmes` ;

-- institution_site_grades
CREATE TABLE `z_2117_institution_site_grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `institution_site_programme_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
) ENGINE=InnoDB AUTO_INCREMENT=179 DEFAULT CHARSET=utf8

INSERT INTO `z_2117_institution_site_grades` (`id`, `institution_site_programme_id`)
SELECT `id`, `institution_site_programme_id` FROM `institution_site_grades`;


-- CREATE TABLE `institution_site_grades` (
--   `id` int(11) NOT NULL AUTO_INCREMENT,
--   `education_grade_id` int(11) NOT NULL,
--   `start_date` date NOT NULL,
--   `start_year` int(4) NOT NULL,
--   `end_date` date DEFAULT NULL,
--   `end_year` int(4) DEFAULT NULL,
--   `institution_site_programme_id` int(11) NOT NULL,
--   `institution_site_id` int(11) NOT NULL,
--   `modified_user_id` int(11) DEFAULT NULL,
--   `modified` datetime DEFAULT NULL,
--   `created_user_id` int(11) NOT NULL,
--   `created` datetime NOT NULL,
--   PRIMARY KEY (`id`),
--   KEY `institution_site_id` (`institution_site_id`),
--   KEY `institution_site_programme_id` (`institution_site_programme_id`)
-- ) ENGINE=InnoDB AUTO_INCREMENT=179 DEFAULT CHARSET=utf8