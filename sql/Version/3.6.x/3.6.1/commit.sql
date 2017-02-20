-- POCOR-3080
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3080', NOW());

-- assessment_items_grading_types
DROP TABLE IF EXISTS `assessment_items_grading_types`;
CREATE TABLE IF NOT EXISTS `assessment_items_grading_types` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id',
  `assessment_grading_type_id` int(11) NOT NULL COMMENT 'links to assessment_grading_types.id',
  `assessment_id` int(11) NOT NULL COMMENT 'links to assessments.id',
  `assessment_period_id` int(11) NOT NULL COMMENT 'links to assessment_periods.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes for table `assessment_items_grading_types`
ALTER TABLE `assessment_items_grading_types`
  ADD PRIMARY KEY (`assessment_grading_type_id`,`assessment_id`,`education_subject_id`,`assessment_period_id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `modified_user_id` (`modified_user_id`),
  ADD KEY `created_user_id` (`created_user_id`);

-- assessment_items
-- backup assessment_items / assessment_grading_type_id cloumn
RENAME TABLE `assessment_items` TO `z_3080_assessment_items`;

CREATE TABLE IF NOT EXISTS `assessment_items` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `weight` decimal(6,2) DEFAULT '0.00',
  `assessment_id` int(11) NOT NULL,
  `education_subject_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes for table `assessment_items`
ALTER TABLE `assessment_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assessment_id` (`assessment_id`),
  ADD KEY `education_subject_id` (`education_subject_id`),
  ADD KEY `modified_user_id` (`modified_user_id`),
  ADD KEY `created_user_id` (`created_user_id`);

-- restore from backup
INSERT INTO `assessment_items` (`id`, `weight`, `assessment_id`, `education_subject_id`,
  `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `weight`, `assessment_id`, `education_subject_id`,
  `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3080_assessment_items`;

INSERT INTO `assessment_items_grading_types` (`id`, `education_subject_id`, `assessment_grading_type_id`, `assessment_id`, `assessment_period_id`,
  `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT AI.`id`, AI.`education_subject_id`, AI.`assessment_grading_type_id`, AI.`assessment_id`, AP.`id`,
  AI.`modified_user_id`, AI.`modified`, AI.`created_user_id`, AI.`created`
FROM `z_3080_assessment_items`AI
INNER JOIN `assessment_periods` AP ON AP.`assessment_id` = AI.`assessment_id`;

-- assessment_periods
ALTER TABLE `assessment_periods` CHANGE `weight` `weight` DECIMAL(6,2) NULL DEFAULT '0.00';

-- for institution_shift POCOR-2602
ALTER TABLE `institution_shifts` CHANGE `shift_option_id` `shift_option_id` INT(11) NOT NULL COMMENT 'links to shift_options.id';


-- POCOR-2760
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-2760', NOW());

UPDATE `security_functions` SET _delete = NULL WHERE id = 5003;
-- SELECT * FROM `security_functions` WHERE id = 5003;

-- BACKING UP
CREATE TABLE z_2760_security_role_functions LIKE security_role_functions;
INSERT INTO z_2760_security_role_functions SELECT * FROM security_role_functions WHERE security_function_id = 5003;

-- DELETING ASSOCIATED RECORDS
UPDATE security_role_functions SET _delete = 0 WHERE security_function_id = 5003;


-- POCOR-3049
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3049', NOW());

-- security_functions
UPDATE `security_functions` SET `order` = `order`+1 WHERE `order` >= 5028 AND `order` <= 5042;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `order`, `visible`, `created_user_id`, `created`) VALUES (5043, 'Rules', 'Surveys', 'Administration', 'Survey', 5000, 'Rules.index', 'Rules.edit', 5028, 1, 1, NOW());


-- POCOR-2602
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-2602', NOW());

-- add new field option for ShiftOptions
INSERT INTO `field_options` (`id`, `plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES (NULL, 'Institution', 'ShiftOptions', 'Shift Options', 'Institution', NULL, '61', '1', NULL, NULL, '1', '2016-06-23 00:00:00');

--
-- new shift_options table
--
CREATE TABLE IF NOT EXISTS `shift_options` (
  `id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
  `international_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `national_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `shift_options`
INSERT INTO `shift_options` (`id`, `name`, `start_time`, `end_time`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'First Shift', '07:00:00', '11:00:00', 1, 1, 1, 0, NULL, NULL, NULL, NULL, 1, '2016-06-21 00:00:00'),
(2, 'Second Shift', '11:00:00', '15:00:00', 2, 1, 1, 0, NULL, NULL, NULL, NULL, 1, '2016-06-21 00:00:00'),
(3, 'Third Shift', '15:00:00', '19:00:00', 3, 1, 1, 0, NULL, NULL, NULL, NULL, 1, '2016-06-21 00:00:00'),
(4, 'Fourth Shift', '19:00:00', '23:00:00', 4, 1, 1, 0, NULL, NULL, NULL, NULL, 1, '2016-06-21 00:00:00');

-- Indexes for table `shift_options`
ALTER TABLE `shift_options`
  ADD PRIMARY KEY (`id`);

-- AUTO_INCREMENT for table `shift_options`
ALTER TABLE `shift_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;

--
-- institution_shifts
--

CREATE TABLE `z_2602_institution_shifts` LIKE `institution_shifts`;
INSERT INTO `z_2602_institution_shifts` SELECT * FROM `institution_shifts`;

ALTER TABLE `institution_shifts` ADD `shift_option_id` INT NOT NULL AFTER `location_institution_id`;
ALTER TABLE `institution_shifts` DROP `name`;
ALTER TABLE `institution_shifts` CHANGE `location_institution_id` `location_institution_id` INT(11) NOT NULL;

--
-- patch Institution Shift
--
DROP PROCEDURE IF EXISTS patchInstitutionShift;
DELIMITER $$

CREATE PROCEDURE patchInstitutionShift()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE institutionID, academicPeriodID, shiftCounter, recCounter INT(11);
  DECLARE institution_shift_counter CURSOR FOR
    SELECT `institution_id`, `academic_period_id`, COUNT(`id`) AS counter
    FROM `institution_shifts`
    GROUP BY `institution_id`, `academic_period_id`;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN institution_shift_counter;

  read_loop: LOOP
    FETCH institution_shift_counter INTO institutionID, academicPeriodID, shiftCounter;
    IF done THEN
      LEAVE read_loop;
    END IF;

    SET recCounter = 0;
    WHILE recCounter < shiftCounter DO
      UPDATE `institution_shifts`
      SET `shift_option_id` = recCounter+1
      WHERE `id` IN (
        SELECT `id` FROM (
            SELECT `id`
            FROM `institution_shifts`
            WHERE `institution_id` = institutionID
            AND `academic_period_id` = academicPeriodID
            ORDER BY `start_time` ASC
            LIMIT recCounter, 1) tempTable);
      SET recCounter = recCounter + 1;
    END WHILE;

  END LOOP read_loop;

  CLOSE institution_shift_counter;
END

$$

DELIMITER ;

CALL patchInstitutionShift;

DROP PROCEDURE IF EXISTS patchInstitutionShift;

--
-- Label
--
UPDATE `labels`
SET `field` = 'shift_option_id', `field_name` = 'Shift'
WHERE `module` = 'InstitutionShifts'
AND `field` = 'name'
AND `module_name` = 'Institutions -> Shifts'
AND `field_name` = 'Shift Name';

UPDATE `labels`
SET `field_name` = 'Owner',
`field` = 'institution_id'
WHERE `module` = 'InstitutionShifts'
AND `field` = 'location'
AND `module_name` = 'Institutions -> Shifts';

UPDATE `labels`
SET `field_name` = 'Occupier'
WHERE `module` = 'InstitutionShifts'
AND `field` = 'location_institution_id'
AND `module_name` = 'Institutions -> Shifts';

UPDATE `labels`
SET `module_name` = 'Institutions -> Shifts'
WHERE `module` = 'InstitutionShifts';

UPDATE `labels`
SET  `field` = 'academic_period_id'
WHERE `module` = 'InstitutionShifts'
AND `field` = 'Academic_period_id'
AND `module_name` = 'Institutions -> Shifts';
--
-- institutions table
--

CREATE TABLE `z_2602_institutions` LIKE `institutions`;
INSERT INTO `z_2602_institutions` SELECT * FROM `institutions`;

ALTER TABLE `institutions` ADD `shift_type` INT NOT NULL COMMENT '1=Single Shift Owner, 2=Single Shift Occupier, 3=Multiple Shift Owner, 4=Multiple Shift Occupier' AFTER `latitude`;

-- patch patchInstitutionShiftType
DROP PROCEDURE IF EXISTS patchInstitutionShiftTypeOwner;
DROP PROCEDURE IF EXISTS patchInstitutionShiftTypeOccupier;

DELIMITER $$

CREATE PROCEDURE patchInstitutionShiftTypeOwner(IN academicPeriodID INT(11))
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE ownerID, shiftCounter, shiftType INT(11);

  -- update owner
  DECLARE shift_owner_counter CURSOR FOR
    SELECT `institution_id`, COUNT(`id`) AS counter
    FROM `institution_shifts`
    WHERE `academic_period_id` = academicPeriodID
    GROUP BY `institution_id`;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN shift_owner_counter;

  read_loop: LOOP
    FETCH shift_owner_counter INTO ownerID, shiftCounter;
    IF done THEN
      LEAVE read_loop;
    END IF;

    IF shiftCounter > 1 THEN
      SET shiftType = 3;
    ELSE
      SET shiftType = 1;
    END IF;

    UPDATE `institutions`
    SET `shift_type` = shiftType
    WHERE `id` = ownerID;

  END LOOP read_loop;

  CLOSE shift_owner_counter;

END

$$

CREATE PROCEDURE patchInstitutionShiftTypeOccupier(IN academicPeriodID INT(11))
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE occupierID, shiftCounter, shiftType INT(11);

  -- update occupier
  DECLARE shift_occupier_counter CURSOR FOR
    SELECT `location_institution_id`, COUNT(`id`) AS counter
    FROM `institution_shifts`
    WHERE `academic_period_id` = academicPeriodID
    AND `location_institution_id` <> `institution_id`
    GROUP BY `location_institution_id`;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN shift_occupier_counter;

  read_loop: LOOP
    FETCH shift_occupier_counter INTO occupierID, shiftCounter;
    IF done THEN
      LEAVE read_loop;
    END IF;

    IF shiftCounter > 1 THEN
      SET shiftType = 4;
    ELSE
      SET shiftType = 2;
    END IF;

    UPDATE `institutions`
    SET `shift_type` = shiftType
    WHERE `id` = occupierID;

  END LOOP read_loop;

  CLOSE shift_occupier_counter;
END

$$

DELIMITER ;

SET @academicPeriodID := 0;

SELECT `id` INTO @academicPeriodID FROM `academic_periods`
WHERE `current` = 1;

CALL patchInstitutionShiftTypeOwner(@academicPeriodID);
CALL patchInstitutionShiftTypeOccupier(@academicPeriodID);

DROP PROCEDURE IF EXISTS patchInstitutionShiftTypeOwner;
DROP PROCEDURE IF EXISTS patchInstitutionShiftTypeOccupier;

-- Adding description to foreign keys
ALTER TABLE `institution_staff_absences` CHANGE `absence_type_id` `absence_type_id` int(11) NOT NULL COMMENT 'links to absence_types.id';
ALTER TABLE `institution_student_absences` CHANGE `absence_type_id` `absence_type_id` int(11) NOT NULL COMMENT 'links to absence_types.id';
ALTER TABLE `assessments` CHANGE `academic_period_id` `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id';
ALTER TABLE `institution_class_students` CHANGE `academic_period_id` `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id';
ALTER TABLE `institution_classes` CHANGE `academic_period_id` `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id';
ALTER TABLE `institution_fees` CHANGE `academic_period_id` `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id';
ALTER TABLE `institution_quality_rubrics` CHANGE `academic_period_id` `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id';
ALTER TABLE `institution_quality_visits` CHANGE `academic_period_id` `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id';
ALTER TABLE `institution_repeater_surveys` CHANGE `academic_period_id` `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id';
ALTER TABLE `institution_shifts` CHANGE `academic_period_id` `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id';
ALTER TABLE `institution_student_admission` CHANGE `academic_period_id` `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id';
ALTER TABLE `institution_student_dropout` CHANGE `academic_period_id` `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id';
ALTER TABLE `institution_student_surveys` CHANGE `academic_period_id` `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id';
ALTER TABLE `institution_students` CHANGE `academic_period_id` `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id';
ALTER TABLE `institution_subject_students` CHANGE `academic_period_id` `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id';
ALTER TABLE `institution_subjects` CHANGE `academic_period_id` `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id';
ALTER TABLE `institution_surveys` CHANGE `academic_period_id` `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id';
ALTER TABLE `rubric_status_periods` CHANGE `academic_period_id` `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id';
ALTER TABLE `staff_extracurriculars` CHANGE `academic_period_id` `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id';
ALTER TABLE `student_extracurriculars` CHANGE `academic_period_id` `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id';
ALTER TABLE `survey_status_periods` CHANGE `academic_period_id` `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id';
ALTER TABLE `academic_periods` CHANGE `academic_period_level_id` `academic_period_level_id` int(11) NOT NULL COMMENT 'links to academic_period_levels.id';
ALTER TABLE `security_users` CHANGE `address_area_id` `address_area_id` int(11) NULL COMMENT 'links to area_administratives.id';
ALTER TABLE `alert_roles` CHANGE `alert_id` `alert_id` int(11) NOT NULL COMMENT 'links to alerts.id';
ALTER TABLE `area_administrative_levels` CHANGE `area_administrative_id` `area_administrative_id` int(11) NOT NULL COMMENT 'links to area_administratives.id';
ALTER TABLE `area_administratives` CHANGE `area_administrative_level_id` `area_administrative_level_id` int(11) NOT NULL COMMENT 'links to area_administrative_levels.id';
ALTER TABLE `institutions` CHANGE `area_id` `area_id` int(11) NOT NULL COMMENT 'links to areas.id';
ALTER TABLE `security_group_areas` CHANGE `area_id` `area_id` int(11) NOT NULL COMMENT 'links to areas.id';
ALTER TABLE `areas` CHANGE `area_level_id` `area_level_id` int(11) NOT NULL COMMENT 'links to area_levels.id';
ALTER TABLE `assessment_grading_options` CHANGE `assessment_grading_type_id` `assessment_grading_type_id` int(11) NOT NULL COMMENT 'links to assessment_grading_types.id';
ALTER TABLE `assessment_items` CHANGE `assessment_grading_type_id` `assessment_grading_type_id` int(11) NOT NULL COMMENT 'links to assessment_grading_types.id';
ALTER TABLE `assessment_items` CHANGE `assessment_id` `assessment_id` int(11) NOT NULL COMMENT 'links to assessments.id';
ALTER TABLE `assessment_periods` CHANGE `assessment_id` `assessment_id` int(11) NOT NULL COMMENT 'links to assessments.id';
ALTER TABLE `institution_bank_accounts` CHANGE `bank_branch_id` `bank_branch_id` int(11) NOT NULL COMMENT 'links to bank_branches.id';
ALTER TABLE `user_bank_accounts` CHANGE `bank_branch_id` `bank_branch_id` int(11) NOT NULL COMMENT 'links to bank_branches.id';
ALTER TABLE `bank_branches` CHANGE `bank_id` `bank_id` int(11) NOT NULL COMMENT 'links to banks.id';
ALTER TABLE `security_users` CHANGE `birthplace_area_id` `birthplace_area_id` int(11) NULL COMMENT 'links to area_administratives.id';
ALTER TABLE `contact_types` CHANGE `contact_option_id` `contact_option_id` int(11) NOT NULL COMMENT 'links to contact_options.id';
ALTER TABLE `user_contacts` CHANGE `contact_type_id` `contact_type_id` int(11) NOT NULL COMMENT 'links to contact_types.id';
ALTER TABLE `staff_training_needs` CHANGE `course_id` `course_id` int(11) NOT NULL COMMENT 'links to training_courses.id';
ALTER TABLE `custom_field_options` CHANGE `custom_field_id` `custom_field_id` int(11) NOT NULL COMMENT 'links to custom_fields.id';
ALTER TABLE `custom_field_values` CHANGE `custom_field_id` `custom_field_id` int(11) NOT NULL COMMENT 'links to custom_fields.id';
ALTER TABLE `custom_forms_fields` CHANGE `custom_field_id` `custom_field_id` int(11) NOT NULL COMMENT 'links to custom_fields.id';
ALTER TABLE `custom_table_cells` CHANGE `custom_field_id` `custom_field_id` int(11) NOT NULL COMMENT 'links to custom_fields.id';
ALTER TABLE `custom_table_columns` CHANGE `custom_field_id` `custom_field_id` int(11) NOT NULL COMMENT 'links to custom_fields.id';
ALTER TABLE `custom_table_rows` CHANGE `custom_field_id` `custom_field_id` int(11) NOT NULL COMMENT 'links to custom_fields.id';
ALTER TABLE `custom_forms_fields` CHANGE `custom_form_id` `custom_form_id` int(11) NOT NULL COMMENT 'links to custom_forms.id';
ALTER TABLE `custom_forms_filters` CHANGE `custom_form_id` `custom_form_id` int(11) NOT NULL COMMENT 'links to custom_forms.id';
ALTER TABLE `custom_records` CHANGE `custom_form_id` `custom_form_id` int(11) NOT NULL COMMENT 'links to custom_forms.id';
ALTER TABLE `custom_forms` CHANGE `custom_module_id` `custom_module_id` int(11) NOT NULL COMMENT 'links to custom_modules.id';
ALTER TABLE `infrastructure_custom_forms` CHANGE `custom_module_id` `custom_module_id` int(11) NOT NULL COMMENT 'links to custom_modules.id';
ALTER TABLE `institution_custom_forms` CHANGE `custom_module_id` `custom_module_id` int(11) NOT NULL COMMENT 'links to custom_modules.id';
ALTER TABLE `staff_custom_forms` CHANGE `custom_module_id` `custom_module_id` int(11) NOT NULL COMMENT 'links to custom_modules.id';
ALTER TABLE `student_custom_forms` CHANGE `custom_module_id` `custom_module_id` int(11) NOT NULL COMMENT 'links to custom_modules.id';
ALTER TABLE `survey_forms` CHANGE `custom_module_id` `custom_module_id` int(11) NOT NULL COMMENT 'links to custom_modules.id';
ALTER TABLE `custom_field_values` CHANGE `custom_record_id` `custom_record_id` int(11) NOT NULL COMMENT 'links to custom_records.id';
ALTER TABLE `custom_table_cells` CHANGE `custom_record_id` `custom_record_id` int(11) NOT NULL COMMENT 'links to custom_records.id';
ALTER TABLE `custom_table_cells` CHANGE `custom_table_column_id` `custom_table_column_id` int(11) NOT NULL COMMENT 'links to custom_table_columns.id';
ALTER TABLE `custom_table_cells` CHANGE `custom_table_row_id` `custom_table_row_id` int(11) NOT NULL COMMENT 'links to custom_table_rows.id';
ALTER TABLE `education_programmes` CHANGE `education_certification_id` `education_certification_id` int(11) NOT NULL COMMENT 'links to education_certifications.id';
ALTER TABLE `education_programmes` CHANGE `education_cycle_id` `education_cycle_id` int(11) NOT NULL COMMENT 'links to education_cycles.id';
ALTER TABLE `education_programmes` CHANGE `education_field_of_study_id` `education_field_of_study_id` int(11) NOT NULL COMMENT 'links to education_field_of_studies.id';
ALTER TABLE `assessments` CHANGE `education_grade_id` `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id';
ALTER TABLE `education_grades_subjects` CHANGE `education_grade_id` `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id';
ALTER TABLE `institution_class_grades` CHANGE `education_grade_id` `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id';
ALTER TABLE `institution_class_students` CHANGE `education_grade_id` `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id';
ALTER TABLE `institution_fees` CHANGE `education_grade_id` `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id';
ALTER TABLE `institution_grades` CHANGE `education_grade_id` `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id';
ALTER TABLE `institution_quality_rubrics` CHANGE `education_grade_id` `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id';
ALTER TABLE `institution_student_admission` CHANGE `education_grade_id` `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id';
ALTER TABLE `institution_student_dropout` CHANGE `education_grade_id` `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id';
ALTER TABLE `institution_students` CHANGE `education_grade_id` `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id';
ALTER TABLE `education_cycles` CHANGE `education_level_id` `education_level_id` int(11) NOT NULL COMMENT 'links to education_levels.id';
ALTER TABLE `education_levels` CHANGE `education_level_isced_id` `education_level_isced_id` int(11) NOT NULL COMMENT 'links to education_level_isced.id';
ALTER TABLE `education_grades` CHANGE `education_programme_id` `education_programme_id` int(11) NOT NULL COMMENT 'links to education_programmes.id';
ALTER TABLE `education_programmes_next_programmes` CHANGE `education_programme_id` `education_programme_id` int(11) NOT NULL COMMENT 'links to education_programmes.id';
ALTER TABLE `rubric_status_programmes` CHANGE `education_programme_id` `education_programme_id` int(11) NOT NULL COMMENT 'links to education_programmes.id';
ALTER TABLE `education_field_of_studies` CHANGE `education_programme_orientation_id` `education_programme_orientation_id` int(11) NOT NULL COMMENT 'links to education_programme_orientations.id';
ALTER TABLE `assessment_items` CHANGE `education_subject_id` `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id';
ALTER TABLE `education_grades_subjects` CHANGE `education_subject_id` `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id';
ALTER TABLE `institution_subject_students` CHANGE `education_subject_id` `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id';
ALTER TABLE `institution_subjects` CHANGE `education_subject_id` `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id';
ALTER TABLE `qualification_specialisation_subjects` CHANGE `education_subject_id` `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id';
ALTER TABLE `education_levels` CHANGE `education_system_id` `education_system_id` int(11) NOT NULL COMMENT 'links to education_systems.id';
ALTER TABLE `staff_employments` CHANGE `employment_type_id` `employment_type_id` int(11) NOT NULL COMMENT 'links to employment_types.id';
ALTER TABLE `staff_extracurriculars` CHANGE `extracurricular_type_id` `extracurricular_type_id` int(11) NOT NULL COMMENT 'links to extracurricular_types.id';
ALTER TABLE `student_extracurriculars` CHANGE `extracurricular_type_id` `extracurricular_type_id` int(11) NOT NULL COMMENT 'links to extracurricular_types.id';
ALTER TABLE `institution_fee_types` CHANGE `fee_type_id` `fee_type_id` int(11) NOT NULL COMMENT 'links to fee_types.id';
ALTER TABLE `security_users` CHANGE `gender_id` `gender_id` int(1) NOT NULL COMMENT 'links to genders.id';
ALTER TABLE `student_guardians` CHANGE `guardian_id` `guardian_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `student_guardians` CHANGE `guardian_relation_id` `guardian_relation_id` int(11) NOT NULL COMMENT 'links to guardian_relations.id';
ALTER TABLE `user_health_allergies` CHANGE `health_allergy_type_id` `health_allergy_type_id` int(11) NOT NULL COMMENT 'links to health_allergy_types.id';
ALTER TABLE `user_health_families` CHANGE `health_condition_id` `health_condition_id` int(6) NOT NULL COMMENT 'links to health_conditions.id';
ALTER TABLE `user_health_histories` CHANGE `health_condition_id` `health_condition_id` int(11) NOT NULL COMMENT 'links to health_conditions.id';
ALTER TABLE `user_health_consultations` CHANGE `health_consultation_type_id` `health_consultation_type_id` int(11) NOT NULL COMMENT 'links to health_consultation_types.id';
ALTER TABLE `user_health_immunizations` CHANGE `health_immunization_type_id` `health_immunization_type_id` int(11) NOT NULL COMMENT 'links to health_immunization_types.id';
ALTER TABLE `user_health_families` CHANGE `health_relationship_id` `health_relationship_id` int(4) NOT NULL COMMENT 'links to health_relationships.id';
ALTER TABLE `user_health_tests` CHANGE `health_test_type_id` `health_test_type_id` int(11) NOT NULL COMMENT 'links to health_test_types.id';
ALTER TABLE `nationalities` CHANGE `identity_type_id` `identity_type_id` int(11) NULL COMMENT 'links to identity_types.id';
ALTER TABLE `user_identities` CHANGE `identity_type_id` `identity_type_id` int(11) NOT NULL COMMENT 'links to identity_types.id';
ALTER TABLE `institution_infrastructures` CHANGE `infrastructure_condition_id` `infrastructure_condition_id` int(11) NOT NULL COMMENT 'links to infrastructure_conditions.id';
ALTER TABLE `infrastructure_custom_field_options` CHANGE `infrastructure_custom_field_id` `infrastructure_custom_field_id` int(11) NOT NULL COMMENT 'links to infrastructure_custom_fields.id';
ALTER TABLE `infrastructure_custom_field_values` CHANGE `infrastructure_custom_field_id` `infrastructure_custom_field_id` int(11) NOT NULL COMMENT 'links to infrastructure_custom_fields.id';
ALTER TABLE `infrastructure_custom_forms_fields` CHANGE `infrastructure_custom_field_id` `infrastructure_custom_field_id` int(11) NOT NULL COMMENT 'links to infrastructure_custom_fields.id';
ALTER TABLE `infrastructure_custom_forms_fields` CHANGE `infrastructure_custom_form_id` `infrastructure_custom_form_id` int(11) NOT NULL COMMENT 'links to infrastructure_custom_forms.id';
ALTER TABLE `infrastructure_custom_forms_filters` CHANGE `infrastructure_custom_form_id` `infrastructure_custom_form_id` int(11) NOT NULL COMMENT 'links to infrastructure_custom_forms.id';
ALTER TABLE `infrastructure_types` CHANGE `infrastructure_level_id` `infrastructure_level_id` int(11) NOT NULL COMMENT 'links to infrastructure_levels.id';
ALTER TABLE `institution_infrastructures` CHANGE `infrastructure_level_id` `infrastructure_level_id` int(11) NOT NULL COMMENT 'links to infrastructure_levels.id';
ALTER TABLE `institution_infrastructures` CHANGE `infrastructure_ownership_id` `infrastructure_ownership_id` int(11) NOT NULL COMMENT 'links to infrastructure_ownerships.id';
ALTER TABLE `institution_infrastructures` CHANGE `infrastructure_type_id` `infrastructure_type_id` int(11) NOT NULL COMMENT 'links to infrastructure_types.id';
ALTER TABLE `institution_class_grades` CHANGE `institution_class_id` `institution_class_id` int(11) NOT NULL COMMENT 'links to institution_classes.id';
ALTER TABLE `institution_class_students` CHANGE `institution_class_id` `institution_class_id` int(11) NOT NULL COMMENT 'links to institution_classes.id';
ALTER TABLE `institution_class_subjects` CHANGE `institution_class_id` `institution_class_id` int(11) NOT NULL COMMENT 'links to institution_classes.id';
ALTER TABLE `institution_quality_rubrics` CHANGE `institution_class_id` `institution_class_id` int(11) NOT NULL COMMENT 'links to institution_classes.id';
ALTER TABLE `institution_student_admission` CHANGE `institution_class_id` `institution_class_id` int(11) NULL COMMENT 'links to institution_classes.id';
ALTER TABLE `institution_subject_students` CHANGE `institution_class_id` `institution_class_id` int(11) NOT NULL COMMENT 'links to institution_classes.id';
ALTER TABLE `institution_custom_field_options` CHANGE `institution_custom_field_id` `institution_custom_field_id` int(11) NOT NULL COMMENT 'links to institution_custom_fields.id';
ALTER TABLE `institution_custom_field_values` CHANGE `institution_custom_field_id` `institution_custom_field_id` int(11) NOT NULL COMMENT 'links to institution_custom_fields.id';
ALTER TABLE `institution_custom_forms_fields` CHANGE `institution_custom_field_id` `institution_custom_field_id` int(11) NOT NULL COMMENT 'links to institution_custom_fields.id';
ALTER TABLE `institution_custom_table_cells` CHANGE `institution_custom_field_id` `institution_custom_field_id` int(11) NOT NULL COMMENT 'links to institution_custom_fields.id';
ALTER TABLE `institution_custom_table_columns` CHANGE `institution_custom_field_id` `institution_custom_field_id` int(11) NOT NULL COMMENT 'links to institution_custom_fields.id';
ALTER TABLE `institution_custom_table_rows` CHANGE `institution_custom_field_id` `institution_custom_field_id` int(11) NOT NULL COMMENT 'links to institution_custom_fields.id';
ALTER TABLE `institution_custom_forms_fields` CHANGE `institution_custom_form_id` `institution_custom_form_id` int(11) NOT NULL COMMENT 'links to institution_custom_forms.id';
ALTER TABLE `institution_custom_forms_filters` CHANGE `institution_custom_form_id` `institution_custom_form_id` int(11) NOT NULL COMMENT 'links to institution_custom_forms.id';
ALTER TABLE `institution_custom_table_cells` CHANGE `institution_custom_table_column_id` `institution_custom_table_column_id` int(11) NOT NULL COMMENT 'links to institution_custom_table_columns.id';
ALTER TABLE `institution_custom_table_cells` CHANGE `institution_custom_table_row_id` `institution_custom_table_row_id` int(11) NOT NULL COMMENT 'links to institution_custom_table_rows.id';
ALTER TABLE `institution_fee_types` CHANGE `institution_fee_id` `institution_fee_id` int(11) NOT NULL COMMENT 'links to institution_fees.id';
ALTER TABLE `student_fees` CHANGE `institution_fee_id` `institution_fee_id` int(11) NOT NULL COMMENT 'links to institution_fees.id';
ALTER TABLE `institutions` CHANGE `institution_gender_id` `institution_gender_id` int(5) NOT NULL COMMENT 'links to institution_genders.id';
ALTER TABLE `institution_activities` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `institution_attachments` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `institution_bank_accounts` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `institution_class_students` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `institution_classes` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `institution_custom_field_values` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `institution_custom_table_cells` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `institution_fees` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `institution_grades` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `institution_infrastructures` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `institution_positions` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `institution_quality_rubrics` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `institution_quality_visits` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `institution_repeater_surveys` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `institution_shifts` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `institution_staff` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `institution_staff_absences` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `institution_staff_assignments` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `institution_staff_position_profiles` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `institution_student_absences` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `institution_student_admission` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `institution_student_dropout` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `institution_student_surveys` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `institution_students` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `institution_subject_students` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `institution_subjects` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `institution_surveys` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `security_group_institutions` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `staff_behaviours` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `student_behaviours` CHANGE `institution_id` `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `infrastructure_custom_field_values` CHANGE `institution_infrastructure_id` `institution_infrastructure_id` int(11) NOT NULL COMMENT 'links to institution_infrastructures.id';
ALTER TABLE `institutions` CHANGE `institution_locality_id` `institution_locality_id` int(11) NOT NULL COMMENT 'links to institution_localities.id';
ALTER TABLE `institutions` CHANGE `institution_network_connectivity_id` `institution_network_connectivity_id` int(11) NOT NULL COMMENT 'links to institution_network_connectivities.id';
ALTER TABLE `institutions` CHANGE `institution_ownership_id` `institution_ownership_id` int(11) NOT NULL COMMENT 'links to institution_ownerships.id';
ALTER TABLE `institution_staff` CHANGE `institution_position_id` `institution_position_id` int(11) NOT NULL COMMENT 'links to institution_positions.id';
ALTER TABLE `institution_staff_assignments` CHANGE `institution_position_id` `institution_position_id` int(11) NOT NULL COMMENT 'links to institution_positions.id';
ALTER TABLE `institution_staff_position_profiles` CHANGE `institution_position_id` `institution_position_id` int(11) NOT NULL COMMENT 'links to institution_positions.id';
ALTER TABLE `institutions` CHANGE `institution_provider_id` `institution_provider_id` int(11) NOT NULL COMMENT 'links to institution_providers.id';
ALTER TABLE `institution_quality_rubric_answers` CHANGE `institution_quality_rubric_id` `institution_quality_rubric_id` int(11) NOT NULL COMMENT 'links to institution_quality_rubrics.id';
ALTER TABLE `institution_repeater_survey_answers` CHANGE `institution_repeater_survey_id` `institution_repeater_survey_id` int(11) NOT NULL COMMENT 'links to institution_repeater_surveys.id';
ALTER TABLE `institution_repeater_survey_table_cells` CHANGE `institution_repeater_survey_id` `institution_repeater_survey_id` int(11) NOT NULL COMMENT 'links to institution_repeater_surveys.id';
ALTER TABLE `institutions` CHANGE `institution_sector_id` `institution_sector_id` int(11) NOT NULL COMMENT 'links to institution_sectors.id';
ALTER TABLE `institution_classes` CHANGE `institution_shift_id` `institution_shift_id` int(11) NOT NULL COMMENT 'links to institution_shifts.id';
ALTER TABLE `institution_staff_position_profiles` CHANGE `institution_staff_id` `institution_staff_id` int(11) NOT NULL COMMENT 'links to institution_staff.id';
ALTER TABLE `institutions` CHANGE `institution_status_id` `institution_status_id` int(11) NOT NULL COMMENT 'links to institution_statuses.id';
ALTER TABLE `institution_student_survey_answers` CHANGE `institution_student_survey_id` `institution_student_survey_id` int(11) NOT NULL COMMENT 'links to institution_student_surveys.id';
ALTER TABLE `institution_student_survey_table_cells` CHANGE `institution_student_survey_id` `institution_student_survey_id` int(11) NOT NULL COMMENT 'links to institution_student_surveys.id';
ALTER TABLE `institution_class_subjects` CHANGE `institution_subject_id` `institution_subject_id` int(11) NOT NULL COMMENT 'links to institution_subjects.id';
ALTER TABLE `institution_quality_rubrics` CHANGE `institution_subject_id` `institution_subject_id` int(11) NOT NULL COMMENT 'links to institution_subjects.id';
ALTER TABLE `institution_quality_visits` CHANGE `institution_subject_id` `institution_subject_id` int(11) NOT NULL COMMENT 'links to institution_subjects.id';
ALTER TABLE `institution_subject_staff` CHANGE `institution_subject_id` `institution_subject_id` int(11) NOT NULL COMMENT 'links to institution_subjects.id';
ALTER TABLE `institution_subject_students` CHANGE `institution_subject_id` `institution_subject_id` int(11) NOT NULL COMMENT 'links to institution_subjects.id';
ALTER TABLE `institution_survey_answers` CHANGE `institution_survey_id` `institution_survey_id` int(11) NOT NULL COMMENT 'links to institution_surveys.id';
ALTER TABLE `institution_survey_table_cells` CHANGE `institution_survey_id` `institution_survey_id` int(11) NOT NULL COMMENT 'links to institution_surveys.id';
ALTER TABLE `institutions` CHANGE `institution_type_id` `institution_type_id` int(11) NOT NULL COMMENT 'links to institution_types.id';
ALTER TABLE `user_languages` CHANGE `language_id` `language_id` int(11) NOT NULL COMMENT 'links to languages.id';
ALTER TABLE `staff_licenses` CHANGE `license_type_id` `license_type_id` int(11) NOT NULL COMMENT 'links to license_types.id';
ALTER TABLE `institution_shifts` CHANGE `location_institution_id` `location_institution_id` int(11) NOT NULL COMMENT 'links to institutions.id';
ALTER TABLE `user_nationalities` CHANGE `nationality_id` `nationality_id` int(11) NOT NULL COMMENT 'links to nationalities.id';
ALTER TABLE `institution_student_admission` CHANGE `new_education_grade_id` `new_education_grade_id` int(11) NULL COMMENT 'links to education_grades.id';
ALTER TABLE `training_courses_prerequisites` CHANGE `prerequisite_training_course_id` `prerequisite_training_course_id` int(11) NOT NULL COMMENT 'links to training_courses.id';
ALTER TABLE `staff_qualifications` CHANGE `qualification_institution_id` `qualification_institution_id` int(11) NOT NULL COMMENT 'links to qualification_institutions.id';
ALTER TABLE `staff_qualifications` CHANGE `qualification_level_id` `qualification_level_id` int(11) NOT NULL COMMENT 'links to qualification_levels.id';
ALTER TABLE `qualification_specialisation_subjects` CHANGE `qualification_specialisation_id` `qualification_specialisation_id` int(11) NOT NULL COMMENT 'links to qualification_specialisations.id';
ALTER TABLE `staff_qualifications` CHANGE `qualification_specialisation_id` `qualification_specialisation_id` int(11) NOT NULL COMMENT 'links to qualification_specialisations.id';
ALTER TABLE `institution_quality_visits` CHANGE `quality_visit_type_id` `quality_visit_type_id` int(11) NOT NULL COMMENT 'links to quality_visit_types.id';
ALTER TABLE `institution_quality_rubric_answers` CHANGE `rubric_criteria_id` `rubric_criteria_id` int(11) NOT NULL COMMENT 'links to rubric_criterias.id';
ALTER TABLE `rubric_criteria_options` CHANGE `rubric_criteria_id` `rubric_criteria_id` int(11) NOT NULL COMMENT 'links to rubric_criterias.id';
ALTER TABLE `institution_quality_rubric_answers` CHANGE `rubric_criteria_option_id` `rubric_criteria_option_id` int(11) NULL COMMENT 'links to rubric_criteria_options.id';
ALTER TABLE `institution_quality_rubric_answers` CHANGE `rubric_section_id` `rubric_section_id` int(11) NOT NULL COMMENT 'links to rubric_sections.id';
ALTER TABLE `rubric_criterias` CHANGE `rubric_section_id` `rubric_section_id` int(11) NOT NULL COMMENT 'links to rubric_sections.id';
ALTER TABLE `rubric_status_periods` CHANGE `rubric_status_id` `rubric_status_id` int(11) NOT NULL COMMENT 'links to rubric_statuses.id';
ALTER TABLE `rubric_status_programmes` CHANGE `rubric_status_id` `rubric_status_id` int(11) NOT NULL COMMENT 'links to rubric_statuses.id';
ALTER TABLE `rubric_status_roles` CHANGE `rubric_status_id` `rubric_status_id` int(11) NOT NULL COMMENT 'links to rubric_statuses.id';
ALTER TABLE `institution_quality_rubrics` CHANGE `rubric_template_id` `rubric_template_id` int(11) NOT NULL COMMENT 'links to rubric_templates.id';
ALTER TABLE `rubric_sections` CHANGE `rubric_template_id` `rubric_template_id` int(11) NOT NULL COMMENT 'links to rubric_templates.id';
ALTER TABLE `rubric_statuses` CHANGE `rubric_template_id` `rubric_template_id` int(11) NOT NULL COMMENT 'links to rubric_templates.id';
ALTER TABLE `rubric_template_options` CHANGE `rubric_template_id` `rubric_template_id` int(11) NOT NULL COMMENT 'links to rubric_templates.id';
ALTER TABLE `rubric_criteria_options` CHANGE `rubric_template_option_id` `rubric_template_option_id` int(11) NOT NULL COMMENT 'links to rubric_template_options.id';
ALTER TABLE `staff_salary_additions` CHANGE `salary_addition_type_id` `salary_addition_type_id` int(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `staff_salary_deductions` CHANGE `salary_deduction_type_id` `salary_deduction_type_id` int(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `security_role_functions` CHANGE `security_function_id` `security_function_id` int(11) NOT NULL COMMENT 'links to security_functions.id';
ALTER TABLE `security_group_areas` CHANGE `security_group_id` `security_group_id` int(11) NOT NULL COMMENT 'links to security_groups.id';
ALTER TABLE `security_group_institutions` CHANGE `security_group_id` `security_group_id` int(11) NOT NULL COMMENT 'links to security_groups.id';
ALTER TABLE `security_group_users` CHANGE `security_group_id` `security_group_id` int(11) NOT NULL COMMENT 'links to security_groups.id';
ALTER TABLE `security_roles` CHANGE `security_group_id` `security_group_id` int(11) NOT NULL COMMENT 'links to security_groups.id';
ALTER TABLE `institution_staff` CHANGE `security_group_user_id` `security_group_user_id` char(36) NULL COMMENT 'links to security_group_users.id';
ALTER TABLE `alert_roles` CHANGE `security_role_id` `security_role_id` int(11) NOT NULL COMMENT 'links to security_roles.id';
ALTER TABLE `rubric_status_roles` CHANGE `security_role_id` `security_role_id` int(11) NOT NULL COMMENT 'links to security_roles.id';
ALTER TABLE `security_group_users` CHANGE `security_role_id` `security_role_id` int(11) NOT NULL COMMENT 'links to security_roles.id';
ALTER TABLE `security_role_functions` CHANGE `security_role_id` `security_role_id` int(11) NOT NULL COMMENT 'links to security_roles.id';
ALTER TABLE `staff_position_titles` CHANGE `security_role_id` `security_role_id` int(11) NOT NULL COMMENT 'links to security_roles.id';
ALTER TABLE `workflow_steps_roles` CHANGE `security_role_id` `security_role_id` int(11) NOT NULL COMMENT 'links to security_roles.id';
ALTER TABLE `security_group_users` CHANGE `security_user_id` `security_user_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `staff_training_self_studies` CHANGE `security_user_id` `security_user_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `student_extracurriculars` CHANGE `security_user_id` `security_user_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `user_activities` CHANGE `security_user_id` `security_user_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `user_attachments` CHANGE `security_user_id` `security_user_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `user_awards` CHANGE `security_user_id` `security_user_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `user_bank_accounts` CHANGE `security_user_id` `security_user_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `user_comments` CHANGE `security_user_id` `security_user_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `user_contacts` CHANGE `security_user_id` `security_user_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `user_health_allergies` CHANGE `security_user_id` `security_user_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `user_health_consultations` CHANGE `security_user_id` `security_user_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `user_health_families` CHANGE `security_user_id` `security_user_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `user_health_histories` CHANGE `security_user_id` `security_user_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `user_health_immunizations` CHANGE `security_user_id` `security_user_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `user_health_medications` CHANGE `security_user_id` `security_user_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `user_health_tests` CHANGE `security_user_id` `security_user_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `user_healths` CHANGE `security_user_id` `security_user_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `user_identities` CHANGE `security_user_id` `security_user_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `user_languages` CHANGE `security_user_id` `security_user_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `user_nationalities` CHANGE `security_user_id` `security_user_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `user_special_needs` CHANGE `security_user_id` `security_user_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `user_special_needs` CHANGE `special_need_difficulty_id` `special_need_difficulty_id` int(11) NOT NULL COMMENT 'links to special_need_difficulties.id';
ALTER TABLE `user_special_needs` CHANGE `special_need_type_id` `special_need_type_id` int(11) NOT NULL COMMENT 'links to special_need_types.id';
ALTER TABLE `institution_staff_absences` CHANGE `staff_absence_reason_id` `staff_absence_reason_id` int(11) NOT NULL COMMENT 'links to staff_absence_reasons.id';
ALTER TABLE `staff_behaviours` CHANGE `staff_behaviour_category_id` `staff_behaviour_category_id` int(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `institution_staff_position_profiles` CHANGE `staff_change_type_id` `staff_change_type_id` int(11) NOT NULL COMMENT 'links to staff_change_types.id';
ALTER TABLE `staff_custom_field_options` CHANGE `staff_custom_field_id` `staff_custom_field_id` int(11) NOT NULL COMMENT 'links to staff_custom_fields.id';
ALTER TABLE `staff_custom_field_values` CHANGE `staff_custom_field_id` `staff_custom_field_id` int(11) NOT NULL COMMENT 'links to staff_custom_fields.id';
ALTER TABLE `staff_custom_forms_fields` CHANGE `staff_custom_field_id` `staff_custom_field_id` int(11) NOT NULL COMMENT 'links to staff_custom_fields.id';
ALTER TABLE `staff_custom_table_cells` CHANGE `staff_custom_field_id` `staff_custom_field_id` int(11) NOT NULL COMMENT 'links to staff_custom_fields.id';
ALTER TABLE `staff_custom_table_columns` CHANGE `staff_custom_field_id` `staff_custom_field_id` int(11) NOT NULL COMMENT 'links to staff_custom_fields.id';
ALTER TABLE `staff_custom_table_rows` CHANGE `staff_custom_field_id` `staff_custom_field_id` int(11) NOT NULL COMMENT 'links to staff_custom_fields.id';
ALTER TABLE `staff_custom_forms_fields` CHANGE `staff_custom_form_id` `staff_custom_form_id` int(11) NOT NULL COMMENT 'links to staff_custom_forms.id';
ALTER TABLE `staff_custom_table_cells` CHANGE `staff_custom_table_column_id` `staff_custom_table_column_id` int(11) NOT NULL COMMENT 'links to staff_custom_table_columns.id';
ALTER TABLE `staff_custom_table_cells` CHANGE `staff_custom_table_row_id` `staff_custom_table_row_id` int(11) NOT NULL COMMENT 'links to staff_custom_table_rows.id';
ALTER TABLE `institution_classes` CHANGE `staff_id` `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `institution_quality_rubrics` CHANGE `staff_id` `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `institution_quality_visits` CHANGE `staff_id` `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `institution_staff` CHANGE `staff_id` `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `institution_staff_absences` CHANGE `staff_id` `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `institution_staff_assignments` CHANGE `staff_id` `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `institution_staff_position_profiles` CHANGE `staff_id` `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `institution_subject_staff` CHANGE `staff_id` `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `staff_behaviours` CHANGE `staff_id` `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `staff_custom_field_values` CHANGE `staff_id` `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `staff_custom_table_cells` CHANGE `staff_id` `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `staff_employments` CHANGE `staff_id` `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `staff_extracurriculars` CHANGE `staff_id` `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `staff_leaves` CHANGE `staff_id` `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `staff_licenses` CHANGE `staff_id` `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `staff_memberships` CHANGE `staff_id` `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `staff_qualifications` CHANGE `staff_id` `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `staff_salaries` CHANGE `staff_id` `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `staff_training_needs` CHANGE `staff_id` `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `staff_trainings` CHANGE `staff_id` `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `staff_leaves` CHANGE `staff_leave_type_id` `staff_leave_type_id` int(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `institution_positions` CHANGE `staff_position_grade_id` `staff_position_grade_id` int(11) NOT NULL COMMENT 'links to staff_position_grades.id';
ALTER TABLE `institution_positions` CHANGE `staff_position_title_id` `staff_position_title_id` int(11) NOT NULL COMMENT 'links to staff_position_titles.id';
ALTER TABLE `staff_salary_additions` CHANGE `staff_salary_id` `staff_salary_id` int(11) NOT NULL COMMENT 'links to staff_salaries.id';
ALTER TABLE `staff_salary_deductions` CHANGE `staff_salary_id` `staff_salary_id` int(11) NOT NULL COMMENT 'links to staff_salaries.id';
ALTER TABLE `institution_staff` CHANGE `staff_status_id` `staff_status_id` int(3) NOT NULL COMMENT 'links to staff_statuses.id';
ALTER TABLE `staff_trainings` CHANGE `staff_training_category_id` `staff_training_category_id` int(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `staff_training_self_study_attachments` CHANGE `staff_training_self_study_id` `staff_training_self_study_id` int(11) NOT NULL COMMENT 'links to staff_training_self_studies.id';
ALTER TABLE `staff_training_self_study_results` CHANGE `staff_training_self_study_id` `staff_training_self_study_id` int(11) NOT NULL COMMENT 'links to staff_training_self_studies.id';
ALTER TABLE `institution_staff` CHANGE `staff_type_id` `staff_type_id` int(5) NOT NULL COMMENT 'links to staff_types.id';
ALTER TABLE `institution_staff_assignments` CHANGE `staff_type_id` `staff_type_id` int(5) NOT NULL COMMENT 'links to staff_types.id';
ALTER TABLE `institution_staff_position_profiles` CHANGE `staff_type_id` `staff_type_id` int(5) NOT NULL COMMENT 'links to staff_types.id';
ALTER TABLE `institution_positions` CHANGE `status_id` `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id';
ALTER TABLE `institution_repeater_surveys` CHANGE `status_id` `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id';
ALTER TABLE `institution_staff_position_profiles` CHANGE `status_id` `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id';
ALTER TABLE `institution_student_surveys` CHANGE `status_id` `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id';
ALTER TABLE `institution_surveys` CHANGE `status_id` `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id';
ALTER TABLE `staff_leaves` CHANGE `status_id` `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id';
ALTER TABLE `staff_training_needs` CHANGE `status_id` `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id';
ALTER TABLE `training_courses` CHANGE `status_id` `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id';
ALTER TABLE `training_session_results` CHANGE `status_id` `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id';
ALTER TABLE `training_sessions` CHANGE `status_id` `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id';
ALTER TABLE `institution_student_absences` CHANGE `student_absence_reason_id` `student_absence_reason_id` int(11) NOT NULL COMMENT 'links to student_absence_reasons.id';
ALTER TABLE `student_behaviours` CHANGE `student_behaviour_category_id` `student_behaviour_category_id` int(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `student_custom_field_options` CHANGE `student_custom_field_id` `student_custom_field_id` int(11) NOT NULL COMMENT 'links to student_custom_fields.id';
ALTER TABLE `student_custom_field_values` CHANGE `student_custom_field_id` `student_custom_field_id` int(11) NOT NULL COMMENT 'links to student_custom_fields.id';
ALTER TABLE `student_custom_forms_fields` CHANGE `student_custom_field_id` `student_custom_field_id` int(11) NOT NULL COMMENT 'links to student_custom_fields.id';
ALTER TABLE `student_custom_table_cells` CHANGE `student_custom_field_id` `student_custom_field_id` int(11) NOT NULL COMMENT 'links to student_custom_fields.id';
ALTER TABLE `student_custom_table_columns` CHANGE `student_custom_field_id` `student_custom_field_id` int(11) NOT NULL COMMENT 'links to student_custom_fields.id';
ALTER TABLE `student_custom_table_rows` CHANGE `student_custom_field_id` `student_custom_field_id` int(11) NOT NULL COMMENT 'links to student_custom_fields.id';
ALTER TABLE `student_custom_forms_fields` CHANGE `student_custom_form_id` `student_custom_form_id` int(11) NOT NULL COMMENT 'links to student_custom_forms.id';
ALTER TABLE `student_custom_table_cells` CHANGE `student_custom_table_column_id` `student_custom_table_column_id` int(11) NOT NULL COMMENT 'links to student_custom_table_columns.id';
ALTER TABLE `student_custom_table_cells` CHANGE `student_custom_table_row_id` `student_custom_table_row_id` int(11) NOT NULL COMMENT 'links to student_custom_table_rows.id';
ALTER TABLE `institution_student_dropout` CHANGE `student_dropout_reason_id` `student_dropout_reason_id` int(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `institution_class_students` CHANGE `student_id` `student_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `institution_student_absences` CHANGE `student_id` `student_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `institution_student_admission` CHANGE `student_id` `student_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `institution_student_dropout` CHANGE `student_id` `student_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `institution_student_surveys` CHANGE `student_id` `student_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `institution_students` CHANGE `student_id` `student_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `institution_subject_students` CHANGE `student_id` `student_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `student_behaviours` CHANGE `student_id` `student_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `student_custom_field_values` CHANGE `student_id` `student_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `student_custom_table_cells` CHANGE `student_id` `student_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `student_fees` CHANGE `student_id` `student_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `student_guardians` CHANGE `student_id` `student_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `institution_class_students` CHANGE `student_status_id` `student_status_id` int(11) NOT NULL COMMENT 'links to student_statuses.id';
ALTER TABLE `institution_students` CHANGE `student_status_id` `student_status_id` int(11) NOT NULL COMMENT 'links to student_statuses.id';
ALTER TABLE `institution_student_admission` CHANGE `student_transfer_reason_id` `student_transfer_reason_id` int(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `institution_repeater_surveys` CHANGE `survey_form_id` `survey_form_id` int(11) NOT NULL COMMENT 'links to survey_forms.id';
ALTER TABLE `institution_student_surveys` CHANGE `survey_form_id` `survey_form_id` int(11) NOT NULL COMMENT 'links to survey_forms.id';
ALTER TABLE `institution_surveys` CHANGE `survey_form_id` `survey_form_id` int(11) NOT NULL COMMENT 'links to survey_forms.id';
ALTER TABLE `survey_forms_questions` CHANGE `survey_form_id` `survey_form_id` int(11) NOT NULL COMMENT 'links to survey_forms.id';
ALTER TABLE `survey_rules` CHANGE `survey_form_id` `survey_form_id` int(11) NOT NULL COMMENT 'links to survey_forms.id';
ALTER TABLE `survey_statuses` CHANGE `survey_form_id` `survey_form_id` int(11) NOT NULL COMMENT 'links to survey_forms.id';
ALTER TABLE `institution_repeater_survey_answers` CHANGE `survey_question_id` `survey_question_id` int(11) NOT NULL COMMENT 'links to survey_questions.id';
ALTER TABLE `institution_repeater_survey_table_cells` CHANGE `survey_question_id` `survey_question_id` int(11) NOT NULL COMMENT 'links to survey_questions.id';
ALTER TABLE `institution_student_survey_answers` CHANGE `survey_question_id` `survey_question_id` int(11) NOT NULL COMMENT 'links to survey_questions.id';
ALTER TABLE `institution_student_survey_table_cells` CHANGE `survey_question_id` `survey_question_id` int(11) NOT NULL COMMENT 'links to survey_questions.id';
ALTER TABLE `institution_survey_answers` CHANGE `survey_question_id` `survey_question_id` int(11) NOT NULL COMMENT 'links to survey_questions.id';
ALTER TABLE `institution_survey_table_cells` CHANGE `survey_question_id` `survey_question_id` int(11) NOT NULL COMMENT 'links to survey_questions.id';
ALTER TABLE `survey_forms_questions` CHANGE `survey_question_id` `survey_question_id` int(11) NOT NULL COMMENT 'links to survey_questions.id';
ALTER TABLE `survey_question_choices` CHANGE `survey_question_id` `survey_question_id` int(11) NOT NULL COMMENT 'links to survey_questions.id';
ALTER TABLE `survey_rules` CHANGE `survey_question_id` `survey_question_id` int(11) NOT NULL COMMENT 'links to survey_questions.id';
ALTER TABLE `survey_table_columns` CHANGE `survey_question_id` `survey_question_id` int(11) NOT NULL COMMENT 'links to survey_questions.id';
ALTER TABLE `survey_table_rows` CHANGE `survey_question_id` `survey_question_id` int(11) NOT NULL COMMENT 'links to survey_questions.id';
ALTER TABLE `survey_status_periods` CHANGE `survey_status_id` `survey_status_id` int(11) NOT NULL COMMENT 'links to survey_statuses.id';
ALTER TABLE `institution_repeater_survey_table_cells` CHANGE `survey_table_column_id` `survey_table_column_id` int(11) NOT NULL COMMENT 'links to survey_table_columns.id';
ALTER TABLE `institution_student_survey_table_cells` CHANGE `survey_table_column_id` `survey_table_column_id` int(11) NOT NULL COMMENT 'links to survey_table_columns.id';
ALTER TABLE `institution_survey_table_cells` CHANGE `survey_table_column_id` `survey_table_column_id` int(11) NOT NULL COMMENT 'links to survey_table_columns.id';
ALTER TABLE `institution_repeater_survey_table_cells` CHANGE `survey_table_row_id` `survey_table_row_id` int(11) NOT NULL COMMENT 'links to survey_table_rows.id';
ALTER TABLE `institution_student_survey_table_cells` CHANGE `survey_table_row_id` `survey_table_row_id` int(11) NOT NULL COMMENT 'links to survey_table_rows.id';
ALTER TABLE `institution_survey_table_cells` CHANGE `survey_table_row_id` `survey_table_row_id` int(11) NOT NULL COMMENT 'links to survey_table_rows.id';
ALTER TABLE `training_courses_target_populations` CHANGE `target_population_id` `target_population_id` int(11) NOT NULL COMMENT 'links to staff_position_titles.id';
ALTER TABLE `training_session_trainee_results` CHANGE `trainee_id` `trainee_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `training_sessions_trainees` CHANGE `trainee_id` `trainee_id` int(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `training_session_trainers` CHANGE `trainer_id` `trainer_id` int(11) NULL COMMENT 'links to security_users.id';
ALTER TABLE `staff_training_self_studies` CHANGE `training_achievement_type_id` `training_achievement_type_id` int(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `training_courses_prerequisites` CHANGE `training_course_id` `training_course_id` int(11) NOT NULL COMMENT 'links to training_courses.id';
ALTER TABLE `training_courses_providers` CHANGE `training_course_id` `training_course_id` int(11) NOT NULL COMMENT 'links to training_courses.id';
ALTER TABLE `training_courses_result_types` CHANGE `training_course_id` `training_course_id` int(11) NOT NULL COMMENT 'links to training_courses.id';
ALTER TABLE `training_courses_specialisations` CHANGE `training_course_id` `training_course_id` int(11) NOT NULL COMMENT 'links to training_courses.id';
ALTER TABLE `training_courses_target_populations` CHANGE `training_course_id` `training_course_id` int(11) NOT NULL COMMENT 'links to training_courses.id';
ALTER TABLE `training_sessions` CHANGE `training_course_id` `training_course_id` int(11) NOT NULL COMMENT 'links to training_courses.id';
ALTER TABLE `training_courses` CHANGE `training_course_type_id` `training_course_type_id` int(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `training_courses` CHANGE `training_field_of_study_id` `training_field_of_study_id` int(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `training_courses` CHANGE `training_level_id` `training_level_id` int(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `training_courses` CHANGE `training_mode_of_delivery_id` `training_mode_of_delivery_id` int(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `staff_training_needs` CHANGE `training_need_category_id` `training_need_category_id` int(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `staff_training_needs` CHANGE `training_priority_id` `training_priority_id` int(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `training_courses_providers` CHANGE `training_provider_id` `training_provider_id` int(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `training_sessions` CHANGE `training_provider_id` `training_provider_id` int(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `staff_training_needs` CHANGE `training_requirement_id` `training_requirement_id` int(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `training_courses` CHANGE `training_requirement_id` `training_requirement_id` int(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `training_courses_result_types` CHANGE `training_result_type_id` `training_result_type_id` int(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `training_session_trainee_results` CHANGE `training_result_type_id` `training_result_type_id` int(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `training_session_results` CHANGE `training_session_id` `training_session_id` int(11) NOT NULL COMMENT 'links to training_sessions.id';
ALTER TABLE `training_session_trainee_results` CHANGE `training_session_id` `training_session_id` int(11) NOT NULL COMMENT 'links to training_sessions.id';
ALTER TABLE `training_session_trainers` CHANGE `training_session_id` `training_session_id` int(11) NOT NULL COMMENT 'links to training_sessions.id';
ALTER TABLE `training_sessions_trainees` CHANGE `training_session_id` `training_session_id` int(11) NOT NULL COMMENT 'links to training_sessions.id';
ALTER TABLE `training_courses_specialisations` CHANGE `training_specialisation_id` `training_specialisation_id` int(11) NOT NULL COMMENT 'links to field_option_values.id';
ALTER TABLE `workflow_steps` CHANGE `workflow_id` `workflow_id` int(11) NOT NULL COMMENT 'links to workflows.id';
ALTER TABLE `workflows_filters` CHANGE `workflow_id` `workflow_id` int(11) NOT NULL COMMENT 'links to workflows.id';
ALTER TABLE `workflow_records` CHANGE `workflow_model_id` `workflow_model_id` int(11) NOT NULL COMMENT 'links to workflow_models.id';
ALTER TABLE `workflow_statuses` CHANGE `workflow_model_id` `workflow_model_id` int(11) NOT NULL COMMENT 'links to workflow_models.id';
ALTER TABLE `workflows` CHANGE `workflow_model_id` `workflow_model_id` int(11) NOT NULL COMMENT 'links to workflow_models.id';
ALTER TABLE `workflow_comments` CHANGE `workflow_record_id` `workflow_record_id` int(11) NOT NULL COMMENT 'links to workflow_records.id';
ALTER TABLE `workflow_transitions` CHANGE `workflow_record_id` `workflow_record_id` int(11) NOT NULL COMMENT 'links to workflow_records.id';
ALTER TABLE `workflow_statuses_steps` CHANGE `workflow_status_id` `workflow_status_id` int(11) NOT NULL COMMENT 'links to workflow_statuses.id';
ALTER TABLE `workflow_actions` CHANGE `workflow_step_id` `workflow_step_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id';
ALTER TABLE `workflow_records` CHANGE `workflow_step_id` `workflow_step_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id';
ALTER TABLE `workflow_statuses_steps` CHANGE `workflow_step_id` `workflow_step_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id';
ALTER TABLE `workflow_steps_roles` CHANGE `workflow_step_id` `workflow_step_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id';


-- adding table descriptions
ALTER TABLE `absence_types` COMMENT = 'This table contains absence types used in absence records';
ALTER TABLE `academic_periods` COMMENT = 'This table contains the list of academic school periods';
ALTER TABLE `academic_period_levels` COMMENT = '';
ALTER TABLE `alerts` COMMENT = '';
ALTER TABLE `alert_logs` COMMENT = '';
ALTER TABLE `alert_roles` COMMENT = '';
ALTER TABLE `api_authorizations` COMMENT = 'This table contains the security tokens that allow public to access OpenEMIS Core APIs';
ALTER TABLE `areas` COMMENT = 'This table contains the educational areas of the country';
ALTER TABLE `area_administratives` COMMENT = 'This table contains the administrative areas of the country';
ALTER TABLE `area_administrative_levels` COMMENT = 'This table contains the administrative area levels of the country';
ALTER TABLE `area_levels` COMMENT = 'This table contains the educational area levels of the country';
ALTER TABLE `assessments` COMMENT = 'This table contains the assessment template for a specific grade';
ALTER TABLE `assessment_grading_options` COMMENT = 'This table contains all options linked to a specific grading type';
ALTER TABLE `assessment_grading_types` COMMENT = 'This table contains the list of grading types that can be used for an assessable subject';
ALTER TABLE `assessment_items` COMMENT = 'This table contains the list of assessable subjects for a specific grade';
ALTER TABLE `assessment_item_results` COMMENT = 'This table contains all the assessment results for an individual student in an institution';
ALTER TABLE `assessment_periods` COMMENT = 'This table contains the list of periods for a specific assessment';
ALTER TABLE `authentication_type_attributes` COMMENT = 'This table contains the list of attributes that can be configured for specific authentication method';
ALTER TABLE `banks` COMMENT = 'This table contains the bank information used by institutions and individuals';
ALTER TABLE `bank_branches` COMMENT = 'This table contains the bank branches information used by institutions and individuals';
ALTER TABLE `config_attachments` COMMENT = '';
ALTER TABLE `config_items` COMMENT = 'This table contains the list of configurations used by the system';
ALTER TABLE `config_item_options` COMMENT = 'This table contains the list of configuration options for specific configuration';
ALTER TABLE `contact_options` COMMENT = 'This table contains the options of specific type of contacts';
ALTER TABLE `contact_types` COMMENT = 'This table contains the different types of contacts';
ALTER TABLE `countries` COMMENT = 'This table contains the list of countries of the world';
ALTER TABLE `custom_fields` COMMENT = '';
ALTER TABLE `custom_field_options` COMMENT = '';
ALTER TABLE `custom_field_types` COMMENT = '';
ALTER TABLE `custom_field_values` COMMENT = '';
ALTER TABLE `custom_forms` COMMENT = '';
ALTER TABLE `custom_forms_fields` COMMENT = '';
ALTER TABLE `custom_forms_filters` COMMENT = '';
ALTER TABLE `custom_modules` COMMENT = '';
ALTER TABLE `custom_records` COMMENT = '';
ALTER TABLE `custom_table_cells` COMMENT = '';
ALTER TABLE `custom_table_columns` COMMENT = '';
ALTER TABLE `custom_table_rows` COMMENT = '';
ALTER TABLE `db_patches` COMMENT = 'Internal use - for tracking jira issues containing sql patches executed on this database';
ALTER TABLE `deleted_records` COMMENT = 'This table contains data of previously deleted records';
ALTER TABLE `education_certifications` COMMENT = 'This table contains all information related to academic certifications';
ALTER TABLE `education_cycles` COMMENT = 'This table contains the list of education cycles';
ALTER TABLE `education_field_of_studies` COMMENT = 'This table contains the list of field of studies';
ALTER TABLE `education_grades` COMMENT = 'This table contains the list of education grades linked to specific education programmes';
ALTER TABLE `education_grades_subjects` COMMENT = 'This table contains the list of subjects linked to specific education grade';
ALTER TABLE `education_levels` COMMENT = 'This table contains the list of education levels';
ALTER TABLE `education_level_isced` COMMENT = '';
ALTER TABLE `education_programmes` COMMENT = 'This table contains the list of education programmes';
ALTER TABLE `education_programmes_next_programmes` COMMENT = 'This table contains the next programme linked to existing programmes';
ALTER TABLE `education_programme_orientations` COMMENT = 'This table contains the programme orientation such as General or Vocational';
ALTER TABLE `education_subjects` COMMENT = 'This table contains the educational subjects';
ALTER TABLE `education_systems` COMMENT = 'This table contains the list of education systems';
ALTER TABLE `employment_types` COMMENT = 'This is a field option table used by staff_employments';
ALTER TABLE `extracurricular_types` COMMENT = 'This is a field option table used by student_extracurriculars/staff_extracurriculars';
ALTER TABLE `fee_types` COMMENT = 'This table contains the list of items chargable to individual students';
ALTER TABLE `field_options` COMMENT = '';
ALTER TABLE `field_option_values` COMMENT = '';
ALTER TABLE `genders` COMMENT = 'This table contain the two types of gender (Male / Female) used by security_users';
ALTER TABLE `health_allergy_types` COMMENT = 'This is a field option table used by user_health_allergies';
ALTER TABLE `health_conditions` COMMENT = 'This is a field option table containing different types of health conditions used by user_health_families and user_health_histories';
ALTER TABLE `health_consultation_types` COMMENT = '';
ALTER TABLE `health_immunization_types` COMMENT = '';
ALTER TABLE `health_relationships` COMMENT = '';
ALTER TABLE `health_test_types` COMMENT = '';
ALTER TABLE `identity_types` COMMENT = 'This is a field option table containing the different types of identity used by user_identities';
ALTER TABLE `import_mapping` COMMENT = 'This table contains the mapping of the fields used by all Import features';
ALTER TABLE `infrastructure_conditions` COMMENT = '';
ALTER TABLE `infrastructure_custom_fields` COMMENT = '';
ALTER TABLE `infrastructure_custom_field_options` COMMENT = '';
ALTER TABLE `infrastructure_custom_field_values` COMMENT = '';
ALTER TABLE `infrastructure_custom_forms` COMMENT = '';
ALTER TABLE `infrastructure_custom_forms_fields` COMMENT = '';
ALTER TABLE `infrastructure_custom_forms_filters` COMMENT = '';
ALTER TABLE `infrastructure_levels` COMMENT = '';
ALTER TABLE `infrastructure_ownerships` COMMENT = 'This is a field option table containing the different types of ownerships used by institution_infrastructures';
ALTER TABLE `infrastructure_types` COMMENT = 'This table contains the different types of infrastructure';
ALTER TABLE `institutions` COMMENT = 'This table contains information of every institution';
ALTER TABLE `institution_activities` COMMENT = 'This table contains all modification of information related to every institution';
ALTER TABLE `institution_attachments` COMMENT = 'This table contains any file type information related to every institution';
ALTER TABLE `institution_bank_accounts` COMMENT = 'This table contains the bank accounts used by institutions';
ALTER TABLE `institution_classes` COMMENT = 'This table contains the list of classes by grade and academic period in every institution';
ALTER TABLE `institution_class_grades` COMMENT = 'This table contains multi-grade classes information';
ALTER TABLE `institution_class_students` COMMENT = 'This table contains information of students in classes';
ALTER TABLE `institution_class_subjects` COMMENT = 'This table contains information about the subjects of any class';
ALTER TABLE `institution_custom_fields` COMMENT = 'This table contains the list of custom fields used by institution';
ALTER TABLE `institution_custom_field_options` COMMENT = '';
ALTER TABLE `institution_custom_field_values` COMMENT = '';
ALTER TABLE `institution_custom_forms` COMMENT = '';
ALTER TABLE `institution_custom_forms_fields` COMMENT = '';
ALTER TABLE `institution_custom_forms_filters` COMMENT = '';
ALTER TABLE `institution_custom_table_cells` COMMENT = '';
ALTER TABLE `institution_custom_table_columns` COMMENT = '';
ALTER TABLE `institution_custom_table_rows` COMMENT = '';
ALTER TABLE `institution_fees` COMMENT = 'This table contains the total chargable amount by grade and year for all institutions';
ALTER TABLE `institution_fee_types` COMMENT = '';
ALTER TABLE `institution_genders` COMMENT = 'This is a field option table containing the list of genders used by institutions';
ALTER TABLE `institution_grades` COMMENT = 'This table contains information about education grades offered by institutions';
ALTER TABLE `institution_infrastructures` COMMENT = 'This table contains the infrastructure information of institutions';
ALTER TABLE `institution_localities` COMMENT = 'This is a field option table containing the list of user-defined locality used by institutions';
ALTER TABLE `institution_network_connectivities` COMMENT = 'This is a field option table containing the list of user-defined network_types used by institutions';
ALTER TABLE `institution_ownerships` COMMENT = 'This is a field option table containing the list of user-defined ownerships used by institutions';
ALTER TABLE `institution_positions` COMMENT = 'This table contains the list of positions offered by the institutions';
ALTER TABLE `institution_providers` COMMENT = 'This is a field option table containing the list of user-defined providers used by institutions';
ALTER TABLE `institution_quality_rubrics` COMMENT = '';
ALTER TABLE `institution_quality_rubric_answers` COMMENT = '';
ALTER TABLE `institution_quality_visits` COMMENT = '';
ALTER TABLE `institution_repeater_surveys` COMMENT = 'This table contains repeater type questions in a survey';
ALTER TABLE `institution_repeater_survey_answers` COMMENT = 'This table contains repeater type answers of a survey';
ALTER TABLE `institution_repeater_survey_table_cells` COMMENT = '';
ALTER TABLE `institution_sectors` COMMENT = 'This is a field option table containing the list of user-defined sectors used by institutions';
ALTER TABLE `institution_shifts` COMMENT = 'This table contains all shifts offered by every institution';
ALTER TABLE `institution_staff` COMMENT = 'This table contains information of all staff in every institution';
ALTER TABLE `institution_staff_absences` COMMENT = 'This table contains absence records of staff';
ALTER TABLE `institution_staff_assignments` COMMENT = 'This table contains staff tranfer requests';
ALTER TABLE `institution_staff_position_profiles` COMMENT = 'This table contains change requests submitted for Staff profiles';
ALTER TABLE `institution_statuses` COMMENT = 'This is a field option table containing the list of user-defined statuses used by institutions';
ALTER TABLE `institution_students` COMMENT = 'This table contains information of all students in every institution';
ALTER TABLE `institution_student_absences` COMMENT = 'This table contains absence records of students';
ALTER TABLE `institution_student_admission` COMMENT = 'This table contains student admission records';
ALTER TABLE `institution_student_dropout` COMMENT = 'This table contains all student dropout requests';
ALTER TABLE `institution_student_surveys` COMMENT = 'This table contains the student list type questions in a survey';
ALTER TABLE `institution_student_survey_answers` COMMENT = 'This table contains the student list answers of a survey';
ALTER TABLE `institution_student_survey_table_cells` COMMENT = '';
ALTER TABLE `institution_subjects` COMMENT = 'This table contains the list of subjects taught by the institution';
ALTER TABLE `institution_subject_staff` COMMENT = 'This table contains the list of subjects taught by which teacher';
ALTER TABLE `institution_subject_students` COMMENT = 'This table contains the list of students attending the subjects';
ALTER TABLE `institution_surveys` COMMENT = 'This table contains the list of forms that all institutions need to complete and their current progress';
ALTER TABLE `institution_survey_answers` COMMENT = 'This table contains the answers to each question in a form';
ALTER TABLE `institution_survey_table_cells` COMMENT = 'This table contains the values of a table-type question in a form';
ALTER TABLE `institution_types` COMMENT = 'This is a field option table containing the list of user-defined types used by institutions';
ALTER TABLE `labels` COMMENT = 'This table contains all field labels used in the system';
ALTER TABLE `languages` COMMENT = 'This table contains the list of possible languages';
ALTER TABLE `license_types` COMMENT = 'This is a field option table containing the list of user-defined type of licences used by staff_licenses';
ALTER TABLE `nationalities` COMMENT = 'This is a field option table containing the list of user-defined nationalities used by user_nationalities';
ALTER TABLE `notices` COMMENT = 'This table contains all notices that are displayed on the dashboard';
ALTER TABLE `qualification_institutions` COMMENT = 'This table contains the list of institutions that can ';
ALTER TABLE `qualification_levels` COMMENT = 'Not in used atm';
ALTER TABLE `qualification_specialisations` COMMENT = 'This table contains the specialisations of the qualifications';
ALTER TABLE `qualification_specialisation_subjects` COMMENT = 'This table contains the subjects that can be taught by teachers with the specialisations';
ALTER TABLE `quality_visit_types` COMMENT = '';
ALTER TABLE `report_progress` COMMENT = '';
ALTER TABLE `rubric_criterias` COMMENT = '';
ALTER TABLE `rubric_criteria_options` COMMENT = '';
ALTER TABLE `rubric_sections` COMMENT = '';
ALTER TABLE `rubric_statuses` COMMENT = '';
ALTER TABLE `rubric_status_periods` COMMENT = '';
ALTER TABLE `rubric_status_programmes` COMMENT = '';
ALTER TABLE `rubric_status_roles` COMMENT = '';
ALTER TABLE `rubric_templates` COMMENT = '';
ALTER TABLE `rubric_template_options` COMMENT = '';
ALTER TABLE `security_functions` COMMENT = 'This table contains the full list of features of the system';
ALTER TABLE `security_groups` COMMENT = 'This table contains the security groups used for allowing access for users to institutions';
ALTER TABLE `security_group_areas` COMMENT = 'This table contains which educational areas are accessible by which security group';
ALTER TABLE `security_group_institutions` COMMENT = 'This table contains specific institutions that can be accessible by which security group';
ALTER TABLE `security_group_users` COMMENT = 'This table contains the list of users assigned with a role in a security group';
ALTER TABLE `security_rest_sessions` COMMENT = '';
ALTER TABLE `security_roles` COMMENT = 'This table contains the list of roles that can be assigned to a user in a security group';
ALTER TABLE `security_role_functions` COMMENT = 'This table contains the list of functions that can be accessed by the roles';
ALTER TABLE `security_users` COMMENT = 'This table contains all user information';
ALTER TABLE `sms_logs` COMMENT = '';
ALTER TABLE `sms_messages` COMMENT = '';
ALTER TABLE `sms_responses` COMMENT = '';
ALTER TABLE `special_need_types` COMMENT = 'This is a field option table containing different types of special needs';
ALTER TABLE `staff_absence_reasons` COMMENT = 'This is a field option table containing different absence reasons for staff absences';
ALTER TABLE `staff_behaviours` COMMENT = 'This table contains all behavioural records of staff';
ALTER TABLE `staff_change_types` COMMENT = '';
ALTER TABLE `staff_custom_fields` COMMENT = '';
ALTER TABLE `staff_custom_field_options` COMMENT = '';
ALTER TABLE `staff_custom_field_values` COMMENT = '';
ALTER TABLE `staff_custom_forms` COMMENT = '';
ALTER TABLE `staff_custom_forms_fields` COMMENT = '';
ALTER TABLE `staff_custom_table_cells` COMMENT = '';
ALTER TABLE `staff_custom_table_columns` COMMENT = '';
ALTER TABLE `staff_custom_table_rows` COMMENT = '';
ALTER TABLE `staff_employments` COMMENT = 'This table contains all other employment records of staff outside of OpenEMIS';
ALTER TABLE `staff_extracurriculars` COMMENT = '';
ALTER TABLE `staff_leaves` COMMENT = 'This table contains all leave applications and their statuses of staff';
ALTER TABLE `staff_licenses` COMMENT = '';
ALTER TABLE `staff_memberships` COMMENT = '';
ALTER TABLE `staff_position_grades` COMMENT = 'This is a field option table containing different position grades used in institution_positions';
ALTER TABLE `staff_position_titles` COMMENT = 'This is a field option table containing different position titles used in institution_positions';
ALTER TABLE `staff_qualifications` COMMENT = '';
ALTER TABLE `staff_salaries` COMMENT = '';
ALTER TABLE `staff_salary_additions` COMMENT = '';
ALTER TABLE `staff_salary_deductions` COMMENT = '';
ALTER TABLE `staff_statuses` COMMENT = 'This table contains the fixed list of statuses for staff in institutions';
ALTER TABLE `staff_trainings` COMMENT = '';
ALTER TABLE `staff_training_needs` COMMENT = '';
ALTER TABLE `staff_training_self_studies` COMMENT = '';
ALTER TABLE `staff_training_self_study_attachments` COMMENT = '';
ALTER TABLE `staff_training_self_study_results` COMMENT = '';
ALTER TABLE `student_absence_reasons` COMMENT = 'This is a field option table containing different absence reasons for student absences';
ALTER TABLE `student_behaviours` COMMENT = 'This table contains all behavioural records of students';
ALTER TABLE `student_custom_fields` COMMENT = '';
ALTER TABLE `student_custom_field_options` COMMENT = '';
ALTER TABLE `student_custom_field_values` COMMENT = '';
ALTER TABLE `student_custom_forms` COMMENT = '';
ALTER TABLE `student_custom_forms_fields` COMMENT = '';
ALTER TABLE `student_custom_table_cells` COMMENT = '';
ALTER TABLE `student_custom_table_columns` COMMENT = '';
ALTER TABLE `student_custom_table_rows` COMMENT = '';
ALTER TABLE `student_extracurriculars` COMMENT = '';
ALTER TABLE `student_fees` COMMENT = 'This table contains the list of payment transactions for individual student';
ALTER TABLE `student_guardians` COMMENT = 'This table contains all guardian records linked to individual student';
ALTER TABLE `student_statuses` COMMENT = 'This table contains a fixed list of statuses for students in institutions';
ALTER TABLE `survey_forms` COMMENT = 'This table contains all forms for data collection in institutions';
ALTER TABLE `survey_forms_questions` COMMENT = 'This table contains the list of questions in all forms';
ALTER TABLE `survey_questions` COMMENT = 'This table contains the full list of questions';
ALTER TABLE `survey_question_choices` COMMENT = 'This table contains the choices for dropdown-type questions';
ALTER TABLE `survey_rules` COMMENT = '';
ALTER TABLE `survey_statuses` COMMENT = '';
ALTER TABLE `survey_status_periods` COMMENT = '';
ALTER TABLE `survey_table_columns` COMMENT = '';
ALTER TABLE `survey_table_rows` COMMENT = '';
ALTER TABLE `system_processes` COMMENT = 'Internal use - to track the background processes triggered by the system';
ALTER TABLE `training_courses` COMMENT = 'This table contains all training courses';
ALTER TABLE `training_courses_prerequisites` COMMENT = 'This table contains all prerequisites related to specific training courses';
ALTER TABLE `training_courses_providers` COMMENT = 'This table contains the course providers for all training courses';
ALTER TABLE `training_courses_result_types` COMMENT = '';
ALTER TABLE `training_courses_specialisations` COMMENT = '';
ALTER TABLE `training_courses_target_populations` COMMENT = '';
ALTER TABLE `training_sessions` COMMENT = 'This table contains all training sessions';
ALTER TABLE `training_sessions_trainees` COMMENT = 'This table contains the list of trainees attending specific training sessions';
ALTER TABLE `training_session_results` COMMENT = '';
ALTER TABLE `training_session_trainee_results` COMMENT = '';
ALTER TABLE `training_session_trainers` COMMENT = 'This table contains the list of trainers for specific training sessions';
ALTER TABLE `translations` COMMENT = 'This table contains all translations of text used in the system';
ALTER TABLE `user_activities` COMMENT = 'This table contains all modification of information of every user';
ALTER TABLE `user_attachments` COMMENT = 'This table contains file type information of every user';
ALTER TABLE `user_awards` COMMENT = 'This table contains awards information of every user';
ALTER TABLE `user_bank_accounts` COMMENT = 'This table contains bank account information of every user';
ALTER TABLE `user_comments` COMMENT = '';
ALTER TABLE `user_contacts` COMMENT = 'This table contains contact information of every user';
ALTER TABLE `user_healths` COMMENT = 'This table contains health information of every user';
ALTER TABLE `user_health_allergies` COMMENT = 'This table contains health allergy information of every user';
ALTER TABLE `user_health_consultations` COMMENT = '';
ALTER TABLE `user_health_families` COMMENT = '';
ALTER TABLE `user_health_histories` COMMENT = '';
ALTER TABLE `user_health_immunizations` COMMENT = '';
ALTER TABLE `user_health_medications` COMMENT = '';
ALTER TABLE `user_health_tests` COMMENT = '';
ALTER TABLE `user_identities` COMMENT = 'This table contains identity information of every user';
ALTER TABLE `user_languages` COMMENT = 'This table contains all languages of every user that they know';
ALTER TABLE `user_nationalities` COMMENT = 'This table contains nationality information of every user';
ALTER TABLE `user_special_needs` COMMENT = 'This table contains special need information of every user';
ALTER TABLE `workflows` COMMENT = 'This table contains all user defined workflows used by the system';
ALTER TABLE `workflows_filters` COMMENT = '';
ALTER TABLE `workflow_actions` COMMENT = 'This table contains all actions used by different steps of any workflow';
ALTER TABLE `workflow_comments` COMMENT = 'This table contains comments added by users when performing a workflow action';
ALTER TABLE `workflow_models` COMMENT = 'This table contains the list of features that are workflow-enabled';
ALTER TABLE `workflow_records` COMMENT = '';
ALTER TABLE `workflow_statuses` COMMENT = 'This table contains the list of system-defined and user-defined statuses that are used by reports and other features';
ALTER TABLE `workflow_statuses_steps` COMMENT = 'This table contains the list of steps belonging to which status';
ALTER TABLE `workflow_steps` COMMENT = 'This table contains the list of steps used by all workflows';
ALTER TABLE `workflow_steps_roles` COMMENT = 'This table contains the list of steps accessible by which roles';
ALTER TABLE `workflow_transitions` COMMENT = 'This table contains specific action executed by users to transit from one step to another';


-- 3.6.1
UPDATE config_items SET value = '3.6.1' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
