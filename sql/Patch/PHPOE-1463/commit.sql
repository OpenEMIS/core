-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1463', NOW());

-- institutions
ALTER TABLE `institution_sites` 
DROP COLUMN `institution_site_area_id`,
CHANGE COLUMN `institution_site_locality_id` `institution_locality_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_type_id` `institution_type_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_ownership_id` `institution_ownership_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_status_id` `institution_status_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_sector_id` `institution_sector_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_provider_id` `institution_provider_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_gender_id` `institution_gender_id` INT(5) NOT NULL COMMENT '' , 
RENAME TO  `institutions` ;

-- security_group_institutions
ALTER TABLE `security_group_institution_sites` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `security_group_institutions` ;

-- institution_custom_field_values
ALTER TABLE `institution_custom_field_values` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' ;

-- institution_custom_table_cells
ALTER TABLE `institution_custom_table_cells` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' ;

-- institution_activities
ALTER TABLE `institution_site_activities` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_activities` ;

-- institution_attachments
DROP TABLE IF EXISTS `institution_attachments`;

ALTER TABLE `institution_site_attachments` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_attachments` ;

-- institution_positions
ALTER TABLE `institution_site_positions` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_positions` ;

-- institution_staff
ALTER TABLE `institution_site_staff` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_position_id` `institution_position_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_staff` ;

-- institution_classes
ALTER TABLE `institution_site_classes` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO `institution_classes` ;

-- institution_class_staff
ALTER TABLE `institution_site_class_staff` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_class_id` `institution_class_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_class_staff` ;

-- institution_class_student
ALTER TABLE `institution_site_class_students` 
CHANGE COLUMN `institution_site_class_id` `institution_class_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_section_id` `institution_section_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_class_students` ;

-- institution_section
ALTER TABLE `institution_site_sections` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_shift_id` `institution_shift_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_sections` ;

-- institution_section_students
ALTER TABLE `institution_site_section_students` 
CHANGE COLUMN `institution_site_section_id` `institution_section_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_section_students` ;

-- institution_section_grades
ALTER TABLE `institution_site_section_grades` 
CHANGE COLUMN `institution_site_section_id` `institution_section_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_section_grades` ;

-- institution_section_classes
ALTER TABLE `institution_site_section_classes` 
CHANGE COLUMN `institution_site_section_id` `institution_section_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_class_id` `institution_class_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_section_classes` ;

-- institution_shifts
ALTER TABLE `institution_site_shifts` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `location_institution_site_id` `location_institution_id` INT(11) NULL DEFAULT NULL COMMENT '' , 
RENAME TO  `institution_shifts` ;

-- institution_grades
ALTER TABLE `institution_site_grades` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_grades` ;

-- institution_infrastructure
ALTER TABLE `institution_infrastructures` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' ;

-- institution_bank_accounts
ALTER TABLE `institution_site_bank_accounts` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_bank_accounts` ;

-- institution_student_absence
ALTER TABLE `institution_site_student_absences` 
CHANGE COLUMN `security_user_id` `student_id` INT(11) NOT NULL COMMENT '' , 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' ,
RENAME TO  `institution_student_absences` ;

-- institution_assessments
ALTER TABLE `institution_site_assessments` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_assessments` ;

-- institution_quality_visit
ALTER TABLE `institution_site_quality_visits` 
CHANGE COLUMN `institution_site_section_id` `institution_section_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_class_id` `institution_class_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_quality_visits` ;

-- institution_quality_rubric_answers
ALTER TABLE `institution_site_quality_rubric_answers` 
CHANGE COLUMN `institution_site_quality_rubric_id` `institution_quality_rubric_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_quality_rubric_answers` ;

-- institution_quality_rubrics
ALTER TABLE `institution_site_quality_rubrics` 
CHANGE COLUMN `institution_site_section_id` `institution_section_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_class_id` `institution_class_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_quality_rubrics` ;

-- institution_survey_answers
ALTER TABLE `institution_site_survey_answers` 
CHANGE COLUMN `institution_site_survey_id` `institution_survey_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_survey_answers` ;

-- institution_survey
ALTER TABLE `institution_site_surveys` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_surveys` ;

-- institution_survey_table_cells
ALTER TABLE `institution_site_survey_table_cells` 
CHANGE COLUMN `institution_site_survey_id` `institution_survey_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_survey_table_cells` ;

-- student_attendance
DROP TABLE IF EXISTS student_attendances;

-- student_attendance_types
DROP TABLE IF EXISTS student_attendance_types;

-- institution_site_students
ALTER TABLE `institution_site_students` 
RENAME TO  `z_1463_institution_site_students` ;

-- institution_staff_absences
ALTER TABLE `institution_site_staff_absences` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_staff_absences` ;

-- institution_site_student_absence_attachments
DROP TABLE IF EXISTS institution_site_student_absence_attachments;

-- institution_site_staff_absence_attachments
DROP TABLE IF EXISTS institution_site_staff_absence_attachments;

-- institution_site_quality_visit_attachments
DROP TABLE IF EXISTS institution_site_quality_visit_attachments;

-- staff
ALTER TABLE `staff` 
RENAME TO  `z_1463_staff` ;

-- staff_activities
ALTER TABLE `staff_activities` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT '' ;

-- staff_attendances
DROP TABLE IF EXISTS `staff_attendances`;

-- staff_attendance_types
DROP TABLE IF EXISTS `staff_attendance_types`;

-- staff_categories
DROP TABLE IF EXISTS `staff_categories`;

-- staff_custom_field_values
ALTER TABLE `staff_custom_field_values` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT '' ;

-- staff_leaves
ALTER TABLE `staff_leaves` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT '' ;

-- staff_leave_types
DROP TABLE IF EXISTS `staff_leave_types`;

-- staff_licenses
ALTER TABLE `staff_licenses` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT '' ;

-- staff_memberships
ALTER TABLE `staff_memberships` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT '' ;

-- staff_qualifications
ALTER TABLE `staff_qualifications` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT '' ;

-- custom_modules
UPDATE `custom_modules` SET `filter`='FieldOption.InstitutionTypes' WHERE `model`='Institution.Institutions';

-- field_options
UPDATE `field_options` SET `plugin`='FieldOption', `code`='InstitutionTypes' WHERE `plugin`='Institution' AND `code`='Types';

-- staff_employments
ALTER TABLE `staff_employments` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT '' ;

-- staff_extracurriculars
ALTER TABLE `staff_extracurriculars` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT '' ;

-- staff_salaries
ALTER TABLE `staff_salaries` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT '' ;

-- staff_custom_table_cells
ALTER TABLE `staff_custom_table_cells` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT '' ;

-- census_behaviours
DROP TABLE IF EXISTS census_behaviours;

-- census_buildings
DROP TABLE IF EXISTS census_buildings;

-- census_classes
DROP TABLE IF EXISTS census_classes;

-- census_class_grades
DROP TABLE IF EXISTS census_class_grades;

-- census_custom_field_options
DROP TABLE IF EXISTS census_custom_field_options;

-- census_custom_fields
DROP TABLE IF EXISTS census_custom_fields;

-- census_custom_values
DROP TABLE IF EXISTS census_custom_values;

-- census_energy
DROP TABLE IF EXISTS census_energy;

-- census_finances
DROP TABLE IF EXISTS census_finances;

-- census_furniture
DROP TABLE IF EXISTS census_furniture;

-- census_graduates
DROP TABLE IF EXISTS census_graduates;

-- census_grids
DROP TABLE IF EXISTS census_grids;

-- census_grid_values
DROP TABLE IF EXISTS census_grid_values;

-- census_grid_x_categories
DROP TABLE IF EXISTS census_grid_x_categories;

-- census_grid_y_categories
DROP TABLE IF EXISTS census_grid_y_categories;

-- census_resources
DROP TABLE IF EXISTS census_resources;

-- census_rooms
DROP TABLE IF EXISTS census_rooms;

-- census_sanitations
DROP TABLE IF EXISTS census_sanitations;

-- census_shifts
DROP TABLE IF EXISTS census_shifts;

-- census_students
DROP TABLE IF EXISTS census_students;

-- census_teacher_fte
DROP TABLE IF EXISTS census_teacher_fte;

-- census_teacher_grades
DROP TABLE IF EXISTS census_teacher_grades;

-- census_teachers
DROP TABLE IF EXISTS census_teachers;

-- census_teacher_training
DROP TABLE IF EXISTS census_teacher_training;

-- census_textbooks
DROP TABLE IF EXISTS census_textbooks;

-- census_verifications
DROP TABLE IF EXISTS census_verifications;

-- census_water
DROP TABLE IF EXISTS census_water;

-- students
ALTER TABLE `students` 
RENAME TO  `z_1463_students` ;

-- guardians
ALTER TABLE `guardians` 
RENAME TO  `z_1463_guardians` ;

-- student_custom_field_values
ALTER TABLE `student_custom_field_values` 
CHANGE COLUMN `security_user_id` `student_id` INT(11) NOT NULL COMMENT '' ;

-- student_custom_table_cells
ALTER TABLE `student_custom_table_cells` 
CHANGE COLUMN `security_user_id` `student_id` INT(11) NOT NULL COMMENT '' ;