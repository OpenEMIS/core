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
ADD INDEX `institution_locality_id` (`institution_locality_id`),
ADD INDEX `institution_type_id` (`institution_type_id`),
ADD INDEX `institution_ownership_id` (`institution_ownership_id`),
ADD INDEX `institution_status_id` (`institution_status_id`),
DROP INDEX `institution_site_sector_id` ,
ADD INDEX `institution_sector_id` (`institution_sector_id`),
DROP INDEX `institution_site_provider_id` ,
ADD INDEX `institution_provider_id` (`institution_provider_id`),
DROP INDEX `institution_site_gender_id` ,
ADD INDEX `institution_gender_id` (`institution_gender_id`),
RENAME TO  `institutions` ;

-- security_group_institutions
ALTER TABLE `security_group_institution_sites` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
ADD INDEX `institution_id` (`institution_id`),
RENAME TO  `security_group_institutions` ;

-- institution_custom_field_values
ALTER TABLE `institution_custom_field_values` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
DROP INDEX `institution_site_id` ,
ADD INDEX `institution_id` (`institution_id`);

-- institution_custom_table_cells
ALTER TABLE `institution_custom_table_cells` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' ,
ADD INDEX `institution_id` (`institution_id`);

-- institution_activities
ALTER TABLE `institution_site_activities` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' ,
DROP INDEX `institution_site_id` ,
ADD INDEX `institution_id` (`institution_id`), 
RENAME TO  `institution_activities` ;

-- institution_attachments (Extra table in the database)
DROP TABLE IF EXISTS `institution_attachments`;

-- institution_history not in used anymore
DROP TABLE IF EXISTS `institution_history`;

-- institution_attachments
ALTER TABLE `institution_site_attachments` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
ADD INDEX `institution_id` (`institution_id`), 
RENAME TO  `institution_attachments` ;

-- institution_positions
ALTER TABLE `institution_site_positions` 
CHANGE COLUMN `institution_site_id` `institution_id` INT(11) NOT NULL COMMENT '' , 
ADD INDEX `institution_id` (`institution_id`), 
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
ADD INDEX `institution_class_id` (`institution_class_id`),
DROP INDEX `security_user_id` ,
ADD INDEX `staff_id` (`staff_id`),
RENAME TO  `institution_class_staff` ;

-- institution_class_students
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

-- institution_class_grade_students not in used anymore
DROP TABLE IF EXISTS `institution_site_class_grade_students`;

-- institution_class_grades not in used anymore
DROP TABLE IF EXISTS `institution_site_class_grades`;

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
DROP TABLE IF EXISTS `student_attendances`;

-- student_attendance_types
DROP TABLE IF EXISTS `student_attendance_types`;

-- institution_site_students
DROP TABLE IF EXISTS `institution_site_students`;

-- institution_site_student_absence_attachments
DROP TABLE IF EXISTS `institution_site_student_absence_attachments`;

-- institution_site_staff_absence_attachments
DROP TABLE IF EXISTS `institution_site_staff_absence_attachments`;

-- institution_site_quality_visit_attachments
DROP TABLE IF EXISTS `institution_site_quality_visit_attachments`;

-- staff
DROP TABLE IF EXISTS `staff`;

-- staff_attendances
DROP TABLE IF EXISTS `staff_attendances`;

-- staff_attendance_types
DROP TABLE IF EXISTS `staff_attendance_types`;

-- staff_categories
DROP TABLE IF EXISTS `staff_categories`;

-- staff_leave_types
DROP TABLE IF EXISTS `staff_leave_types`;

-- students
DROP TABLE IF EXISTS `students`;

-- guardians
DROP TABLE IF EXISTS `guardians`;

-- guardian_activities
ALTER TABLE `guardian_activities` 
CHANGE COLUMN `security_user_id` `guardian_id` INT(11) NOT NULL COMMENT '' ,
DROP INDEX `security_user_id` ,
ADD INDEX `guardian_id` (`guardian_id`);

-- census_staff_bak
DROP TABLE IF EXISTS `census_staff_bak`;

-- census_behaviours
DROP TABLE IF EXISTS `census_behaviours`;

-- census_buildings
DROP TABLE IF EXISTS `census_buildings`;

-- census_classes
DROP TABLE IF EXISTS `census_classes`;

-- census_class_grades
DROP TABLE IF EXISTS `census_class_grades`;

-- census_custom_field_options
DROP TABLE IF EXISTS `census_custom_field_options`;

-- census_custom_fields
DROP TABLE IF EXISTS `census_custom_fields`;

-- census_custom_values
DROP TABLE IF EXISTS `census_custom_values`;

-- census_energy
DROP TABLE IF EXISTS `census_energy`;

-- census_finances
DROP TABLE IF EXISTS `census_finances`;

-- census_furniture
DROP TABLE IF EXISTS `census_furniture`;

-- census_graduates
DROP TABLE IF EXISTS `census_graduates`;

-- census_grids
DROP TABLE IF EXISTS `census_grids`;

-- census_grid_values
DROP TABLE IF EXISTS `census_grid_values`;

-- census_grid_x_categories
DROP TABLE IF EXISTS `census_grid_x_categories`;

-- census_grid_y_categories
DROP TABLE IF EXISTS `census_grid_y_categories`;

-- census_resources
DROP TABLE IF EXISTS `census_resources`;

-- census_rooms
DROP TABLE IF EXISTS `census_rooms`;

-- census_sanitations
DROP TABLE IF EXISTS `census_sanitations`;

-- census_shifts
DROP TABLE IF EXISTS `census_shifts`;

-- census_students
DROP TABLE IF EXISTS `census_students`;

-- census_teacher_fte
DROP TABLE IF EXISTS `census_teacher_fte`;

-- census_teacher_grades
DROP TABLE IF EXISTS `census_teacher_grades`;

-- census_teachers
DROP TABLE IF EXISTS `census_teachers`;
-- census_teacher_training
DROP TABLE IF EXISTS `census_teacher_training`;

-- census_textbooks
DROP TABLE IF EXISTS `census_textbooks`;

-- census_verifications
DROP TABLE IF EXISTS `census_verifications`;

-- census_water
DROP TABLE IF EXISTS `census_water`;

-- batch_report
DROP TABLE IF EXISTS `batch_reports`;

-- reports
DROP TABLE IF EXISTS `reports`;

-- report_templates
DROP TABLE IF EXISTS `report_templates`;

-- finance_categories
DROP TABLE IF EXISTS `finance_categories`;

-- finance_natures
DROP TABLE IF EXISTS `finance_natures`;

-- finance_sources
DROP TABLE IF EXISTS `finance_sources`;

-- finance_types
DROP TABLE IF EXISTS `finance_types`;

-- guardian_education_levels
DROP TABLE IF EXISTS `guardian_education_levels`;

-- guardian_relations
DROP TABLE IF EXISTS `guardian_relations`;

-- infrastructure_buildings
DROP TABLE IF EXISTS `infrastructure_buildings`;

-- infrastructure_categories
DROP TABLE IF EXISTS `infrastructure_categories`;

-- infrastructure_energy
DROP TABLE IF EXISTS `infrastructure_energy`;

-- infrastructure_furniture
DROP TABLE IF EXISTS `infrastructure_furniture`;

-- infrastructure_materials
DROP TABLE IF EXISTS `infrastructure_materials`;

-- infrastructure_resources
DROP TABLE IF EXISTS `infrastructure_resources`;

-- infrastructure_rooms
DROP TABLE IF EXISTS `infrastructure_rooms`;

-- infrastructure_sanitations
DROP TABLE IF EXISTS `infrastructure_sanitations`;

-- infrastructure_statuses
DROP TABLE IF EXISTS `infrastructure_statuses`;

-- infrastructure_water
DROP TABLE IF EXISTS `infrastructure_water`;

-- institution_providers
DROP TABLE IF EXISTS `institution_providers`;

-- institution_sectors
DROP TABLE IF EXISTS `institution_sectors`;

-- institution_statuses
DROP TABLE IF EXISTS `institution_statuses`;

-- leave_statuses
DROP TABLE IF EXISTS `leave_statuses`;

-- qualification_level_bak
DROP TABLE IF EXISTS `qualification_levels_bak`;

-- security_user_access
DROP TABLE IF EXISTS `security_user_access`;

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

DROP TABLE IF EXISTS `staff_import`;
DROP TABLE IF EXISTS `student_import`;
DROP TABLE IF EXISTS `student_results`;
DROP TABLE IF EXISTS `z_1352_institution_site_surveys`;
DROP TABLE IF EXISTS `z_1414_institution_site_fees`;
DROP TABLE IF EXISTS `z_1414_institution_site_fee_types`;
DROP TABLE IF EXISTS `z_1978_staff_training_needs`;
DROP TABLE IF EXISTS `z_1992_training_course_attachments`;
DROP TABLE IF EXISTS `z_1992_training_course_experiences`;
DROP TABLE IF EXISTS `z_1992_training_course_prerequisites`;
DROP TABLE IF EXISTS `z_1992_training_course_providers`;
DROP TABLE IF EXISTS `z_1992_training_course_result_types`;
DROP TABLE IF EXISTS `z_1992_training_courses`;
DROP TABLE IF EXISTS `z_1992_training_course_specialisations`;
DROP TABLE IF EXISTS `z_1992_training_course_target_populations`;
DROP TABLE IF EXISTS `z_1992_training_credit_hours`;
DROP TABLE IF EXISTS `z_1992_training_session_results`;
DROP TABLE IF EXISTS `z_1992_training_sessions`;
DROP TABLE IF EXISTS `z_1992_training_session_trainee_results`;
DROP TABLE IF EXISTS `z_1992_training_session_trainees`;
DROP TABLE IF EXISTS `z_1992_training_session_trainers`;
DROP TABLE IF EXISTS `z2081_import_mapping`;
DROP TABLE IF EXISTS `z2084_import_mapping`;
DROP TABLE IF EXISTS `z2086_import_mapping`;
DROP TABLE IF EXISTS `z_2086_security_functions`;
DROP TABLE IF EXISTS `z_2086_security_role_functions`;
DROP TABLE IF EXISTS `z2086_survey_forms`;
DROP TABLE IF EXISTS `z2086_survey_questions`;
DROP TABLE IF EXISTS `z_2117_institution_site_grades`;
DROP TABLE IF EXISTS `z_2117_institution_site_programmes`;
DROP TABLE IF EXISTS `z_2178_Institution_sites`;
DROP TABLE IF EXISTS `z_2305_security_users`;