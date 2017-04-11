-- institution_sites
ALTER TABLE `institutions` 
CHANGE COLUMN `institution_locality_id` `institution_site_locality_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_type_id` `institution_site_type_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_ownership_id` `institution_site_ownership_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_status_id` `institution_site_status_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_sector_id` `institution_site_sector_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_provider_id` `institution_site_provider_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_gender_id` `institution_site_gender_id` INT(5) NOT NULL COMMENT '' ,
DROP INDEX `institution_locality_id`,
DROP INDEX `institution_type_id`,
DROP INDEX `institution_ownership_id`,
DROP INDEX `institution_status_id`,
DROP INDEX `institution_provider_id` ,
ADD INDEX `institution_site_provider_id` (`institution_site_provider_id`),
DROP INDEX `institution_sector_id` ,
ADD INDEX `institution_site_sector_id` (`institution_site_sector_id`),
DROP INDEX `institution_gender_id` ,
ADD INDEX `institution_site_gender_id` (`institution_site_gender_id`),
ADD COLUMN `institution_site_area_id` INT(11) NULL COMMENT '' AFTER `latitude`, 
RENAME TO `institution_sites` ;

-- security_group_institution_sites
ALTER TABLE `security_group_institutions` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_id`,
RENAME TO  `security_group_institution_sites` ;

-- institution_custom_field_values
ALTER TABLE `institution_custom_field_values` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '',
DROP INDEX `institution_id` ,
ADD INDEX `institution_site_id` (`institution_site_id`);

-- institution_custom_table_cells
ALTER TABLE `institution_custom_table_cells` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '',
DROP INDEX `institution_id`;

-- institution_site_activities
ALTER TABLE `institution_activities` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' ,
DROP INDEX `institution_id` ,
ADD INDEX `institution_site_id` (`institution_site_id`),
RENAME TO  `institution_site_activities` ;

-- institution_attachments
ALTER TABLE `institution_attachments` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_id`, 
RENAME TO  `institution_site_attachments` ;

-- institution_positions
ALTER TABLE `institution_positions` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '', 
DROP INDEX `institution_id`, 
RENAME TO  `institution_site_positions`;

-- institution_site_staff
ALTER TABLE `institution_staff` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_position_id` `institution_site_position_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_position_id` ,
ADD INDEX `institution_site_position_id` (`institution_site_position_id`),
DROP INDEX `staff_id` ,
ADD INDEX `security_user_id` (`security_user_id`),
DROP INDEX `institution_id` ,
ADD INDEX `institution_site_id` (`institution_site_id`),
RENAME TO  `institution_site_staff` ;

-- institution_site_classes
ALTER TABLE `institution_classes` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_id` ,
ADD INDEX `institution_site_id` (`institution_site_id`),
RENAME TO `institution_site_classes` ;

-- institution_site_class_staff
ALTER TABLE `institution_class_staff` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_class_id` `institution_site_class_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_class_id` ,
ADD INDEX `institution_site_class_id` (`institution_site_class_id`),
DROP INDEX `staff_id` ,
ADD INDEX `security_user_id` (`security_user_id`),
RENAME TO  `institution_site_class_staff` ;

-- institution_site_class_students
ALTER TABLE `institution_class_students` 
CHANGE COLUMN `institution_class_id` `institution_site_class_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_section_id` `institution_site_section_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_class_id` ,
ADD INDEX `institution_site_class_id` (`institution_site_class_id`),
DROP INDEX `institution_section_id` ,
ADD INDEX `institution_site_section_id` (`institution_site_section_id`),
DROP INDEX `student_id` ,
ADD INDEX `security_user_id` (`student_id`),
RENAME TO  `institution_site_class_students` ;

-- institution_site_section
ALTER TABLE `institution_sections` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_shift_id` `institution_site_shift_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `staff_id` ,
ADD INDEX `security_user_id` (`security_user_id`),
DROP INDEX `institution_shift_id` ,
ADD INDEX `institution_site_shift_id` (`institution_site_shift_id`),
DROP INDEX `institution_id` ,
ADD INDEX `institution_site_id` (`institution_site_id`),
RENAME TO  `institution_site_sections` ;

-- institution_site_section_students
ALTER TABLE `institution_section_students` 
CHANGE COLUMN `institution_section_id` `institution_site_section_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `student_id` ,
ADD INDEX `security_user_id` (`student_id`),
DROP INDEX `institution_section_id` ,
ADD INDEX `institution_site_id` (`institution_site_section_id`),
RENAME TO  `institution_site_section_students` ;

-- institution_site_section_grades
ALTER TABLE `institution_section_grades` 
CHANGE COLUMN `institution_section_id` `institution_site_section_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_section_id` ,
ADD INDEX `institution_site_section_id` (`institution_site_section_id`),
RENAME TO  `institution_site_section_grades` ;

-- institution_site_section_classes
ALTER TABLE `institution_section_classes` 
CHANGE COLUMN `institution_section_id` `institution_site_section_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_class_id` `institution_site_class_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_section_id` ,
ADD INDEX `institution_site_section_id` (`institution_site_section_id`),
DROP INDEX `institution_class_id` ,
ADD INDEX `institution_site_class_id` (`institution_site_class_id`),
RENAME TO  `institution_site_section_classes` ;

-- institution_site_shifts
ALTER TABLE `institution_shifts` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `location_institution_id` `location_institution_site_id` INT(11) NULL DEFAULT NULL COMMENT '' , 
DROP INDEX `institution_id` ,
ADD INDEX `institution_site_id` (`institution_site_id`),
DROP INDEX `location_institution_id` ,
ADD INDEX `location_institution_site_id` (`location_institution_site_id`),
RENAME TO  `institution_site_shifts` ;

-- institution_grades
ALTER TABLE `institution_grades` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_id` ,
ADD INDEX `institution_site_id` (`institution_site_id`),
RENAME TO  `institution_site_grades` ;

-- institution_infrastructure
ALTER TABLE `institution_infrastructures` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' ,
DROP INDEX `institution_id` ,
ADD INDEX `institution_site_id` (`institution_site_id`);

-- institution_site_bank_accounts
ALTER TABLE `institution_bank_accounts` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_site_bank_accounts` ;

-- institution_site_student_absence
ALTER TABLE `institution_student_absences` 
CHANGE COLUMN `student_id` `security_user_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `student_id` ,
ADD INDEX `security_user_id` (`security_user_id`),
RENAME TO  `institution_site_student_absences` ;

-- institution_site_assessments
ALTER TABLE `institution_assessments` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_id` ,
ADD INDEX `institution_site_id` (`institution_site_id`),
RENAME TO  `institution_site_assessments` ;

-- institution_site_quality_visit
ALTER TABLE `institution_quality_visits` 
CHANGE COLUMN `institution_section_id` `institution_site_section_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_class_id` `institution_site_class_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `staff_id` ,
ADD INDEX `security_user_id` (`security_user_id`),
DROP INDEX `institution_section_id` ,
ADD INDEX `institution_site_section_id` (`institution_site_section_id`),
DROP INDEX `institution_class_id` ,
ADD INDEX `institution_site_class_id` (`institution_site_class_id`),
DROP INDEX `institution_id` ,
ADD INDEX `institution_site_id` (`institution_site_id`),
RENAME TO  `institution_site_quality_visits` ;

-- institution_site_quality_rubric_answers
ALTER TABLE `institution_quality_rubric_answers` 
CHANGE COLUMN `institution_quality_rubric_id` `institution_site_quality_rubric_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_quality_rubric_id` ,
ADD INDEX `institution_site_quality_rubric_id` (`institution_site_quality_rubric_id`),
RENAME TO  `institution_site_quality_rubric_answers` ;

-- institution_site_quality_rubrics
ALTER TABLE `institution_quality_rubrics` 
CHANGE COLUMN `institution_section_id` `institution_site_section_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_class_id` `institution_site_class_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_section_id` ,
ADD INDEX `institution_site_section_id` (`institution_site_section_id`),
DROP INDEX `institution_class_id` ,
ADD INDEX `institution_site_class_id` (`institution_site_class_id`),
DROP INDEX `staff_id` ,
ADD INDEX `security_user_id` (`security_user_id`), 
DROP INDEX `institution_id` ,
ADD INDEX `institution_site_id` (`institution_site_id`),
RENAME TO  `institution_site_quality_rubrics` ;

-- institution_site_survey_answers
ALTER TABLE `institution_survey_answers` 
CHANGE COLUMN `institution_survey_id` `institution_site_survey_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_survey_id` ,
ADD INDEX `institution_site_survey_id` (`institution_site_survey_id`),
RENAME TO  `institution_site_survey_answers` ;

-- institution_site_survey
ALTER TABLE `institution_surveys` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_id` ,
ADD INDEX `institution_site_id` (`institution_site_id`),
RENAME TO  `institution_site_surveys` ;

-- institution_site_survey_table_cells
ALTER TABLE `institution_survey_table_cells` 
CHANGE COLUMN `institution_survey_id` `institution_site_survey_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_site_survey_table_cells` ;

-- institution_site_staff_absences
ALTER TABLE `institution_staff_absences` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `staff_id` ,
ADD INDEX `security_user_id` (`security_user_id`),
DROP INDEX `institution_id` ,
ADD INDEX `institution_site_id` (`institution_site_id`),
RENAME TO  `institution_site_staff_absences` ;

-- student_fees
ALTER TABLE `student_fees` 
DROP INDEX `institution_fee_id` ,
ADD INDEX `institution_site_fee_id` (`institution_fee_id`),
DROP INDEX `student_id` ,
ADD INDEX `security_user_id` (`student_id`);

-- student_guardians
ALTER TABLE `student_guardians` 
DROP INDEX `student_id` ,
ADD INDEX `security_user_id` (`student_id`);

-- staff_activities
ALTER TABLE `staff_activities` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '',
DROP INDEX `staff_id` ,
ADD INDEX `security_user_id` (`security_user_id`);

-- staff_custom_field_values
ALTER TABLE `staff_custom_field_values` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '',
DROP INDEX `staff_id` ,
ADD INDEX `security_user_id` (`security_user_id`);

-- staff_leaves
ALTER TABLE `staff_leaves` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '',
DROP INDEX `staff_id` ,
ADD INDEX `security_user_id` (`security_user_id`);

-- staff_licenses
ALTER TABLE `staff_licenses` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '',
DROP INDEX `staff_id` ,
ADD INDEX `security_user_id` (`security_user_id`);

-- staff_memberships
ALTER TABLE `staff_memberships` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '',
DROP INDEX `staff_id` ,
ADD INDEX `security_user_id` (`security_user_id`);

-- staff_qualifications
ALTER TABLE `staff_qualifications` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '',
DROP INDEX `staff_id` ,
ADD INDEX `security_user_id` (`security_user_id`);

-- staff_employments
ALTER TABLE `staff_employments` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '' ,
DROP INDEX `staff_id` ,
ADD INDEX `security_user_id` (`security_user_id`);

-- staff_extracurriculars
ALTER TABLE `staff_extracurriculars` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '',
DROP INDEX `staff_id` ,
ADD INDEX `security_user_id` (`security_user_id`);

-- staff_salaries
ALTER TABLE `staff_salaries` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '',
DROP INDEX `staff_id` ,
ADD INDEX `security_user_id` (`security_user_id`);

-- staff_custom_table_cells
ALTER TABLE `staff_custom_table_cells` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '' ;

-- student_custom_field_values
ALTER TABLE `student_custom_field_values` 
CHANGE COLUMN `student_id` `security_user_id` INT(11) NOT NULL COMMENT '',
DROP INDEX `student_id` ,
ADD INDEX `security_user_id` (`security_user_id`);

-- student_custom_table_cells
ALTER TABLE `student_custom_table_cells` 
CHANGE COLUMN `student_id` `security_user_id` INT(11) NOT NULL COMMENT '' ;

-- guardian_activities
ALTER TABLE `guardian_activities` 
CHANGE COLUMN `guardian_id` `security_user_id` INT(11) NOT NULL COMMENT '' ,
DROP INDEX `guardian_id` ,
ADD INDEX `security_user_id` (`security_user_id`);

-- assessment_item_results
ALTER TABLE `assessment_item_results` 
DROP INDEX `student_id` ,
ADD INDEX `security_user_id` (`student_id`),
DROP INDEX `institution_id` ,
ADD INDEX `institution_site_id` (`institution_id`);

-- institution_fees
ALTER TABLE `institution_fees` 
DROP INDEX `institution_id` ,
ADD INDEX `institution_site_id` (`institution_id`);

-- labels
UPDATE `labels`
INNER JOIN `z_1463_labels` ON `labels`.`id` = `z_1463_labels`.`id`
SET `labels`.`module` = `z_1463_labels`.`module`, `labels`.`field` = `z_1463_labels`.`field`;

DROP TABLE `z_1463_labels`;

-- import_mapping
UPDATE `import_mapping` 
INNER JOIN `z_1463_import_mapping` ON `import_mapping`.`id` = `z_1463_import_mapping`.id
SET `import_mapping`.`model` = `z_1463_import_mapping`.`model`, `import_mapping`.`column_name` = `z_1463_import_mapping`.`column_name`;

DROP TABLE `z_1463_import_mapping`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1463';