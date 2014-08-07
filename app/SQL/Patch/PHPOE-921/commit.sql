--
-- 1. settle field_options and field_option_values for gender
--

SET @fieldOptionLatestId := 0;
SELECT MAX(`order`) INTO @fieldOptionLatestId from `field_options`;
INSERT INTO `field_options` (`id`, `code`, `name`, `parent`, `order`, `visible`, `created_user_id`) VALUES (NULL, 'Gender', 'Gender', 'Student', @fieldOptionLatestId, '1', '1');

SET @genderFieldOptionId = 0;
SELECT `id` INTO @genderFieldOptionId FROM `field_options` WHERE `code` LIKE 'Gender';

INSERT INTO `field_option_values` (`id`, `name`, `order`, `visible`, `editable`, `default`, `field_option_id`, `created_user_id`, `created`) VALUES (NULL, 'Male', '1', '1', '1', '0', @genderFieldOptionId, '1', '2014-07-14');
INSERT INTO `field_option_values` (`id`, `name`, `order`, `visible`, `editable`, `default`, `field_option_id`, `created_user_id`, `created`) VALUES (NULL,'Female', '2', '1', '1', '0', @genderFieldOptionId, '1', '2014-07-14');

SET @maleGenderId = 0;
SET @femaleGenderId = 0;

SELECT `id` INTO @maleGenderId FROM `field_option_values` WHERE `name` LIKE 'Male';
SELECT `id` INTO @femaleGenderId FROM `field_option_values` WHERE `name` LIKE 'Female';

--
-- 2. chnage table census_students
--

ALTER TABLE `census_students` 
RENAME TO  `census_students_bak` ;

CREATE TABLE IF NOT EXISTS `census_students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `age` int(5) NOT NULL,
  `gender_id` int(11) NOT NULL DEFAULT '0',
  `value` int(11) NOT NULL DEFAULT '0',
  `student_category_id` int(11) NOT NULL,
  `education_grade_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `source` int(1) DEFAULT '0' COMMENT '0-dataentry,1-external,2-estimate',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `education_grade_id` (`education_grade_id`),
  KEY `school_year_id` (`school_year_id`),
  KEY `student_category_id` (`student_category_id`),
  KEY `unique_yearage_census` (`institution_site_id`,`education_grade_id`,`student_category_id`),
  KEY `source` (`source`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `age` (`age`),
  KEY `gender_id` (`gender_id`),
  KEY `value` (`value`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


INSERT INTO census_students (`age`, `gender_id`, `value`, `student_category_id`, `education_grade_id`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created`)
select age, @maleGenderId, male, student_category_id, education_grade_id, institution_site_id, school_year_id, source, modified_user_id, modified, created_user_id, created 
from census_students_bu;

INSERT INTO census_students (`age`, `gender_id`, `value`, `student_category_id`, `education_grade_id`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created`)
select age, @femaleGenderId, female, student_category_id, education_grade_id, institution_site_id, school_year_id, source, modified_user_id, modified, created_user_id, created 
from census_students_bu;

-- DROP TABLE IF EXISTS `census_students_bak`;

--
-- 3. 
--

