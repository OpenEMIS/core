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




