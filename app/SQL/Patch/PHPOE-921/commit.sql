SET @maleGenderId := 0;
SET @femaleGenderId := 0;

SET @commonGenderOptionId := 0;
SELECT `id` INTO @commonGenderOptionId FROM `field_options` WHERE `code` LIKE 'Gender';

SELECT `id` INTO @maleGenderId FROM `field_option_values` WHERE `name` LIKE 'Male' AND `field_option_id` = @commonGenderOptionId;
SELECT `id` INTO @femaleGenderId FROM `field_option_values` WHERE `name` LIKE 'Female' AND `field_option_id` = @commonGenderOptionId;

--
-- 1. chnage table census_graduates
--

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

--
-- 3. change table census_teacher_fte, 
--

RENAME TABLE `census_teacher_fte` TO `census_teacher_fte_bak` ;

CREATE TABLE `census_teacher_fte` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gender_id` int(11) NOT NULL,
  `value` int(11) NOT NULL DEFAULT '0',
  `education_level_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `source` int(1) DEFAULT '0' COMMENT '0-dataentry,1-external,2-estimate',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `education_level_id` (`education_level_id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `school_year_id` (`school_year_id`),
  KEY `source` (`source`),
  KEY `gender_id` (`gender_id`) 
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `census_teacher_fte` (`gender_id`, `value`, `education_level_id`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
SELECT @maleGenderId, `male`, `education_level_id`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM census_teacher_fte_bak;

INSERT INTO `census_teacher_fte` (`gender_id`, `value`, `education_level_id`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
SELECT @femaleGenderId, `female`, `education_level_id`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM census_teacher_fte_bak;

-- DROP TABLE IF EXISTS `census_teacher_fte_bak`;

RENAME TABLE `census_teacher_training` TO `census_teacher_training_bak` ;

CREATE TABLE `census_teacher_training` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gender_id` int(11) NOT NULL,
  `value` int(11) NOT NULL DEFAULT '0',
  `education_level_id` int(11) NOT NULL,
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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

INSERT INTO `census_teacher_training` (`gender_id`, `value`, `education_level_id`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
SELECT @maleGenderId, `male`, `education_level_id`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM census_teacher_training_bak;

INSERT INTO `census_teacher_training` (`gender_id`, `value`, `education_level_id`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
SELECT @femaleGenderId, `female`, `education_level_id`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM census_teacher_training_bak;

-- DROP TABLE IF EXISTS `census_teacher_training_bak`;

RENAME TABLE `census_teachers` TO `census_teachers_bak` ;

CREATE TABLE `census_teachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `old_id` int(11) NOT NULL,
  `gender_id` int(11) NOT NULL,
  `value` int(11) NOT NULL DEFAULT '0',
  `institution_site_id` int(11) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `source` int(1) DEFAULT '0' COMMENT '0-dataentry,1-external,2-estimate',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `school_year_id` (`school_year_id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `source` (`source`),
  KEY `gender_id` (`gender_id`),
  KEY `old_id` (`old_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

INSERT INTO `census_teachers` (`old_id`, `gender_id`, `value`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
SELECT `id`, @maleGenderId, `male`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM census_teachers_bak;

INSERT INTO `census_teachers` (`old_id`, `gender_id`, `value`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
SELECT `id`, @femaleGenderId, `female`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM census_teachers_bak;

-- DROP TABLE IF EXISTS `census_teachers_bak`;

RENAME TABLE `census_teacher_grades` TO `census_teacher_grades_bak` ;

CREATE TABLE `census_teacher_grades` (
  `census_teacher_id` int(11) NOT NULL,
  `education_grade_id` int(11) NOT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`census_teacher_id`,`education_grade_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `census_teacher_grades` (`census_teacher_id`, `education_grade_id`, `created_user_id`, `created`) 
SELECT t.`id`, g.`education_grade_id`, g.`created_user_id`, g.`created` FROM census_teacher_grades_bak AS g, census_teachers AS t 
WHERE g.census_teacher_id = t.old_id;

-- DROP TABLE IF EXISTS `census_teacher_grades_bak`;

ALTER TABLE `census_teachers` DROP `old_id`;

--
-- 4. add field option sanitation gender
--

SET @fieldOptionId := 0;
SELECT MAX(`id`) + 1 INTO @fieldOptionId FROM `field_options`;

SET @sanitationOrder := 0;
SELECT `order` INTO @sanitationOrder FROM `field_options` WHERE `code` LIKE 'InfrastructureSanitation';

UPDATE `field_options` SET `order` = `order`+1 WHERE `order` > @sanitationOrder;

INSERT INTO `field_options` (`id`, `code`, `name`, `parent`, `order`, `visible`, `created_user_id`, `created`) VALUES (@fieldOptionId, 'SanitationGender', 'Sanitation Gender', 'Infrastructure', @sanitationOrder+1, '1', '1', NOW());

INSERT INTO `field_option_values` (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `field_option_id`, `created_user_id`, `created`) VALUES (NULL, 'Male', '1', '1', '0', '0', 'male', 'male', @fieldOptionId, '1', NOW());
INSERT INTO `field_option_values` (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `field_option_id`, `created_user_id`, `created`) VALUES (NULL, 'Female', '2', '1', '0', '0', 'female', 'female', @fieldOptionId, '1', NOW());
INSERT INTO `field_option_values` (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `field_option_id`, `created_user_id`, `created`) VALUES (NULL, 'Unisex', '3', '1', '0', '0', 'unisex', 'unisex', @fieldOptionId, '1', NOW());


--
-- 5. chnage table census_sanitations
--

SET @sanitationMaleId := 0;
SET @sanitationFemaleId := 0;
SET @sanitationUnisexId := 0;

SET @sanitationGenderOptionId := 0;
SELECT `id` INTO @sanitationGenderOptionId FROM `field_options` WHERE `code` LIKE 'SanitationGender';

SELECT `id` INTO @sanitationMaleId FROM `field_option_values` WHERE `name` LIKE 'Male' AND `field_option_id` = @sanitationGenderOptionId;
SELECT `id` INTO @sanitationFemaleId FROM `field_option_values` WHERE `name` LIKE 'Female' AND `field_option_id` = @sanitationGenderOptionId;
SELECT `id` INTO @sanitationUnisexId FROM `field_option_values` WHERE `name` LIKE 'Unisex' AND `field_option_id` = @sanitationGenderOptionId;

RENAME TABLE `census_sanitations` TO `census_sanitations_bak` ;

CREATE TABLE `census_sanitations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gender_id` int(11) NOT NULL,
  `value` int(11) NOT NULL DEFAULT '0',
  `infrastructure_sanitation_id` int(11) NOT NULL,
  `infrastructure_material_id` int(11) NOT NULL,
  `infrastructure_status_id` int(11) NOT NULL,
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
  KEY `infrastructure_status_id` (`infrastructure_status_id`),
  KEY `source` (`source`),
  KEY `gender_id` (`gender_id`) 
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

INSERT INTO `census_sanitations` (`gender_id`, `value`, `infrastructure_sanitation_id`, `infrastructure_material_id`, `infrastructure_status_id`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
SELECT @sanitationMaleId, `male`, `infrastructure_sanitation_id`, `infrastructure_material_id`, `infrastructure_status_id`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `census_sanitations_bak`;

INSERT INTO `census_sanitations` (`gender_id`, `value`, `infrastructure_sanitation_id`, `infrastructure_material_id`, `infrastructure_status_id`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
SELECT @sanitationFemaleId, `female`, `infrastructure_sanitation_id`, `infrastructure_material_id`, `infrastructure_status_id`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `census_sanitations_bak`;

INSERT INTO `census_sanitations` (`gender_id`, `value`, `infrastructure_sanitation_id`, `infrastructure_material_id`, `infrastructure_status_id`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
SELECT @sanitationUnisexId, `unisex`, `infrastructure_sanitation_id`, `infrastructure_material_id`, `infrastructure_status_id`, `institution_site_id`, `school_year_id`, `source`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `census_sanitations_bak`;

-- DROP TABLE IF EXISTS `census_sanitations_bak`;

--
-- 6. chnage table census_attendances
--

RENAME TABLE `census_attendances` TO `census_attendances_bak` ;

CREATE TABLE `census_attendances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gender_id` int(11) NOT NULL,
  `value` int(11) NOT NULL DEFAULT '0',
  `source` int(1) NOT NULL DEFAULT '0' COMMENT '0 -> Data Entry, 1 -> External, 2 -> Estimate',
  `education_grade_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `education_grade_id` (`education_grade_id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `school_year_id` (`school_year_id`),
  KEY `gender_id` (`gender_id`) 
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

INSERT INTO `census_attendances` (`gender_id`, `value`, `source`, `education_grade_id`, `institution_site_id`, `school_year_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
SELECT @maleGenderId, `absent_male`, `source`, `education_grade_id`, `institution_site_id`, `school_year_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM census_attendances_bak;

INSERT INTO `census_attendances` (`gender_id`, `value`, `source`, `education_grade_id`, `institution_site_id`, `school_year_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
SELECT @femaleGenderId, `absent_female`, `source`, `education_grade_id`, `institution_site_id`, `school_year_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM census_attendances_bak;
