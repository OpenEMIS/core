--
-- POCOR-2491
--
INSERT INTO `db_patches` VALUES ('POCOR-2491', NOW());

UPDATE `security_functions` SET `_edit` = 'Sessions.edit|Sessions.template' WHERE `security_functions`.`id` = 5040;


-- POCOR-1968
-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-1968', NOW());

-- backup the table
CREATE TABLE z1968_staff_qualifications LIKE staff_qualifications;
INSERT INTO z1968_staff_qualifications SELECT * FROM staff_qualifications;

-- REQ: move QualificationLevels and QualificationSpecialisations out from field_option_values into individual tables

--
-- Creating table 'qualification_levels'
--
CREATE TABLE `qualification_levels` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `qualification_levels`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `qualification_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


--
-- Creating table 'qualification_specialisations'
--
CREATE TABLE `qualification_specialisations` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `qualification_specialisations`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `qualification_specialisations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

  
  
UPDATE field_options SET params = '{"model":"FieldOption.QualificationSpecialisations"}' WHERE code = 'QualificationSpecialisations';
UPDATE field_options SET params = '{"model":"FieldOption.QualificationLevels"}' WHERE code = 'QualificationLevels';


--  REQ: create a new table called qualification_specialisation_subjects to link qualification specialisation to education subjects

--
-- Table structure for table `qualification_specialisation_subjects`
--

CREATE TABLE `qualification_specialisation_subjects` (
  `id` char(36) CHARACTER SET utf8 NOT NULL,
  `qualification_specialisation_id` int(11) NOT NULL,
  `education_subject_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `qualification_specialisation_subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `qualification_specialisation_subjects`
--
ALTER TABLE `qualification_specialisation_subjects`
  ADD KEY `qualification_specialisation_id` (`qualification_specialisation_id`,`education_subject_id`);



--
-- Migration for QualificationSpecialisations
--
SET @fieldOptionId := 0;
SELECT `id` INTO @fieldOptionId FROM field_options WHERE code = 'QualificationSpecialisations';

INSERT INTO qualification_specialisations (
	`id`,
	`name`, 
	`order`, 
	`visible`, 
	`editable`, 
	`default`, 
	`international_code`, 
	`national_code`, 
	`modified_user_id`, 
	`modified`, 
	`created_user_id`, 
	`created`
)
SELECT 
	`id`,
	`name`, 
	`order`, 
	`visible`, 
	`editable`, 
	`default`, 
	`international_code`, 
	`national_code`, 
	`modified_user_id`, 
	`modified`, 
	`created_user_id`, 
	`created`
FROM field_option_values WHERE `field_option_id` = @fieldOptionId;
UPDATE field_option_values SET visible = 0 WHERE field_option_id = @fieldOptionId;

--
-- Migration for QualificationLevels
--
SET @fieldOptionId := 0;
SELECT `id` INTO @fieldOptionId FROM field_options WHERE code = 'QualificationLevels';

INSERT INTO qualification_levels (
	`id`,
	`name`, 
	`order`, 
	`visible`, 
	`editable`, 
	`default`, 
	`international_code`, 
	`national_code`, 
	`modified_user_id`, 
	`modified`, 
	`created_user_id`, 
	`created`
)
SELECT 
	`id`,
	`name`, 
	`order`, 
	`visible`, 
	`editable`, 
	`default`, 
	`international_code`, 
	`national_code`, 
	`modified_user_id`, 
	`modified`, 
	`created_user_id`, 
	`created`
FROM field_option_values WHERE `field_option_id` = @fieldOptionId;
UPDATE field_option_values SET visible = 0 WHERE field_option_id = @fieldOptionId;




-- POCOR-2014

INSERT INTO `db_patches` VALUES ('POCOR-2014', NOW());

-- Showing x records out of x;
UPDATE translations SET en = 'Showing %s to %s of %s records', ar = 'يظهر %s من %s من %s عدد السجلات الكلي' WHERE en = 'Showing % to % of % records';
INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, '', 'Showing %s to %s of %s records', 'يظهر %s من %s من %s عدد السجلات الكلي', '', '', '', '', NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Showing %s to %s of %s records');

-- School dashboard titles, subtitle, axes titles;
INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'For Year %s', 'العام %s', NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'For Year %s');

-- Institution overview page: Country;
INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'Area (Administrative)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Area (Administrative)');

-- Student picture: the select file button is too small for the arabic text.
UPDATE translations SET en = 'Select File' WHERE en = 'Select file';
INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'Select File', 'اختار الملف', NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Select File');

-- Screenshot 1
INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'Select Teacher or Leave Blank', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Select Teacher or Leave Blank');
INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'No Other Student Available', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'No Other Student Available');

-- Screenshot 4
INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'Select Period', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Select Period');
INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'Select Class', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Select Class');

-- Screenshot 5
INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'Sunday', 'الأحد', NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Sunday');
INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'Monday', 'الإثنين', NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Monday');
INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'Tuesday', 'الثلاثاء', NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Tuesday');
INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'Wednesday', 'الأربعاء', NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Wednesday');
INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'Thursday', 'الخميس', NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Thursday');
INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'Friday', 'الجمعة', NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Friday');
INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'Saturday', 'السبت', NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Saturday');

-- Screenshot 6
UPDATE translations SET en = 'Select Role' WHERE en = '-- Select Role --';
INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'Select Role', 'اختر دور', NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Select Role');

-- Screenshot 7
INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'No Programme Grade Fees', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'No Programme Grade Fees');

-- Screenshot 8
INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'No Available Subjects', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'No Available Subjects');

-- Screenshot 11
INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'Add Addition', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Add Addition');
INSERT INTO `translations` (`id`, `code`,	 `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'Add Deduction', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Add Deduction');

-- Screenshot 14
INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'Next grade in the Education Structure is not available in this Institution.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Next grade in the Education Structure is not available in this Institution.');


ALTER TABLE `translations` ADD `editable` INT NOT NULL DEFAULT 1 AFTER `ru`;
UPDATE translations SET editable = 0 WHERE `en` LIKE '%\%s%' ORDER BY `id`;




-- POCOR-2564
-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2564', NOW());

-- institution_section_students
CREATE TABLE `z_2564_institution_section_students` (
  `id` char(36) NOT NULL,
  `student_status_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `student_status_id` (`student_status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `z_2564_institution_section_students`
SELECT `institution_section_students`.`id`, `institution_section_students`.`student_status_id` FROM  `institution_section_students` 
INNER JOIN `institution_sections` ON `institution_sections`.`id` = `institution_section_students`.`institution_section_id`
INNER JOIN `institution_students` ON `institution_sections`.`academic_period_id` = `institution_students`.`academic_period_id`
    AND `institution_students`.`student_id` = `institution_section_students`.`student_id`
    AND `institution_students`.`institution_id` = `institution_sections`.`institution_id`
    AND `institution_section_students`.`education_grade_id` = `institution_students`.`education_grade_id`
GROUP BY `institution_section_students`.`id`;

UPDATE `institution_section_students` 
INNER JOIN `institution_sections` ON `institution_sections`.`id` = `institution_section_students`.`institution_section_id`
INNER JOIN `institution_students` 
	ON `institution_sections`.`academic_period_id` = `institution_students`.`academic_period_id`
	AND `institution_students`.`education_grade_id` = `institution_section_students`.`education_grade_id`
    AND `institution_students`.`student_id` = `institution_section_students`.`student_id`
    AND `institution_students`.`institution_id` = `institution_sections`.`institution_id`
SET `institution_section_students`.`student_status_id` = `institution_students`.`student_status_id`;



-- POCOR-2571
-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2571', NOW());

-- Backup table
CREATE TABLE `z_2571_institution_infrastructures` LIKE  `institution_infrastructures`;
INSERT INTO `z_2571_institution_infrastructures` SELECT * FROM `institution_infrastructures` WHERE 1;

-- institution_infrastructures
ALTER TABLE `institution_infrastructures` DROP `lft`;
ALTER TABLE `institution_infrastructures` DROP `rght`;

UPDATE `institution_infrastructures` SET `parent_id` = null;
-- patch Infrastructure
DROP PROCEDURE IF EXISTS patchInfrastructure;
DELIMITER $$

CREATE PROCEDURE patchInfrastructure()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE institutionId, levelId, parentId, minId INT(11);
  DECLARE infra_levels CURSOR FOR
                SELECT `InstitutionInfrastructures`.`institution_id`, `InfrastructureLevels`.`id`, `InfrastructureLevels`.`parent_id`
                FROM `institution_infrastructures` AS `InstitutionInfrastructures`
                INNER JOIN `infrastructure_levels` AS `InfrastructureLevels`
                ON `InfrastructureLevels`.`id` = `InstitutionInfrastructures`.`infrastructure_level_id`
                AND `InfrastructureLevels`.`parent_id` <> 0
                GROUP BY `InstitutionInfrastructures`.`institution_id`, `InfrastructureLevels`.`id`, `InfrastructureLevels`.`parent_id`
                ORDER BY `InfrastructureLevels`.`parent_id`;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN infra_levels;

  read_loop: LOOP
    FETCH infra_levels INTO institutionId, levelId, parentId;
    IF done THEN
      LEAVE read_loop;
    END IF;

        SELECT MIN(`id`) INTO minId FROM `institution_infrastructures` WHERE `institution_id` =  institutionId AND `infrastructure_level_id` = parentId;
        UPDATE `institution_infrastructures` SET `parent_id` = minId WHERE `institution_id` =  institutionId AND `infrastructure_level_id` = levelId;

  END LOOP read_loop;

  CLOSE infra_levels;
END
$$

DELIMITER ;

CALL patchInfrastructure;

DROP PROCEDURE IF EXISTS patchInfrastructure;



-- DB Version
UPDATE config_items SET value = '3.4.14' WHERE code = 'db_version';
