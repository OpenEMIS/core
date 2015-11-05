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
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' ;

-- institution_custom_table_cells
ALTER TABLE `institution_custom_table_cells` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' ;

-- institution_site_activities
ALTER TABLE `institution_activities` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_site_activities` ;

-- institution_attachments
ALTER TABLE `institution_attachments` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_site_attachments` ;

CREATE TABLE `institution_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) NOT NULL,
  `description` varchar(250) NOT NULL,
  `file_name` varchar(250) NOT NULL,
  `file_content` longblob NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `institution_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- institution_positions
ALTER TABLE `institution_positions` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , RENAME TO  `institution_site_positions` ;

-- institution_site_staff
ALTER TABLE `institution_staff` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_position_id` `institution_site_position_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_site_staff` ;

-- institution_site_classes
ALTER TABLE `institution_classes` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO `institution_site_classes` ;

-- institution_site_class_staff
ALTER TABLE `institution_class_staff` 
CHANGE COLUMN `institution_class_id` `institution_site_class_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_site_class_staff` ;

-- institution_site_class_student
ALTER TABLE `institution_class_students` 
CHANGE COLUMN `institution_class_id` `institution_site_class_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_section_id` `institution_site_section_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_site_class_students` ;

-- institution_site_section
ALTER TABLE `institution_sections` 
CHANGE COLUMN `institution_shift_id` `institution_site_shift_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_site_sections` ;

-- institution_site_section_students
ALTER TABLE `institution_section_students` 
CHANGE COLUMN `institution_section_id` `institution_site_section_id` INT(11) NOT NULL COMMENT '' , 
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
RENAME TO  `institution_site_shifts` ;

-- institution_grades
ALTER TABLE `institution_grades` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_site_grades` ;

-- institution_infrastructure
ALTER TABLE `institution_infrastructures` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' ;

-- institution_site_bank_accounts
ALTER TABLE `institution_bank_accounts` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_site_bank_accounts` ;

-- institution_site_student_absence
ALTER TABLE `institution_student_absences` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_site_student_absences` ;

-- institution_site_assessments
ALTER TABLE `institution_assessments` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_site_assessments` ;

-- institution_site_quality_visit
ALTER TABLE `institution_quality_visits` 
CHANGE COLUMN `institution_section_id` `institution_site_section_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_class_id` `institution_site_class_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_site_quality_visits` ;

-- institution_site_quality_rubric_answers
ALTER TABLE `institution_quality_rubric_answers` 
CHANGE COLUMN `institution_quality_rubric_id` `institution_site_quality_rubric_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_site_quality_rubric_answers` ;

-- institution_site_quality_rubrics
ALTER TABLE `institution_quality_rubrics` 
CHANGE COLUMN `institution_section_id` `institution_site_section_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_class_id` `institution_site_class_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_site_quality_rubrics` ;

-- institution_site_survey_answers
ALTER TABLE `institution_survey_answers` 
CHANGE COLUMN `institution_survey_id` `institution_site_survey_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_site_survey_answers` ;

-- institution_site_survey
ALTER TABLE `institution_surveys` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_site_surveys` ;

-- institution_site_survey_table_cells
ALTER TABLE `institution_survey_table_cells` 
CHANGE COLUMN `institution_survey_id` `institution_site_survey_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_site_survey_table_cells` ;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1463';