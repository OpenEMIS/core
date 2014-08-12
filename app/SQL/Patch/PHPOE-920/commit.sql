DROP TABLE IF EXISTS `datawarehouse_indicators`;
CREATE TABLE IF NOT EXISTS `datawarehouse_indicators` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `code` varchar(60) NOT NULL,
  `description` text,
  `editable` int(1) NOT NULL DEFAULT 1,
  `enabled` int(1) NOT NULL DEFAULT '1',
  `type` varchar(30) NOT NULL,
  `datawarehouse_unit_id` int(5) NOT NULL,
  `datawarehouse_field_id` int(5) NOT NULL,
  `denominator` int(5),
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `datawarehouse_field_id` (`datawarehouse_field_id`),
  KEY `datawarehouse_unit_id` (`datawarehouse_unit_id`),
  KEY `denominator` (`denominator`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `datawarehouse_units`;
CREATE TABLE IF NOT EXISTS `datawarehouse_units` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `datawarehouse_indicator_dimensions`;
CREATE TABLE IF NOT EXISTS `datawarehouse_indicator_dimensions` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `datawarehouse_dimension_id` int(5) NOT NULL,
  `datawarehouse_indicator_id` int(5) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `datawarehouse_dimension_id` (`datawarehouse_dimension_id`),
  KEY `datawarehouse_indicator_id` (`datawarehouse_indicator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `datawarehouse_modules`;
CREATE TABLE IF NOT EXISTS `datawarehouse_modules` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `model` varchar(100) NOT NULL,
  `joins` text,
  `enabled` int(1) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `datawarehouse_fields`;
CREATE TABLE IF NOT EXISTS `datawarehouse_fields` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `type` varchar(20) NOT NULL,
  `datawarehouse_module_id` int(5) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `datawarehouse_module_id` (`datawarehouse_module_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `datawarehouse_dimensions`;
CREATE TABLE IF NOT EXISTS `datawarehouse_dimensions` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `field` varchar(50) NOT NULL,
  `model` varchar(50),
  `joins` text, 
  `datawarehouse_module_id` int(5) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `datawarehouse_module_id` (`datawarehouse_module_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `datawarehouse_indicator_subgroups`;
CREATE TABLE IF NOT EXISTS `datawarehouse_indicator_subgroups` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `subgroup` text NOT NULL,
  `value` text NOT NULL,
  `datawarehouse_indicator_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `datawarehouse_indicator_id` (`datawarehouse_indicator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



INSERT INTO `field_options` (`id`, `code`, `name`, `parent`, `order`, `visible`, `created_user_id`) VALUES ('71', 'Gender', 'Gender', 'Student', '71', '1', '1');

INSERT INTO `field_option_values` (`id`, `name`, `order`, `visible`, `editable`, `default`, `field_option_id`, `created_user_id`, `created`) VALUES (NULL, 'Male', '1', '1', '1', '0', '71', '1', '2014-07-14');
INSERT INTO `field_option_values` (`id`, `name`, `order`, `visible`, `editable`, `default`, `field_option_id`, `created_user_id`, `created`) VALUES (NULL,'Female', '2', '1', '1', '0', '71', '1', '2014-07-14');

ALTER TABLE `census_students` 
RENAME TO  `census_students_bu` ;

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

SET @maleID := 0;
SET @femaleID := 0;
SELECT `id` INTO @maleID FROM `field_option_values` WHERE `field_option_id` = 71 AND `name` = 'Male';
SELECT `id` INTO @femaleID FROM `field_option_values` WHERE `field_option_id` = 71 AND `name` = 'Female';

INSERT INTO census_students (`age`, `gender_id`, `value`, `student_category_id`, `education_grade_id`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created`)
select age, @maleID, male, student_category_id, education_grade_id, institution_site_id, school_year_id, source, modified_user_id, modified, created_user_id, created 
from census_students_bu;

INSERT INTO census_students (`age`, `gender_id`, `value`, `student_category_id`, `education_grade_id`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created`)
select age, @femaleID, female, student_category_id, education_grade_id, institution_site_id, school_year_id, source, modified_user_id, modified, created_user_id, created 
from census_students_bu;

/*DROP TABLE IF EXISTS `census_students_bu`;*/

UPDATE `navigations` SET `plugin`='Datawarehouse', `controller`='Datawarehouse', `action`='indicator', `pattern`='^indicator' WHERE `id`='43';

INSERT INTO `datawarehouse_modules` (`id`, `name`, `model`, `joins`, `enabled`, `created_user_id`, `created`) 
VALUES ('1', 'Student', 'CensusStudent', 'array(\n  \'type\' => \'INNER\',\n  \'table\' => \'institution_sites\',\n \'alias\' => \'InstitutionSite\',\n \'conditions\' => array(\'CensusStudent.institution_site_id = InstitutionSite.id\')\n)', '1', '1', '2014-07-14');

INSERT INTO `datawarehouse_units` (`id`, `name`, `created_user_id`, `created`) VALUES ('1', 'Number', '1', '2014-07-14');
INSERT INTO `datawarehouse_units` (`id`, `name`, `created_user_id`, `created`) VALUES ('2', 'Rate', '1', '2014-07-14');
INSERT INTO `datawarehouse_units` (`id`, `name`, `created_user_id`, `created`) VALUES ('3', 'Ratio', '1', '2014-07-14');
INSERT INTO `datawarehouse_units` (`id`, `name`, `created_user_id`, `created`) VALUES ('4', 'Percent', '1', '2014-07-14');

INSERT INTO `datawarehouse_fields` (`id`, `name`, `type`, `datawarehouse_module_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'value', 'COUNT', 1, NULL, NULL, 1, '2014-07-14 00:00:00'),
(2, 'value', 'MIN', 1, NULL, NULL, 1, '2014-07-14 00:00:00'),
(3, 'value', 'MAX', 1, NULL, NULL, 1, '2014-07-14 00:00:00'),
(4, 'value', 'AVG', 1, NULL, NULL, 1, '2014-07-14 00:00:00'),
(5, 'value', 'SUM', 1, NULL, NULL, 1, '2014-07-14 00:00:00');

INSERT INTO `datawarehouse_dimensions` (`id`, `name`, `field`, `model`, `datawarehouse_module_id`, `created_user_id`, `created`) VALUES ('1', 'Age', 'age', 'CensusStudent', '1', '1', '2014-07-14');
INSERT INTO `datawarehouse_dimensions` (`id`, `name`, `field`, `model`, `joins`, `datawarehouse_module_id`, `created_user_id`, `created`) VALUES ('2', 'Sex', 'name', 'Gender', 'array(\n \'type\' => \'INNER\',\n  \'table\' => \'field_option_values\',\n \'alias\' => \'Gender\',\n  \'conditions\' => array(\'Gender.id = CensusStudent.gender_id\')\n)', '1', '1', '2014-07-14');
INSERT INTO `datawarehouse_dimensions` (`id`, `name`, `field`, `model`, `joins`, `datawarehouse_module_id`, `created_user_id`, `created`) VALUES ('3', 'Locality', 'name', 'InstitutionSiteLocality',null, '1', '1', '2014-07-14');
INSERT INTO `datawarehouse_dimensions` (`id`, `name`, `field`, `model`, `datawarehouse_module_id`, `created_user_id`, `created`) VALUES ('4', 'Category', 'name', 'StudentCategory', '1', '1', '2014-07-14');
INSERT INTO `datawarehouse_dimensions` (`id`, `name`, `field`, `model`, `datawarehouse_module_id`, `created_user_id`, `created`) VALUES ('5', 'Grade', 'name', 'EducationGrade', '1', '1', '2014-07-14');

