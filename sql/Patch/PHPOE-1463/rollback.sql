-- institution_sites
ALTER TABLE `institutions` 
CHANGE COLUMN `institution_locality_id` `institution_site_locality_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_type_id` `institution_site_type_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_ownership_id` `institution_site_ownership_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_status_id` `institution_site_status_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_sector_id` `institution_site_sector_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_provider_id` `institution_site_provider_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_gender_id` `institution_site_gender_id` INT(5) NOT NULL COMMENT '' ,
ADD COLUMN `institution_site_area_id` INT(11) NULL COMMENT '' AFTER `latitude`, 
RENAME TO `institution_sites` ;

-- security_group_institution_sites
ALTER TABLE `security_group_institutions` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `security_group_institution_sites` ;

-- institution_custom_field_values
ALTER TABLE `institution_custom_field_values` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '',
DROP INDEX `institution_id` ,
ADD INDEX `institution_site_id` (`institution_site_id`);

-- institution_custom_table_cells
ALTER TABLE `institution_custom_table_cells` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' ;

-- institution_site_activities
ALTER TABLE `institution_activities` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' ,
DROP INDEX `institution_id` ,
ADD INDEX `institution_site_id` (`institution_site_id`),
RENAME TO  `institution_site_activities` ;

-- institution_attachments
ALTER TABLE `institution_attachments` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_site_attachments` ;

-- institution_positions
ALTER TABLE `institution_positions` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , RENAME TO  `institution_site_positions` ;

-- institution_site_staff
ALTER TABLE `institution_staff` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_position_id` `institution_site_position_id` INT(11) NOT NULL COMMENT '' , 
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
RENAME TO  `institution_site_class_staff` ;

-- institution_site_class_student
ALTER TABLE `institution_class_students` 
CHANGE COLUMN `institution_class_id` `institution_site_class_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_section_id` `institution_site_section_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_site_class_students` ;

-- institution_site_section
ALTER TABLE `institution_sections` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_shift_id` `institution_site_shift_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_id` ,
ADD INDEX `institution_site_id` (`institution_site_id`),
RENAME TO  `institution_site_sections` ;

-- institution_site_section_students
ALTER TABLE `institution_section_students` 
CHANGE COLUMN `institution_section_id` `institution_site_section_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_section_id` ,
ADD INDEX `institution_site_id` (`institution_site_section_id`),
RENAME TO  `institution_site_section_students` ;

-- institution_site_section_grades
ALTER TABLE `institution_section_grades` 
CHANGE COLUMN `institution_section_id` `institution_site_section_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_site_section_grades` ;

-- institution_site_section_classes
ALTER TABLE `institution_section_classes` 
CHANGE COLUMN `institution_section_id` `institution_site_section_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_class_id` `institution_site_class_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_site_section_classes` ;

-- institution_site_shifts
ALTER TABLE `institution_shifts` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `location_institution_id` `location_institution_site_id` INT(11) NULL DEFAULT NULL COMMENT '' , 
DROP INDEX `institution_id` ,
ADD INDEX `institution_site_id` (`institution_site_id`),
DROP INDEX `location_institution_id` ,
ADD INDEX `location_site_institution_id` (`location_site_institution_id`),
RENAME TO  `institution_site_shifts` ;

-- institution_grades
ALTER TABLE `institution_grades` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_id` ,
ADD INDEX `institution_site_id` (`institution__site_id`),
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
DROP INDEX `institution_id` ,
ADD INDEX `institution_site_id` (`institution_site_id`),
RENAME TO  `institution_site_quality_visits` ;

-- institution_site_quality_rubric_answers
ALTER TABLE `institution_quality_rubric_answers` 
CHANGE COLUMN `institution_quality_rubric_id` `institution_site_quality_rubric_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_site_quality_rubric_answers` ;

-- institution_site_quality_rubrics
ALTER TABLE `institution_quality_rubrics` 
CHANGE COLUMN `institution_section_id` `institution_site_section_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_class_id` `institution_site_class_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_id` ,
ADD INDEX `institution_site_id` (`institution_site_id`),
RENAME TO  `institution_site_quality_rubrics` ;

-- institution_site_survey_answers
ALTER TABLE `institution_survey_answers` 
CHANGE COLUMN `institution_survey_id` `institution_site_survey_id` INT(11) NOT NULL COMMENT '' , 
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
DROP INDEX `institution_id` ,
ADD INDEX `institution_site_id` (`institution_site_id`),
RENAME TO  `institution_site_staff_absences` ;

-- staff_activities
ALTER TABLE `staff_activities` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '' ;

-- staff_custom_field_values
ALTER TABLE `staff_custom_field_values` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '' ;

-- staff_leaves
ALTER TABLE `staff_leaves` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '' ;

-- staff_licenses
ALTER TABLE `staff_licenses` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '' ;

-- staff_memberships
ALTER TABLE `staff_memberships` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '' ;

-- staff_qualifications
ALTER TABLE `staff_qualifications` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '' ;

-- custom_modules
UPDATE `custom_modules` SET `filter`='FieldOption.InstitutionSiteTypes' WHERE `model`='Institution.Institutions';

-- field_options
UPDATE `field_options` SET `plugin`='Institution', `code`='Types' WHERE `plugin`='FieldOption' AND `code`='InstitutionTypes';

-- staff_employments
ALTER TABLE `staff_employments` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '' ;

-- staff_extracurriculars
ALTER TABLE `staff_extracurriculars` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '' ;

-- staff_salaries
ALTER TABLE `staff_salaries` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '' ;

-- staff_custom_table_cells
ALTER TABLE `staff_custom_table_cells` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '' ;

-- student_custom_field_values
ALTER TABLE `student_custom_field_values` 
CHANGE COLUMN `student_id` `security_user_id` INT(11) NOT NULL COMMENT '' ;

-- student_custom_table_cells
ALTER TABLE `student_custom_table_cells` 
CHANGE COLUMN `student_id` `security_user_id` INT(11) NOT NULL COMMENT '' ;

-- institution_attachments
ALTER TABLE `z_1463_institution_attachments`
RENAME TO `institution_attachments`;

-- student_attendance
ALTER TABLE `z_1463_student_attendances`
RENAME TO `student_attendances`;

-- student_attendance_types
ALTER TABLE `z_1463_student_attendance_types`
RENAME TO `student_attendance_types`;

-- institution_site_students
ALTER TABLE `z_1463_institution_site_students` 
RENAME TO  `institution_site_students` ;

-- institution_site_student_absence_attachments
ALTER TABLE `z_1463_institution_site_student_absence_attachments`
RENAME TO  `institution_site_student_absence_attachments`;

-- institution_site_staff_absence_attachments
ALTER TABLE `z_1463_institution_site_staff_absence_attachments`
RENAME TO  `institution_site_staff_absence_attachments`;

-- institution_site_quality_visit_attachments
ALTER TABLE `z_1463_institution_site_quality_visit_attachments`
RENAME TO  `institution_site_quality_visit_attachments`;

-- staff
ALTER TABLE  `z_1463_staff`
RENAME TO   `staff`;

-- staff_attendances
ALTER TABLE `z_1463_staff_attendances`
RENAME TO  `staff_attendances`;

-- staff_attendance_types
ALTER TABLE `z_1463_staff_attendance_types`
RENAME TO  `staff_attendance_types`;

-- staff_categories
ALTER TABLE `z_1463_staff_categories`
RENAME TO `staff_categories`;

-- staff_leave_types
ALTER TABLE `z_1463_staff_leave_types`
RENAME TO `staff_leave_types` ;

-- students
ALTER TABLE `z_1463_students`
RENAME TO `students`;

-- guardians
ALTER TABLE  `z_1463_guardians`
RENAME TO `guardians` ;

-- census_behaviours
ALTER TABLE `z_1463_census_behaviours`
RENAME TO  `census_behaviours`;

-- census_buildings
ALTER TABLE `z_1463_census_buildings`
RENAME TO  `census_buildings`;

-- census_classes
ALTER TABLE `z_1463_census_classes`
RENAME TO  `census_classes`;

-- census_class_grades
ALTER TABLE `z_1463_census_class_grades`
RENAME TO  `census_class_grades`;

-- census_custom_field_options
ALTER TABLE `z_1463_census_custom_field_options`
RENAME TO  `census_custom_field_options`;

-- census_custom_fields
ALTER TABLE `z_1463_census_custom_fields`
RENAME TO  `census_custom_fields` ;

-- census_custom_values
ALTER TABLE `z_1463_census_custom_values`
RENAME TO  `census_custom_values` ;

-- census_energy
ALTER TABLE `z_1463_census_energy`
RENAME TO  `census_energy` ;

-- census_finances
ALTER TABLE `z_1463_census_finances`
RENAME TO  `census_finances` ;

-- census_furniture
ALTER TABLE `z_1463_census_furniture`
RENAME TO  `census_furniture` ;

-- census_graduates
ALTER TABLE `z_1463_census_graduates`
RENAME TO `census_graduates`;

-- census_grids
ALTER TABLE `z_1463_census_grids`
RENAME TO  `census_grids`;

-- census_grid_values
ALTER TABLE `z_1463_census_grid_values`
RENAME TO  `census_grid_values`;

-- census_grid_x_categories
ALTER TABLE `z_1463_census_grid_x_categories`
RENAME TO  `census_grid_x_categories`;

-- census_grid_y_categories
ALTER TABLE `z_1463_census_grid_y_categories`
RENAME TO  `census_grid_y_categories` ;

-- census_resources
ALTER TABLE `z_1463_census_resources`
RENAME TO  `census_resources`;

-- census_rooms
ALTER TABLE `z_1463_census_rooms`
RENAME TO  `census_rooms`;

-- census_sanitations
ALTER TABLE `z_1463_census_sanitations`
RENAME TO  `census_sanitations`;

-- census_shifts
ALTER TABLE `z_1463_census_shifts`
RENAME TO  `census_shifts`;

-- census_students
ALTER TABLE `z_1463_census_students`
RENAME TO  `census_students`;

-- census_teacher_fte
ALTER TABLE `z_1463_census_teacher_fte`
RENAME TO  `census_teacher_fte`;

-- census_teacher_grades
ALTER TABLE `z_1463_census_teacher_grades`
RENAME TO  `census_teacher_grades`;

-- census_teachers
ALTER TABLE `z_1463_census_teachers`
RENAME TO  `census_teachers`;

-- census_teacher_training
ALTER TABLE `z_1463_census_teacher_training`
RENAME TO  `census_teacher_training`;

-- census_textbooks
ALTER TABLE `z_1463_census_textbooks`
RENAME TO  `census_textbooks`;

-- census_verifications
ALTER TABLE `z_1463_census_verifications`
RENAME TO  `census_verifications`;

-- census_water
ALTER TABLE `z_1463_census_water`
RENAME TO  `census_water`;

-- batch_report
ALTER TABLE `z_1463_batch_reports` 
RENAME TO  `batch_reports` ;

-- finance_categories
ALTER TABLE `z_1463_finance_categories` 
RENAME TO  `finance_categories`;

-- finance_natures
ALTER TABLE `z_1463_finance_natures` 
RENAME TO  `finance_natures` ;

-- finance_sources
ALTER TABLE  `z_1463_finance_sources`
RENAME TO  `finance_sources` ;

-- finance_types
ALTER TABLE `z_1463_finance_types` 
RENAME TO  `finance_types` ;

-- guardian_education_levels
ALTER TABLE  `z_1463_guardian_education_levels` 
RENAME TO  `guardian_education_levels`;

-- guardian_relations
ALTER TABLE  `z_1463_guardian_relations`
RENAME TO  `guardian_relations`;

-- infrastructure_buildings
ALTER TABLE `z_1463_infrastructure_buildings` 
RENAME TO  `infrastructure_buildings` ;

-- infrastructure_categories
ALTER TABLE `z_1463_infrastructure_categories` 
RENAME TO  `infrastructure_categories` ;

-- infrastructure_energy
ALTER TABLE `z_1463_infrastructure_energy` 
RENAME TO  `infrastructure_energy` ;

-- infrastructure_furniture
ALTER TABLE `z_1463_infrastructure_furniture` 
RENAME TO  `infrastructure_furniture` ;

-- infrastructure_materials
ALTER TABLE `z_1463_infrastructure_materials` 
RENAME TO  `infrastructure_materials` ;

-- infrastructure_resources
ALTER TABLE `z_1463_infrastructure_resources` 
RENAME TO  `infrastructure_resources` ;

-- infrastructure_rooms
ALTER TABLE `z_1463_infrastructure_rooms` 
RENAME TO  `infrastructure_rooms` ;

-- infrastructure_sanitations
ALTER TABLE `z_1463_infrastructure_sanitations` 
RENAME TO  `infrastructure_sanitations` ;

-- infrastructure_statuses
ALTER TABLE `z_1463_infrastructure_statuses` 
RENAME TO  `infrastructure_statuses` ;

-- infrastructure_water
ALTER TABLE `z_1463_infrastructure_water` 
RENAME TO  `infrastructure_water` ;

-- institution_custom_value_history
ALTER TABLE `z_1463_institution_custom_value_history` 
RENAME TO  `institution_custom_value_history` ;

-- institution_providers
ALTER TABLE `z_1463_institution_providers` 
RENAME TO  `institution_providers` ;

-- institution_sectors
ALTER TABLE `z_1463_institution_sectors` 
RENAME TO  `institution_sectors` ;

-- institution_statuses
ALTER TABLE `z_1463_institution_statuses` 
RENAME TO  `institution_statuses` ;

-- leave_statuses
ALTER TABLE `z_1463_leave_statuses` 
RENAME TO  `leave_statuses` ;

-- qualification_level_bak
ALTER TABLE `z_1463_qualification_levels_bak` 
RENAME TO  `qualification_levels_bak` ;

-- security_user_access
ALTER TABLE `z_1463_security_user_access` 
RENAME TO  `security_user_access` ;

-- assessment_item_results
ALTER TABLE `assessment_item_results` 
DROP INDEX `institution_id` ,
ADD INDEX `institution_site_id` (`institution_id`);

-- institution_fees
ALTER TABLE `institution_fees` 
DROP INDEX `institution_id` ,
ADD INDEX `institution_site_id` (`institution_id`);

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1463';