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
DROP INDEX `institution_site_provider_id` ,
ADD INDEX `institution_provider_id` (`institution_provider_id`),
DROP INDEX `institution_site_sector_id` ,
ADD INDEX `institution_sector_id` (`institution_sector_id`),
DROP INDEX `institution_site_gender_id` ,
ADD INDEX `institution_gender_id` (`institution_gender_id`),
RENAME TO  `institutions` ;

-- security_group_institutions
ALTER TABLE `security_group_institution_sites` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `security_group_institutions` ;

-- institution_custom_field_values
ALTER TABLE `institution_custom_field_values` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_site_id` ,
ADD INDEX `institution_id` (`institution_id`);

-- institution_custom_table_cells
ALTER TABLE `institution_custom_table_cells` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' ;

-- institution_activities
ALTER TABLE `institution_site_activities` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' ,
DROP INDEX `institution_site_id` ,
ADD INDEX `institution_id` (`institution_id`), 
RENAME TO  `institution_activities` ;

-- institution_attachments (Extra table in the database)
ALTER TABLE `institution_attachments`
RENAME TO `z_1463_institution_attachments`;

-- institution_attachments
ALTER TABLE `institution_site_attachments` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_attachments` ;

-- institution_positions
ALTER TABLE `institution_site_positions` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_positions` ;

-- institution_staff
ALTER TABLE `institution_site_staff` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT 'links to security_users.id' ,
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_position_id` `institution_position_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_site_position_id` ,
ADD INDEX `institution_position_id` (`institution_position_id`),
DROP INDEX `security_user_id` ,
ADD INDEX `staff_id` (`staff_id`),
DROP INDEX `institution_site_id` ,
ADD INDEX `institution_id` (`institution_id`),
RENAME TO  `institution_staff` ;

-- institution_classes
ALTER TABLE `institution_site_classes` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_site_id` ,
ADD INDEX `institution_id` (`institution_id`),
RENAME TO `institution_classes` ;

-- institution_class_staff
ALTER TABLE `institution_site_class_staff` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT 'links to security_users.id' ,
CHANGE COLUMN `institution_site_class_id` `institution_class_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_site_class_id` ,
ADD INDEX `institution_class_id` (`institution_class_id`)  COMMENT '',
DROP INDEX `security_user_id` ,
ADD INDEX `staff_id` (`staff_id`)  COMMENT '',
RENAME TO  `institution_class_staff` ;

-- institution_class_student
ALTER TABLE `institution_site_class_students` 
CHANGE COLUMN `institution_site_class_id` `institution_class_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_section_id` `institution_section_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_site_class_id` ,
ADD INDEX `institution_class_id` (`institution_class_id`),
DROP INDEX `institution_site_section_id` ,
ADD INDEX `institution_section_id` (`institution_section_id`),
DROP INDEX `security_user_id` ,
ADD INDEX `student_id` (`student_id`),
RENAME TO  `institution_class_students` ;

-- institution_section
ALTER TABLE `institution_site_sections` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT 'links to security_users.id' ,
CHANGE COLUMN `institution_site_shift_id` `institution_shift_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `security_user_id` ,
ADD INDEX `staff_id` (`staff_id`),
DROP INDEX `institution_site_shift_id` ,
ADD INDEX `institution_shift_id` (`institution_shift_id`),
DROP INDEX `institution_site_id` ,
ADD INDEX `institution_id` (`institution_id`),
RENAME TO  `institution_sections` ;

-- institution_section_students
ALTER TABLE `institution_site_section_students` 
CHANGE COLUMN `institution_site_section_id` `institution_section_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `security_user_id` ,
ADD INDEX `student_id` (`student_id`),
DROP INDEX `institution_site_id` ,
ADD INDEX `institution_section_id` (`institution_section_id`),
RENAME TO  `institution_section_students` ;

-- institution_class_grade_students
ALTER TABLE `institution_site_class_grade_students` 
CHANGE COLUMN `institution_site_class_grade_id` `institution_class_grade_id` INT(11) NOT NULL COMMENT '' ,
DROP INDEX `institution_site_class_grade_id` ,
ADD INDEX `institution_class_grade_id` (`institution_class_grade_id`), 
RENAME TO  `institution_class_grade_students` ;

-- institution_class_grades
ALTER TABLE `institution_site_class_grades` 
CHANGE COLUMN `institution_site_class_id` `institution_class_id` INT(11) NOT NULL COMMENT '' ,
DROP INDEX `institution_site_class_id` ,
ADD INDEX `institution_class_id` (`institution_class_id`), 
RENAME TO  `institution_class_grades` ;

-- institution_section_grades
ALTER TABLE `institution_site_section_grades` 
CHANGE COLUMN `institution_site_section_id` `institution_section_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_site_section_id` ,
ADD INDEX `institution_section_id` (`institution_section_id`),
RENAME TO  `institution_section_grades` ;

-- institution_section_classes
ALTER TABLE `institution_site_section_classes` 
CHANGE COLUMN `institution_site_section_id` `institution_section_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_class_id` `institution_class_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_site_section_id` ,
ADD INDEX `institution_section_id` (`institution_section_id`),
DROP INDEX `institution_site_class_id` ,
ADD INDEX `institution_class_id` (`institution_class_id`),
RENAME TO  `institution_section_classes` ;

-- institution_shifts
ALTER TABLE `institution_site_shifts` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `location_institution_site_id` `location_institution_id` INT(11) NULL DEFAULT NULL COMMENT '' , 
DROP INDEX `institution_site_id` ,
ADD INDEX `institution_id` (`institution_id`),
DROP INDEX `location_institution_site_id` ,
ADD INDEX `location_institution_id` (`location_institution_id`),
RENAME TO  `institution_shifts` ;

-- institution_grades
ALTER TABLE `institution_site_grades` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_site_id` ,
ADD INDEX `institution_id` (`institution_id`),
RENAME TO  `institution_grades` ;

-- institution_infrastructure
ALTER TABLE `institution_infrastructures` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' ,
DROP INDEX `institution_site_id` ,
ADD INDEX `institution_id` (`institution_id`);

-- institution_bank_accounts
ALTER TABLE `institution_site_bank_accounts` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_bank_accounts` ;

-- institution_student_absence
ALTER TABLE `institution_site_student_absences` 
CHANGE COLUMN `security_user_id` `student_id` INT(11) NOT NULL COMMENT 'links to security_users.id' , 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' ,
DROP INDEX `security_user_id` ,
ADD INDEX `student_id` (`student_id`),
RENAME TO  `institution_student_absences` ;

-- institution_assessments
ALTER TABLE `institution_site_assessments` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_site_id` ,
ADD INDEX `institution_id` (`institution_id`),
RENAME TO  `institution_assessments` ;

-- institution_quality_visit
ALTER TABLE `institution_site_quality_visits` 
CHANGE COLUMN `institution_site_section_id` `institution_section_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_class_id` `institution_class_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT 'links to security_users.id' ,
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `security_user_id` ,
ADD INDEX `staff_id` (`staff_id`),
DROP INDEX `institution_site_section_id` ,
ADD INDEX `institution_section_id` (`institution_section_id`),
DROP INDEX `institution_site_class_id` ,
ADD INDEX `institution_class_id` (`institution_class_id`),
DROP INDEX `institution_site_id` ,
ADD INDEX `institution_id` (`institution_id`),
RENAME TO  `institution_quality_visits` ;

-- institution_quality_rubric_answers
ALTER TABLE `institution_site_quality_rubric_answers` 
CHANGE COLUMN `institution_site_quality_rubric_id` `institution_quality_rubric_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_site_quality_rubric_id` ,
ADD INDEX `institution_quality_rubric_id` (`institution_quality_rubric_id`),
RENAME TO  `institution_quality_rubric_answers` ;

-- institution_quality_rubrics
ALTER TABLE `institution_site_quality_rubrics` 
CHANGE COLUMN `institution_site_section_id` `institution_section_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_site_class_id` `institution_class_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT 'links to security_users.id' ,
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' ,
DROP INDEX `institution_site_section_id` ,
ADD INDEX `institution_section_id` (`institution_section_id`),
DROP INDEX `institution_site_class_id` ,
ADD INDEX `institution_class_id` (`institution_class_id`),
DROP INDEX `security_user_id` ,
ADD INDEX `staff_id` (`staff_id`), 
DROP INDEX `institution_site_id` ,
ADD INDEX `institution_id` (`institution_id`),
RENAME TO  `institution_quality_rubrics` ;

-- institution_survey_answers
ALTER TABLE `institution_site_survey_answers` 
CHANGE COLUMN `institution_site_survey_id` `institution_survey_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_site_survey_id` ,
ADD INDEX `institution_survey_id` (`institution_survey_id`),
RENAME TO  `institution_survey_answers` ;

-- institution_survey
ALTER TABLE `institution_site_surveys` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_site_id` ,
ADD INDEX `institution_id` (`institution_id`),
RENAME TO  `institution_surveys` ;

-- institution_survey_table_cells
ALTER TABLE `institution_site_survey_table_cells` 
CHANGE COLUMN `institution_site_survey_id` `institution_survey_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_survey_table_cells` ;

-- institution_staff_absences
ALTER TABLE `institution_site_staff_absences` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT 'links to security_users.id' ,
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `security_user_id` ,
ADD INDEX `staff_id` (`staff_id`),
DROP INDEX `institution_site_id` ,
ADD INDEX `institution_id` (`institution_id`),
RENAME TO  `institution_staff_absences` ;

-- student_fees
ALTER TABLE `student_fees` 
DROP INDEX `institution_site_fee_id` ,
ADD INDEX `institution_fee_id` (`institution_fee_id`),
DROP INDEX `security_user_id` ,
ADD INDEX `student_id` (`student_id`);

-- student_guardians
ALTER TABLE `student_guardians` 
DROP INDEX `security_user_id` ,
ADD INDEX `student_id` (`student_id`);

-- staff_activities
ALTER TABLE `staff_activities` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT 'links to security_users.id',
DROP INDEX `security_user_id` ,
ADD INDEX `staff_id` (`staff_id`);

-- staff_custom_field_values
ALTER TABLE `staff_custom_field_values` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT 'links to security_users.id',
DROP INDEX `security_user_id` ,
ADD INDEX `staff_id` (`staff_id`);

-- staff_leaves
ALTER TABLE `staff_leaves` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT 'links to security_users.id',
DROP INDEX `security_user_id` ,
ADD INDEX `staff_id` (`staff_id`);

-- staff_licenses
ALTER TABLE `staff_licenses` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT 'links to security_users.id',
DROP INDEX `security_user_id` ,
ADD INDEX `staff_id` (`staff_id`);

-- staff_memberships
ALTER TABLE `staff_memberships` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT 'links to security_users.id',
DROP INDEX `security_user_id` ,
ADD INDEX `staff_id` (`staff_id`);

-- staff_qualifications
ALTER TABLE `staff_qualifications` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT 'links to security_users.id',
DROP INDEX `security_user_id` ,
ADD INDEX `staff_id` (`staff_id`);

-- staff_employments
ALTER TABLE `staff_employments` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT 'links to security_users.id',
DROP INDEX `security_user_id` ,
ADD INDEX `staff_id` (`staff_id`);

-- staff_extracurriculars
ALTER TABLE `staff_extracurriculars` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT 'links to security_users.id',
DROP INDEX `security_user_id` ,
ADD INDEX `staff_id` (`staff_id`);

-- staff_salaries
ALTER TABLE `staff_salaries` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT 'links to security_users.id',
DROP INDEX `security_user_id` ,
ADD INDEX `staff_id` (`staff_id`);

-- staff_custom_table_cells
ALTER TABLE `staff_custom_table_cells` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT 'links to security_users.id' ;

-- student_custom_field_values
ALTER TABLE `student_custom_field_values` 
CHANGE COLUMN `security_user_id` `student_id` INT(11) NOT NULL COMMENT 'links to security_users.id',
DROP INDEX `security_user_id` ,
ADD INDEX `student_id` (`student_id`);

-- student_custom_table_cells
ALTER TABLE `student_custom_table_cells` 
CHANGE COLUMN `security_user_id` `student_id` INT(11) NOT NULL COMMENT 'links to security_users.id' ;

-- student_attendance
ALTER TABLE `student_attendances`
RENAME TO `z_1463_student_attendances`;

-- student_attendance_types
ALTER TABLE `student_attendance_types`
RENAME TO `z_1463_student_attendance_types`;

-- institution_site_students
ALTER TABLE `institution_site_students` 
RENAME TO  `z_1463_institution_site_students` ;

-- institution_site_student_absence_attachments
ALTER TABLE `institution_site_student_absence_attachments`
RENAME TO  `z_1463_institution_site_student_absence_attachments`;

-- institution_site_staff_absence_attachments
ALTER TABLE `institution_site_staff_absence_attachments`
RENAME TO  `z_1463_institution_site_staff_absence_attachments`;

-- institution_site_quality_visit_attachments
ALTER TABLE `institution_site_quality_visit_attachments`
RENAME TO  `z_1463_institution_site_quality_visit_attachments`;

-- staff
ALTER TABLE `staff` 
RENAME TO  `z_1463_staff` ;

-- staff_attendances
ALTER TABLE `staff_attendances`
RENAME TO  `z_1463_staff_attendances` ;

-- staff_attendance_types
ALTER TABLE `staff_attendance_types`
RENAME TO  `z_1463_staff_attendance_types` ;

-- staff_categories
ALTER TABLE `staff_categories`
RENAME TO  `z_1463_staff_categories` ;

-- staff_leave_types
ALTER TABLE `staff_leave_types`
RENAME TO  `z_1463_staff_leave_types` ;

-- students
ALTER TABLE `students` 
RENAME TO  `z_1463_students` ;

-- guardians
ALTER TABLE `guardians` 
RENAME TO  `z_1463_guardians` ;

-- guardian_activities
ALTER TABLE `guardian_activities` 
CHANGE COLUMN `security_user_id` `guardian_id` INT(11) NOT NULL COMMENT '' ,
DROP INDEX `security_user_id` ,
ADD INDEX `guardian_id` (`guardian_id`);

-- census_staff_bak
ALTER TABLE `census_staff_bak` 
RENAME TO  `z_1463_census_staff_bak` ;

-- census_behaviours
ALTER TABLE `census_behaviours`
RENAME TO  `z_1463_census_behaviours` ;

-- census_buildings
ALTER TABLE `census_buildings`
RENAME TO  `z_1463_census_buildings` ;

-- census_classes
ALTER TABLE `census_classes`
RENAME TO  `z_1463_census_classes` ;

-- census_class_grades
ALTER TABLE `census_class_grades`
RENAME TO  `z_1463_census_class_grades` ;

-- census_custom_field_options
ALTER TABLE `census_custom_field_options`
RENAME TO  `z_1463_census_custom_field_options` ;

-- census_custom_fields
ALTER TABLE `census_custom_fields`
RENAME TO  `z_1463_census_custom_fields` ;

-- census_custom_values
ALTER TABLE `census_custom_values`
RENAME TO  `z_1463_census_custom_values` ;

-- census_energy
ALTER TABLE `census_energy`
RENAME TO  `z_1463_census_energy` ;

-- census_finances
ALTER TABLE `census_finances`
RENAME TO  `z_1463_census_finances` ;

-- census_furniture
ALTER TABLE `census_furniture`
RENAME TO  `z_1463_census_furniture` ;

-- census_graduates
ALTER TABLE `census_graduates`
RENAME TO  `z_1463_census_graduates` ;

-- census_grids
ALTER TABLE `census_grids`
RENAME TO  `z_1463_census_grids` ;

-- census_grid_values
ALTER TABLE `census_grid_values`
RENAME TO  `z_1463_census_grid_values` ;

-- census_grid_x_categories
ALTER TABLE `census_grid_x_categories`
RENAME TO  `z_1463_census_grid_x_categories` ;

-- census_grid_y_categories
ALTER TABLE `census_grid_y_categories`
RENAME TO  `z_1463_census_grid_y_categories` ;

-- census_resources
ALTER TABLE `census_resources`
RENAME TO  `z_1463_census_resources` ;

-- census_rooms
ALTER TABLE `census_rooms`
RENAME TO  `z_1463_census_rooms` ;

-- census_sanitations
ALTER TABLE `census_sanitations`
RENAME TO  `z_1463_census_sanitations` ;

-- census_shifts
ALTER TABLE `census_shifts`
RENAME TO  `z_1463_census_shifts` ;

-- census_students
ALTER TABLE `census_students`
RENAME TO  `z_1463_census_students` ;

-- census_teacher_fte
ALTER TABLE `census_teacher_fte`
RENAME TO  `z_1463_census_teacher_fte` ;

-- census_teacher_grades
ALTER TABLE `census_teacher_grades`
RENAME TO  `z_1463_census_teacher_grades` ;

-- census_teachers
ALTER TABLE `census_teachers`
RENAME TO  `z_1463_census_teachers` ;

-- census_teacher_training
ALTER TABLE `census_teacher_training`
RENAME TO  `z_1463_census_teacher_training` ;

-- census_textbooks
ALTER TABLE `census_textbooks`
RENAME TO  `z_1463_census_textbooks` ;

-- census_verifications
ALTER TABLE `census_verifications`
RENAME TO  `z_1463_census_verifications` ;

-- census_water
ALTER TABLE `census_water`
RENAME TO  `z_1463_census_water` ;

-- batch_report
ALTER TABLE `batch_reports` 
RENAME TO  `z_1463_batch_reports` ;

-- finance_categories
ALTER TABLE `finance_categories` 
RENAME TO  `z_1463_finance_categories` ;

-- finance_natures
ALTER TABLE `finance_natures` 
RENAME TO  `z_1463_finance_natures` ;

-- finance_sources
ALTER TABLE `finance_sources` 
RENAME TO  `z_1463_finance_sources` ;

-- finance_types
ALTER TABLE `finance_types` 
RENAME TO  `z_1463_finance_types` ;

-- guardian_education_levels
ALTER TABLE `guardian_education_levels` 
RENAME TO  `z_1463_guardian_education_levels` ;

-- guardian_relations
ALTER TABLE `guardian_relations` 
RENAME TO  `z_1463_guardian_relations` ;

-- infrastructure_buildings
ALTER TABLE `infrastructure_buildings` 
RENAME TO  `z_1463_infrastructure_buildings` ;

-- infrastructure_categories
ALTER TABLE `infrastructure_categories` 
RENAME TO  `z_1463_infrastructure_categories` ;

-- infrastructure_energy
ALTER TABLE `infrastructure_energy` 
RENAME TO  `z_1463_infrastructure_energy` ;

-- infrastructure_furniture
ALTER TABLE `infrastructure_furniture` 
RENAME TO  `z_1463_infrastructure_furniture` ;

-- infrastructure_materials
ALTER TABLE `infrastructure_materials` 
RENAME TO  `z_1463_infrastructure_materials` ;

-- infrastructure_resources
ALTER TABLE `infrastructure_resources` 
RENAME TO  `z_1463_infrastructure_resources` ;

-- infrastructure_rooms
ALTER TABLE `infrastructure_rooms` 
RENAME TO  `z_1463_infrastructure_rooms` ;

-- infrastructure_sanitations
ALTER TABLE `infrastructure_sanitations` 
RENAME TO  `z_1463_infrastructure_sanitations` ;

-- infrastructure_statuses
ALTER TABLE `infrastructure_statuses` 
RENAME TO  `z_1463_infrastructure_statuses` ;

-- infrastructure_water
ALTER TABLE `infrastructure_water` 
RENAME TO  `z_1463_infrastructure_water` ;

-- institution_providers
ALTER TABLE `institution_providers` 
RENAME TO  `z_1463_institution_providers` ;

-- institution_sectors
ALTER TABLE `institution_sectors` 
RENAME TO  `z_1463_institution_sectors` ;

-- institution_statuses
ALTER TABLE `institution_statuses` 
RENAME TO  `z_1463_institution_statuses` ;

-- leave_statuses
ALTER TABLE `leave_statuses` 
RENAME TO  `z_1463_leave_statuses` ;

-- qualification_level_bak
ALTER TABLE `qualification_levels_bak` 
RENAME TO  `z_1463_qualification_levels_bak` ;

-- security_user_access
ALTER TABLE `security_user_access` 
RENAME TO  `z_1463_security_user_access` ;

-- assessment_item_results
ALTER TABLE `assessment_item_results` 
DROP INDEX `security_user_id` ,
ADD INDEX `student_id` (`student_id`),
DROP INDEX `institution_site_id` ,
ADD INDEX `institution_id` (`institution_id`);

-- institution_fees
ALTER TABLE `institution_fees` 
DROP INDEX `institution_site_id` ,
ADD INDEX `institution_id` (`institution_id`);

-- labels
CREATE TABLE `z_1463_labels` (
  `id` char(36) NOT NULL,
  `module` varchar(100) NOT NULL,
  `field` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `z_1463_labels` (`id`, `module`, `field`) 
SELECT `id`, `module`, `field` 
FROM `labels`
WHERE `field` = 'institution_site_id';

UPDATE `labels` SET `field` = 'institution_id' WHERE `field` = 'institution_site_id';

INSERT IGNORE INTO `z_1463_labels` (`id`, `module`, `field`) 
SELECT `id`, `module`, `field` 
FROM `labels`
WHERE `field` = 'institution_site_section_id';

UPDATE `labels` SET `field` = 'institution_section_id' WHERE `field` = 'institution_site_section_id';

INSERT IGNORE INTO `z_1463_labels` (`id`, `module`, `field`) 
SELECT `id`, `module`, `field` 
FROM `labels`
WHERE `field` = 'institution_site_class_id';

UPDATE `labels` SET `field` = 'institution_class_id' WHERE `field` = 'institution_site_class_id';

INSERT IGNORE INTO `z_1463_labels` (`id`, `module`, `field`) 
SELECT `id`, `module`, `field` 
FROM `labels`
WHERE `field` = 'institution_site_gender_id';

UPDATE `labels` SET `field` = 'institution_gender_id' WHERE `field` = 'institution_site_gender_id';

INSERT IGNORE INTO `z_1463_labels` (`id`, `module`, `field`) 
SELECT `id`, `module`, `field` 
FROM `labels`
WHERE `field` = 'institution_site_locality_id';

UPDATE `labels` SET `field` = 'institution_locality_id' WHERE `field` = 'institution_site_locality_id';

INSERT IGNORE INTO `z_1463_labels` (`id`, `module`, `field`) 
SELECT `id`, `module`, `field` 
FROM `labels`
WHERE `field` = 'institution_site_ownership_id';

UPDATE `labels` SET `field` = 'institution_ownership_id' WHERE `field` = 'institution_site_ownership_id';

INSERT IGNORE INTO `z_1463_labels` (`id`, `module`, `field`) 
SELECT `id`, `module`, `field` 
FROM `labels`
WHERE `field` = 'institution_site_provider_id';

UPDATE `labels` SET `field` = 'institution_provider_id' WHERE `field` = 'institution_site_provider_id';

INSERT IGNORE INTO `z_1463_labels` (`id`, `module`, `field`) 
SELECT `id`, `module`, `field` 
FROM `labels`
WHERE `field` = 'institution_site_position_id';

UPDATE `labels` SET `field` = 'institution_position_id' WHERE `field` = 'institution_site_position_id';

INSERT IGNORE INTO `z_1463_labels` (`id`, `module`, `field`) 
SELECT `id`, `module`, `field` 
FROM `labels`
WHERE `field` = 'institution_site_section';

UPDATE `labels` SET `field` = 'institution_section' WHERE `field` = 'institution_site_section';

INSERT IGNORE INTO `z_1463_labels` (`id`, `module`, `field`) 
SELECT `id`, `module`, `field` 
FROM `labels`
WHERE `field` = 'institution_site_shift_id';

UPDATE `labels` SET `field` = 'institution_shift_id' WHERE `field` = 'institution_site_shift_id';

INSERT IGNORE INTO `z_1463_labels` (`id`, `module`, `field`) 
SELECT `id`, `module`, `field` 
FROM `labels`
WHERE `field` = 'location_institution_site_id';

UPDATE `labels` SET `field` = 'location_institution_id' WHERE `field` = 'location_institution_site_id';

INSERT IGNORE INTO `z_1463_labels` (`id`, `module`, `field`) 
SELECT `id`, `module`, `field` 
FROM `labels`
WHERE `field` = 'institution_site_sector_id';

UPDATE `labels` SET `field` = 'institution_sector_id' WHERE `field` = 'institution_site_sector_id';

INSERT IGNORE INTO `z_1463_labels` (`id`, `module`, `field`) 
SELECT `id`, `module`, `field` 
FROM `labels`
WHERE `field` = 'institution_site_status_id';

UPDATE `labels` SET `field` = 'institution_status_id' WHERE `field` = 'institution_site_status_id';

INSERT IGNORE INTO `z_1463_labels` (`id`, `module`, `field`) 
SELECT `id`, `module`, `field` 
FROM `labels`
WHERE `field` = 'institution_site_type_id';

UPDATE `labels` SET `field` = 'institution_type_id' WHERE `field` = 'institution_site_type_id';

INSERT IGNORE INTO `z_1463_labels` (`id`, `module`, `field`) 
SELECT `id`, `module`, `field` 
FROM `labels`
WHERE `module` = 'InstitutionSiteActivities';

UPDATE `labels` SET `module` = 'InstitutionActivities' WHERE `module` = 'InstitutionSiteActivities';

INSERT IGNORE INTO `z_1463_labels` (`id`, `module`, `field`) 
SELECT `id`, `module`, `field` 
FROM `labels`
WHERE `module` = 'InstitutionSiteStaff';

UPDATE `labels` SET `module` = 'InstitutionStaff' WHERE `module` = 'InstitutionSiteStaff';

INSERT IGNORE INTO `z_1463_labels` (`id`, `module`, `field`) 
SELECT `id`, `module`, `field` 
FROM `labels`
WHERE `module` = 'InstitutionSiteAttachments';

UPDATE `labels` SET `module` = 'InstitutionAttachments' WHERE `module` = 'InstitutionSiteAttachments';

INSERT IGNORE INTO `z_1463_labels` (`id`, `module`, `field`) 
SELECT `id`, `module`, `field` 
FROM `labels`
WHERE `module` = 'InstitutionSitePositions';

UPDATE `labels` SET `module` = 'InstitutionPositions' WHERE `module` = 'InstitutionSitePositions';

INSERT IGNORE INTO `z_1463_labels` (`id`, `module`, `field`) 
SELECT `id`, `module`, `field` 
FROM `labels`
WHERE `module` = 'InstitutionSiteClasses';

UPDATE `labels` SET `module` = 'InstitutionClasses' WHERE `module` = 'InstitutionSiteClasses';

INSERT IGNORE INTO `z_1463_labels` (`id`, `module`, `field`) 
SELECT `id`, `module`, `field` 
FROM `labels`
WHERE `module` = 'InstitutionSiteSections';

UPDATE `labels` SET `module` = 'InstitutionSections' WHERE `module` = 'InstitutionSiteSections';

INSERT IGNORE INTO `z_1463_labels` (`id`, `module`, `field`) 
SELECT `id`, `module`, `field` 
FROM `labels`
WHERE `module` = 'InstitutionSiteSections';

UPDATE `labels` SET `module` = 'InstitutionSections' WHERE `module` = 'InstitutionSiteSections';

INSERT IGNORE INTO `z_1463_labels` (`id`, `module`, `field`) 
SELECT `id`, `module`, `field` 
FROM `labels`
WHERE `module` = 'InstitutionSiteStudentAbsences';

UPDATE `labels` SET `module` = 'InstitutionStudentAbsences' WHERE `module` = 'InstitutionSiteStudentAbsences';

INSERT IGNORE INTO `z_1463_labels` (`id`, `module`, `field`) 
SELECT `id`, `module`, `field` 
FROM `labels`
WHERE `module` = 'InstitutionSiteShifts';

UPDATE `labels` SET `module` = 'InstitutionShifts' WHERE `module` = 'InstitutionSiteShifts';

-- import_mapping
CREATE TABLE `z_1463_import_mapping` LIKE `import_mapping`;

INSERT INTO `z_1463_import_mapping` (SELECT * FROM `import_mapping`);

UPDATE `import_mapping` SET `model` = 'InstitutionStudentAbsences' WHERE `model` = 'InstitutionSiteStudentAbsences';
UPDATE `import_mapping` SET `column_name` = 'student_id' WHERE `column_name` = 'security_user_id' AND `model` = 'InstitutionStudentAbsences';
UPDATE `import_mapping` SET `model` = 'InstitutionSurveys' WHERE `model` = 'InstitutionSiteSurveys';
UPDATE `import_mapping` SET `column_name` = 'institution_locality_id' WHERE `column_name` = 'institution_site_locality_id' AND `model` = 'Institutions';
UPDATE `import_mapping` SET `column_name` = 'institution_type_id' WHERE `column_name` = 'institution_site_type_id' AND `model` = 'Institutions';
UPDATE `import_mapping` SET `column_name` = 'institution_ownership_id' WHERE `column_name` = 'institution_site_ownership_id' AND `model` = 'Institutions';
UPDATE `import_mapping` SET `column_name` = 'institution_status_id' WHERE `column_name` = 'institution_site_status_id' AND `model` = 'Institutions';
UPDATE `import_mapping` SET `column_name` = 'institution_sector_id' WHERE `column_name` = 'institution_site_sector_id' AND `model` = 'Institutions';
UPDATE `import_mapping` SET `column_name` = 'institution_provider_id' WHERE `column_name` = 'institution_site_provider_id' AND `model` = 'Institutions';
UPDATE `import_mapping` SET `column_name` = 'institution_gender_id' WHERE `column_name` = 'institution_site_gender_id' AND `model` = 'Institutions';
UPDATE `import_mapping` SET `column_name` = 'staff_id' WHERE `column_name` = 'security_user_id' AND `model` = 'StaffAbsences';

