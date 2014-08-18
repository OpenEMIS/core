--
-- 1. chnage table census_graduates
--

SET @maleGenderId = 0;
SET @femaleGenderId = 0;

SELECT `id` INTO @maleGenderId FROM `field_option_values` WHERE `name` LIKE 'Male';
SELECT `id` INTO @femaleGenderId FROM `field_option_values` WHERE `name` LIKE 'Female';

RENAME TABLE `census_graduates` TO `census_graduates_bak` ;

CREATE TABLE `census_graduates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gender_id` int(11) NOT NULL,
  `value` int(11) NOT NULL DEFAULT '0',
  `education_programme_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `source` int(1) DEFAULT '0' COMMENT '0-dataentry,1-external,2-estimate',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `school_year_id` (`school_year_id`),
  KEY `education_programme_id` (`education_programme_id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `gender_id` (`gender_id`),
  KEY `source` (`source`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

INSERT INTO `census_graduates` (`gender_id`, `value`, `education_programme_id`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
SELECT @maleGenderId, `male`, `education_programme_id`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM census_graduates_bak;

INSERT INTO `census_graduates` (`gender_id`, `value`, `education_programme_id`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
SELECT @femaleGenderId, `female`, `education_programme_id`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM census_graduates_bak;

-- DROP TABLE IF EXISTS `census_graduates_bak`;

--
-- 2. chnage table census_behaviours
--

SET @maleGenderId = 0;
SET @femaleGenderId = 0;

SELECT `id` INTO @maleGenderId FROM `field_option_values` WHERE `name` LIKE 'Male';
SELECT `id` INTO @femaleGenderId FROM `field_option_values` WHERE `name` LIKE 'Female';

RENAME TABLE `census_behaviours` TO `census_behaviours_bak` ;

CREATE TABLE IF NOT EXISTS `census_behaviours` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gender_id` int(11) NOT NULL,
  `value` int(11) NOT NULL DEFAULT '0',
  `source` int(1) NOT NULL DEFAULT '0' COMMENT '0 -> Data Entry, 1 -> External, 2 -> Estimate',
  `student_behaviour_category_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `student_behaviour_category_id` (`student_behaviour_category_id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `school_year_id` (`school_year_id`),
  KEY `gender_id` (`gender_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `census_behaviours` (`gender_id`, `value`, `source`, `student_behaviour_category_id`, `institution_site_id`, `school_year_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
SELECT @maleGenderId, `male`, `source`, `student_behaviour_category_id`, `institution_site_id`, `school_year_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM census_behaviours_bak;

INSERT INTO `census_behaviours` (`gender_id`, `value`, `source`, `student_behaviour_category_id`, `institution_site_id`, `school_year_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
SELECT @femaleGenderId, `female`, `source`, `student_behaviour_category_id`, `institution_site_id`, `school_year_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM census_behaviours_bak;

-- DROP TABLE IF EXISTS `census_behaviours_bak`;
