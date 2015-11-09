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
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_position_id` `institution_site_position_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_site_staff` ;

-- institution_site_classes
ALTER TABLE `institution_classes` 
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
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
CHANGE COLUMN `security_user_id` `student_id` INT(11) NOT NULL COMMENT '' ,
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
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '' ,
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
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '' ,
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

-- student_attendance
CREATE TABLE `student_attendances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` int(11) NOT NULL,
  `student_attendance_type_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `security_user_id` int(11) NOT NULL,
  `institution_id` int(11) NOT NULL,
  `institution_class_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `school_year_id` (`academic_period_id`),
  KEY `institution_site_id` (`institution_id`),
  KEY `institution_site_class_id` (`institution_class_id`),
  KEY `security_user_id` (`security_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- student_attendance_types
CREATE TABLE `student_attendance_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `international_code` varchar(10) DEFAULT NULL,
  `national_code` varchar(10) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- institution_site_students
ALTER TABLE `z_1463_institution_site_students` 
RENAME TO  `institution_site_students` ;

-- institution_site_staff_absences
ALTER TABLE `institution_staff_absences` 
CHANGE COLUMN `security_user_id` `staff_id` INT(11) NOT NULL COMMENT '' ,
CHANGE COLUMN `institution_id` `institution_site_id` INT(11) NOT NULL COMMENT '' , 
RENAME TO  `institution_site_staff_absences` ;

-- institution_site_student_absence_attachments
CREATE TABLE `institution_site_student_absence_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_name` varchar(250) NOT NULL,
  `file_content` longblob NOT NULL,
  `institution_site_student_absence_id` int(11) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_site_student_attendance_id` (`institution_site_student_absence_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- institution_site_staff_absence_attachments
CREATE TABLE `institution_site_staff_absence_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_name` varchar(250) NOT NULL,
  `file_content` longblob NOT NULL,
  `institution_site_staff_absence_id` int(11) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_site_staff_absence_id` (`institution_site_staff_absence_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- institution_site_quality_visit_attachments
CREATE TABLE `institution_site_quality_visit_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_name` varchar(200) DEFAULT NULL,
  `file_content` longblob,
  `visible` int(1) NOT NULL DEFAULT '1',
  `institution_site_quality_visit_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_site_quality_visit_id` (`institution_site_quality_visit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- staff
ALTER TABLE `z_1463_staff` 
RENAME TO  `staff` ;

-- staff_attendances
CREATE TABLE `staff_attendances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` int(11) NOT NULL,
  `staff_attendance_type_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `security_user_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `school_year_id` (`academic_period_id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `security_user_id` (`security_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- staff_activities
ALTER TABLE `staff_activities` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '' ;

-- staff_attendance_types
CREATE TABLE `staff_attendance_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `international_code` varchar(10) DEFAULT NULL,
  `national_code` varchar(10) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- staff_categories
CREATE TABLE `staff_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `international_code` varchar(10) DEFAULT NULL,
  `national_code` varchar(10) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- staff_custom_field_values
ALTER TABLE `staff_custom_field_values` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '' ;

-- staff_leaves
ALTER TABLE `staff_leaves` 
CHANGE COLUMN `staff_id` `security_user_id` INT(11) NOT NULL COMMENT '' ;

-- staff_leave_types
CREATE TABLE `staff_leave_types` (
  `id` int(3) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `international_code` varchar(20) DEFAULT NULL,
  `national_code` varchar(20) DEFAULT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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

-- census_behaviours
CREATE TABLE `census_behaviours` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gender_id` int(11) NOT NULL,
  `value` int(11) NOT NULL DEFAULT '0',
  `source` int(1) NOT NULL DEFAULT '0' COMMENT '0 -> Data Entry, 1 -> External, 2 -> Estimate',
  `student_behaviour_category_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `student_behaviour_category_id` (`student_behaviour_category_id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `school_year_id` (`academic_period_id`),
  KEY `gender_id` (`gender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- census_buildings
CREATE TABLE `census_buildings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` int(11) NOT NULL,
  `infrastructure_building_id` int(11) NOT NULL,
  `infrastructure_material_id` int(11) NOT NULL,
  `infrastructure_status_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `source` int(1) DEFAULT '0' COMMENT '0-dataentry,1-external,2-estimate',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `school_year_id` (`academic_period_id`),
  KEY `source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- census_classes
CREATE TABLE `census_classes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `classes` int(5) NOT NULL,
  `seats` int(11) DEFAULT NULL,
  `institution_site_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `source` int(1) DEFAULT '0' COMMENT '0-dataentry,1-external,2-estimate',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `school_year_id` (`academic_period_id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- census_class_grades
CREATE TABLE `census_class_grades` (
  `census_class_id` int(11) NOT NULL,
  `education_grade_id` int(11) NOT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`census_class_id`,`education_grade_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- census_custom_field_options
CREATE TABLE `census_custom_field_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` varchar(250) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `census_custom_field_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8;

-- census_custom_fields
CREATE TABLE `census_custom_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) NOT NULL,
  `order` int(3) NOT NULL,
  `type` int(1) NOT NULL DEFAULT '1' COMMENT '1 -> Label, 2 -> Text, 3 -> Dropdown, 4 -> Multiple, 5 -> Textarea',
  `visible` int(1) NOT NULL DEFAULT '1',
  `institution_site_type_id` int(11) NOT NULL DEFAULT '0',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8;

-- census_custom_values
CREATE TABLE `census_custom_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` varchar(250) NOT NULL,
  `census_custom_field_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `source` int(1) DEFAULT '0' COMMENT '0-dataentry,1-external,2-estimate',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `school_year_id` (`academic_period_id`),
  KEY `source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- census_energy
CREATE TABLE `census_energy` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` int(11) NOT NULL,
  `infrastructure_energy_id` int(11) NOT NULL,
  `infrastructure_status_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `source` int(1) DEFAULT '0' COMMENT '0-dataentry,1-external,2-estimate',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `school_year_id` (`academic_period_id`),
  KEY `source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- census_finances
CREATE TABLE `census_finances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(150) NOT NULL,
  `amount` float(11,2) NOT NULL,
  `finance_source_id` int(11) NOT NULL,
  `finance_category_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `source` int(1) DEFAULT '0' COMMENT '0-dataentry,1-external,2-estimate',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `school_year_id` (`academic_period_id`),
  KEY `source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- census_furniture
CREATE TABLE `census_furniture` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` int(11) NOT NULL,
  `infrastructure_furniture_id` int(11) NOT NULL,
  `infrastructure_status_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `source` int(1) DEFAULT '0' COMMENT '0-dataentry,1-external,2-estimate',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `school_year_id` (`academic_period_id`),
  KEY `source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- census_graduates
CREATE TABLE `census_graduates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gender_id` int(11) NOT NULL,
  `value` int(11) NOT NULL DEFAULT '0',
  `education_programme_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `source` int(1) DEFAULT '0' COMMENT '0-dataentry,1-external,2-estimate',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `school_year_id` (`academic_period_id`),
  KEY `education_programme_id` (`education_programme_id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `gender_id` (`gender_id`),
  KEY `source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- census_grids
CREATE TABLE `census_grids` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) NOT NULL,
  `description` text,
  `x_title` varchar(100) DEFAULT NULL,
  `y_title` varchar(100) DEFAULT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `institution_site_type_id` int(11) NOT NULL DEFAULT '0',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;

-- census_grid_values
CREATE TABLE `census_grid_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` varchar(250) NOT NULL,
  `census_grid_id` int(11) NOT NULL,
  `census_grid_x_category_id` int(11) DEFAULT NULL,
  `census_grid_y_category_id` int(11) DEFAULT NULL,
  `institution_site_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `source` int(1) DEFAULT '0' COMMENT '0-dataentry,1-external,2-estimate',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `school_year_id` (`academic_period_id`),
  KEY `source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- census_grid_x_categories
CREATE TABLE `census_grid_x_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) NOT NULL,
  `order` int(1) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `census_grid_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8;

-- census_grid_y_categories
CREATE TABLE `census_grid_y_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) NOT NULL,
  `order` int(1) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `census_grid_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8;

-- census_resources
CREATE TABLE `census_resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` int(11) NOT NULL,
  `infrastructure_resource_id` int(11) NOT NULL,
  `infrastructure_status_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `source` int(1) DEFAULT '0' COMMENT '0-dataentry,1-external,2-estimate',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `school_year_id` (`academic_period_id`),
  KEY `source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- census_rooms
CREATE TABLE `census_rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` int(11) NOT NULL,
  `infrastructure_room_id` int(11) NOT NULL,
  `infrastructure_status_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `source` int(1) DEFAULT '0' COMMENT '0-dataentry,1-external,2-estimate',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `school_year_id` (`academic_period_id`),
  KEY `source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- census_sanitations
CREATE TABLE `census_sanitations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gender_id` int(11) NOT NULL,
  `value` int(11) NOT NULL DEFAULT '0',
  `infrastructure_sanitation_id` int(11) NOT NULL,
  `infrastructure_material_id` int(11) NOT NULL,
  `infrastructure_status_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `source` int(1) DEFAULT '0' COMMENT '0-dataentry,1-external,2-estimate',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `school_year_id` (`academic_period_id`),
  KEY `infrastructure_status_id` (`infrastructure_status_id`),
  KEY `source` (`source`),
  KEY `gender_id` (`gender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- census_shifts
CREATE TABLE `census_shifts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `census_class_id` int(11) NOT NULL,
  `shift_id` int(11) DEFAULT NULL,
  `value` int(11) DEFAULT NULL,
  `source` int(1) DEFAULT '0' COMMENT '0-dataentry,1-external,2-estimate',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `census_class_id` (`census_class_id`),
  KEY `source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- census_students
CREATE TABLE `census_students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `age` int(5) NOT NULL,
  `value` int(11) NOT NULL DEFAULT '0',
  `gender_id` int(11) NOT NULL,
  `student_category_id` int(11) NOT NULL,
  `education_grade_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `source` int(1) DEFAULT '0' COMMENT '0-dataentry,1-external,2-estimate',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `gender_id` (`gender_id`),
  KEY `student_category_id` (`student_category_id`),
  KEY `education_grade_id` (`education_grade_id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `school_year_id` (`academic_period_id`),
  KEY `source` (`source`),
  KEY `age` (`age`),
  KEY `unique_yearage_census` (`institution_site_id`,`education_grade_id`,`student_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- census_teacher_fte
CREATE TABLE `census_teacher_fte` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gender_id` int(11) NOT NULL,
  `value` int(11) NOT NULL DEFAULT '0',
  `education_level_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `source` int(1) DEFAULT '0' COMMENT '0-dataentry,1-external,2-estimate',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `education_level_id` (`education_level_id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `school_year_id` (`academic_period_id`),
  KEY `source` (`source`),
  KEY `gender_id` (`gender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- census_teacher_grades
CREATE TABLE `census_teacher_grades` (
  `census_teacher_id` int(11) NOT NULL,
  `education_grade_id` int(11) NOT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`census_teacher_id`,`education_grade_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- census_teachers
CREATE TABLE `census_teachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gender_id` int(11) NOT NULL,
  `value` int(11) NOT NULL DEFAULT '0',
  `institution_site_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `source` int(1) DEFAULT '0' COMMENT '0-dataentry,1-external,2-estimate',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `school_year_id` (`academic_period_id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `source` (`source`),
  KEY `gender_id` (`gender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- census_teacher_training
CREATE TABLE `census_teacher_training` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gender_id` int(11) NOT NULL,
  `value` int(11) NOT NULL DEFAULT '0',
  `education_level_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `source` int(1) DEFAULT '0' COMMENT '0-dataentry,1-external,2-estimate',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `school_year_id` (`academic_period_id`),
  KEY `source` (`source`),
  KEY `gender_id` (`gender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- census_textbooks
CREATE TABLE `census_textbooks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` int(11) NOT NULL,
  `education_grade_subject_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `source` int(1) DEFAULT '0' COMMENT '0-dataentry,1-external,2-estimate',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `school_year_id` (`academic_period_id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- census_verifications
CREATE TABLE `census_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `institution_site_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '1' COMMENT '0 -> Unverified, 1 -> Verified',
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `school_year_id` (`academic_period_id`),
  KEY `status` (`status`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- census_water
CREATE TABLE `census_water` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` int(11) NOT NULL,
  `infrastructure_water_id` int(11) NOT NULL,
  `infrastructure_status_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `source` int(1) DEFAULT '0' COMMENT '0-dataentry,1-external,2-estimate',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `school_year_id` (`academic_period_id`),
  KEY `infrastructure_status_id` (`infrastructure_status_id`),
  KEY `source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- students
ALTER TABLE `z_1463_students` 
RENAME TO  `students` ;

-- guardians
ALTER TABLE `z_1463_guardians` 
RENAME TO  `guardians` ;

-- student_custom_field_values
ALTER TABLE `student_custom_field_values` 
CHANGE COLUMN `student_id` `security_user_id` INT(11) NOT NULL COMMENT '' ;

-- student_custom_table_cells
ALTER TABLE `student_custom_table_cells` 
CHANGE COLUMN `student_id` `security_user_id` INT(11) NOT NULL COMMENT '' ;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1463';