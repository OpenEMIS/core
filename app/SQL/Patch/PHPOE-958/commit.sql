--
-- 1. changes to reports institution bank account
--

UPDATE `reports` SET 
`name` = 'Bank Accounts',
`category` = 'Institution Finance Reports' 
WHERE `reports`.`id` =14;

--
-- 2. changes to reports institution quality
--

UPDATE `reports` SET 
`name` = 'Schools',
`category` = 'Institution Quality Reports' 
WHERE `reports`.`id` =3000;

UPDATE `reports` SET 
`name` = 'Results',
`category` = 'Institution Quality Reports' 
WHERE `reports`.`id` =3001;

UPDATE `reports` SET 
`name` = 'Rubric Not Completed',
`category` = 'Institution Quality Reports' 
WHERE `reports`.`id` =3002;

--
-- 3. changes to reports student results
--

UPDATE `reports` SET 
`name` = 'Results',
`category` = 'Student Details Reports' 
WHERE `reports`.`id` =83;

--
-- 4. chnage table census_staff
--

SET @maleGenderId = 0;
SET @femaleGenderId = 0;

SELECT `id` INTO @maleGenderId FROM `field_option_values` WHERE `name` LIKE 'Male';
SELECT `id` INTO @femaleGenderId FROM `field_option_values` WHERE `name` LIKE 'Female';

RENAME TABLE `census_staff` TO `census_staff_bak` ;

CREATE TABLE `census_staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gender_id` int(11) NOT NULL,
  `value` int(11) NOT NULL DEFAULT '0',
  `staff_category_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `source` int(1) DEFAULT '0' COMMENT '0-dataentry,1-external,2-estimate',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `school_year_id` (`school_year_id`),
  KEY `source` (`source`),
  KEY `gender_id` (`gender_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO census_staff (`gender_id`, `value`, `staff_category_id`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
select @maleGenderId, male, staff_category_id, institution_site_id, school_year_id, source, modified_user_id, modified, created_user_id, created 
from census_staff_bak;

INSERT INTO census_staff (`gender_id`, `value`, `staff_category_id`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
select @femaleGenderId, female, staff_category_id, institution_site_id, school_year_id, source, modified_user_id, modified, created_user_id, created 
from census_staff_bak;

-- DROP TABLE IF EXISTS `census_staff_bak`;

