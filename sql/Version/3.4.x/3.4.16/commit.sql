-- POCOR-1798
-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-1798', NOW());


-- Converting Employment Types
DROP TABLE IF EXISTS `employment_types`;
CREATE TABLE IF NOT EXISTS `employment_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SELECT `id` INTO @fieldOptionId FROM `field_options` WHERE `code` = 'EmploymentTypes';
UPDATE field_options SET params = '{"model":"FieldOption.EmploymentTypes"}' WHERE id = @fieldOptionId;
INSERT INTO employment_types (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created` ) SELECT `id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM field_option_values WHERE field_option_id = @fieldOptionId;

-- Converting Extracurricular Types
DROP TABLE IF EXISTS `extracurricular_types`;
CREATE TABLE IF NOT EXISTS `extracurricular_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SELECT `id` INTO @fieldOptionId FROM `field_options` WHERE `code` = 'ExtracurricularTypes';
UPDATE field_options SET params = '{"model":"FieldOption.ExtracurricularTypes"}' WHERE id = @fieldOptionId;
INSERT INTO extracurricular_types (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created` ) SELECT `id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM field_option_values WHERE field_option_id = @fieldOptionId;

-- Converting Identity Types
DROP TABLE IF EXISTS `identity_types`;
CREATE TABLE IF NOT EXISTS `identity_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SELECT `id` INTO @fieldOptionId FROM `field_options` WHERE `code` = 'IdentityTypes';
UPDATE field_options SET params = '{"model":"FieldOption.IdentityTypes"}' WHERE id = @fieldOptionId;
INSERT INTO identity_types (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created` ) SELECT `id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM field_option_values WHERE field_option_id = @fieldOptionId;

-- Converting Languages
DROP TABLE IF EXISTS `languages`;
CREATE TABLE IF NOT EXISTS `languages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SELECT `id` INTO @fieldOptionId FROM `field_options` WHERE `code` = 'Languages';
UPDATE field_options SET params = '{"model":"Languages"}' WHERE id = @fieldOptionId;
INSERT INTO languages (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created` ) SELECT `id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM field_option_values WHERE field_option_id = @fieldOptionId;

-- Converting License Types
DROP TABLE IF EXISTS `license_types`;
CREATE TABLE IF NOT EXISTS `license_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SELECT `id` INTO @fieldOptionId FROM `field_options` WHERE `code` = 'LicenseTypes';
UPDATE field_options SET params = '{"model":"FieldOption.LicenseTypes"}' WHERE id = @fieldOptionId;
INSERT INTO license_types (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created` ) SELECT `id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM field_option_values WHERE field_option_id = @fieldOptionId;

-- Converting Special Need Types
DROP TABLE IF EXISTS `special_need_types`;
CREATE TABLE IF NOT EXISTS `special_need_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SELECT `id` INTO @fieldOptionId FROM `field_options` WHERE `code` = 'SpecialNeedTypes';
UPDATE field_options SET params = '{"model":"FieldOption.SpecialNeedTypes"}' WHERE id = @fieldOptionId;
INSERT INTO special_need_types (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created` ) SELECT `id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM field_option_values WHERE field_option_id = @fieldOptionId;


-- POCOR-1905
-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-1905', NOW());

INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created` )
VALUES
('Minimum Length', 'password_min_length', 'Password', 'Min Length', '6', '6', 0 , 1 , '', '', NULL , NULL , 1 , now());

INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created` )
VALUES
('Has at least 1 Uppercase Character', 'password_has_uppercase', 'Password', 'Has Uppercase', '0', '0', 0 , 1 , 'Dropdown', 'yes_no', NULL , NULL , 1 , now());

-- added in after test fail - 'didnt implement lowercase'
INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created` )
VALUES
('Has at least 1 Lowercase Character', 'password_has_lowercase', 'Password', 'Has Lowercase', '0', '0', 0 , 1 , 'Dropdown', 'yes_no', NULL , NULL , 1 , now());

INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created` )
VALUES
('Has at least 1 Number', 'password_has_number', 'Password', 'Has Number', '0', '0', 0 , 1 , 'Dropdown', 'yes_no', NULL , NULL , 1 , now());

INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created` )
VALUES
('Has at least 1 Non-alphanumeric Character', 'password_has_non_alpha', 'Password', 'Has Non Alpha', '0', '0', 0 , 1 , 'Dropdown', 'yes_no', NULL , NULL , 1 , now());


-- POCOR-2208
-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2208', NOW());

UPDATE labels SET field_name = 'Deletable' WHERE module = 'WorkflowSteps' AND module_name = 'Workflow -> Steps' AND field_name = 'Removable';


-- POCOR-2540
-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2540', NOW());

-- add description to workflow_actions
ALTER TABLE `workflow_actions` ADD `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `name`;


-- POCOR-2562
-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2562', NOW());

-- absence_types
CREATE TABLE `absence_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(100) NOT NULL,
  `name` varchar(250) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `absence_types` (`code`, `name`) VALUES ('EXCUSED', 'Absence - Excused');
INSERT INTO `absence_types` (`code`, `name`) VALUES ('UNEXCUSED', 'Absence - Unexcused');
INSERT INTO `absence_types` (`code`, `name`) VALUES ('LATE', 'Late');

-- institution_staff_absences
CREATE TABLE `z_2562_institution_staff_absences` LIKE `institution_staff_absences`;

INSERT INTO `z_2562_institution_staff_absences`
SELECT * FROM `institution_staff_absences`;

UPDATE `institution_staff_absences`
SET start_time = str_to_date(start_time, '%h:%i %p'), end_time = str_to_date(end_time, '%h:%i %p');

ALTER TABLE `institution_staff_absences` 
CHANGE COLUMN `start_time` `start_time` TIME NULL DEFAULT NULL COMMENT '' ,
CHANGE COLUMN `end_time` `end_time` TIME NULL DEFAULT NULL COMMENT '' ;

ALTER TABLE `institution_staff_absences` 
ADD COLUMN `absence_type_id` INT NULL DEFAULT 0 AFTER `institution_id`,
ADD INDEX `absence_type_id` (`absence_type_id`);

UPDATE institution_staff_absences
SET absence_type_id = (
	SELECT id FROM absence_types WHERE code = 'EXCUSED'
)
WHERE staff_absence_reason_id <> 0;

UPDATE institution_staff_absences
SET absence_type_id = (
	SELECT id FROM absence_types WHERE code = 'UNEXCUSED'
)
WHERE staff_absence_reason_id = 0;

ALTER TABLE `institution_staff_absences` 
CHANGE COLUMN `absence_type_id` `absence_type_id` INT(11) NOT NULL COMMENT '' ;

-- institution_student_absences
CREATE TABLE `z_2562_institution_student_absences` LIKE `institution_student_absences`;

INSERT INTO `z_2562_institution_student_absences`
SELECT * FROM `institution_student_absences`;

UPDATE `institution_student_absences`
SET start_time = str_to_date(start_time, '%h:%i %p'), end_time = str_to_date(end_time, '%h:%i %p');

ALTER TABLE `institution_student_absences` 
CHANGE COLUMN `start_time` `start_time` TIME NULL DEFAULT NULL COMMENT '' ,
CHANGE COLUMN `end_time` `end_time` TIME NULL DEFAULT NULL COMMENT '' ;

ALTER TABLE `institution_student_absences` 
ADD COLUMN `absence_type_id` INT NULL DEFAULT 0 AFTER `institution_id`,
ADD INDEX `absence_type_id` (`absence_type_id`);

UPDATE institution_student_absences
SET absence_type_id = (
	SELECT id FROM absence_types WHERE code = 'EXCUSED'
)
WHERE student_absence_reason_id <> 0;

UPDATE institution_student_absences
SET absence_type_id = (
	SELECT id FROM absence_types WHERE code = 'UNEXCUSED'
)
WHERE student_absence_reason_id = 0;

ALTER TABLE `institution_student_absences` 
CHANGE COLUMN `absence_type_id` `absence_type_id` INT(11) NOT NULL COMMENT '' ;

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StaffAbsences', 'absence_type_id', 'Institutions -> Staff -> Absences', 'Type', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'InstitutionStudentAbsences', 'absence_type_id', 'Institutions -> Students -> Absences', 'Type', 1, 1, NOW());

-- staff_absence_reasons
CREATE TABLE `staff_absence_reasons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SET @reorder = 0;
INSERT INTO `staff_absence_reasons`
SELECT 
  `field_option_values`.`id`,
  `field_option_values`.`name`, 
  @reorder:=@reorder+1 as `order`, 
  `field_option_values`.`visible`, 
  `field_option_values`.`editable`, 
  `field_option_values`.`default`, 
  `field_option_values`.`international_code`, 
  `field_option_values`.`national_code`, 
  `field_option_values`.`modified_user_id`, 
  `field_option_values`.`modified`, 
  `field_option_values`.`created_user_id`, 
  `field_option_values`.`created` 
FROM `field_option_values` 
INNER JOIN `field_options` 
  ON `field_options`.`id` = `field_option_values`.`field_option_id` 
    AND `field_options`.`code` = 'StaffAbsenceReasons' 
    AND `field_options`.`plugin` = 'FieldOption'
Order By `field_option_values`.`order`;

-- student_absence_reasons
CREATE TABLE `student_absence_reasons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SET @studentReasonOrder = 0;
INSERT INTO `student_absence_reasons`
SELECT 
  `field_option_values`.`id`,
  `field_option_values`.`name`, 
  @studentReasonOrder:=@studentReasonOrder+1 as `order`, 
  `field_option_values`.`visible`, 
  `field_option_values`.`editable`, 
  `field_option_values`.`default`, 
  `field_option_values`.`international_code`, 
  `field_option_values`.`national_code`, 
  `field_option_values`.`modified_user_id`, 
  `field_option_values`.`modified`, 
  `field_option_values`.`created_user_id`, 
  `field_option_values`.`created`
FROM `field_option_values`
INNER JOIN `field_options` 
  ON `field_options`.`id` = `field_option_values`.`field_option_id` 
    AND `field_options`.`code` = 'StudentAbsenceReasons' 
    AND `field_options`.`plugin` = 'FieldOption'
Order By `field_option_values`.`order`;

UPDATE `institution_staff_absences` INNER JOIN `staff_absence_reasons` 
  ON `institution_staff_absences`.`staff_absence_reason_id` = `staff_absence_reasons`.`id`
SET  `institution_staff_absences`.`staff_absence_reason_id` = `staff_absence_reasons`.`order`;

UPDATE `staff_absence_reasons`
SET `id` = `order`;

UPDATE `institution_student_absences` INNER JOIN `student_absence_reasons` 
  ON `institution_student_absences`.`student_absence_reason_id` = `student_absence_reasons`.`id`
SET  `institution_student_absences`.`student_absence_reason_id` = `student_absence_reasons`.`order`;

UPDATE `student_absence_reasons`
SET `id` = `order`;

UPDATE `field_option_values` 
INNER JOIN `field_options` 
  ON `field_options`.`id` = `field_option_values`.`field_option_id`
    AND (`field_options`.`code` = 'StaffAbsenceReasons' OR `field_options`.`code` = 'StudentAbsenceReasons')
    AND `field_options`.`plugin` = 'FieldOption'
SET `field_option_values`.`visible` = 0;

UPDATE `field_options` SET `params`='{\"model\":\"FieldOptions.StaffAbsenceReasons\"}' WHERE `code`='StaffAbsenceReasons' AND `plugin` = 'FieldOptions';
UPDATE `field_options` SET `params`='{\"model\":\"FieldOptions.StudentAbsenceReasons\"}' WHERE `code`='StudentAbsenceReasons' AND `plugin` = 'FieldOptions';


-- POCOR-2603
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `created_user_id`, `created`) VALUES
(uuid(), 'Accounts', 'password', 'Students -> Accounts | Staff -> Accounts | Security -> Accounts', 'New Password', NULL, NULL, 1, 1, now()),
(uuid(), 'Accounts', 'retype_password', 'Students -> Accounts | Staff -> Accounts | Security -> Accounts', 'Retype New Password', NULL, NULL, 1, 1, now()),
(uuid(), 'StudentAccount', 'password', 'Institution -> Students -> Accounts', 'New Password', NULL, NULL, 1, 1, now()),
(uuid(), 'StudentAccount', 'retype_password', 'Institution -> Students -> Accounts', 'Retype New Password', NULL, NULL, 1, 1, now()),
(uuid(), 'StaffAccount', 'password', 'Institution -> Staff -> Accounts', 'New Password', NULL, NULL, 1, 1, now()),
(uuid(), 'StaffAccount', 'retype_password', 'Institution -> Staff -> Accounts', 'Retype New Password', NULL, NULL, 1, 1, now())
;


-- POCOR-2609
-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2609', NOW());

-- procedures
DROP PROCEDURE IF EXISTS patchOrder;
DROP PROCEDURE IF EXISTS tmpRefTable;
DROP PROCEDURE IF EXISTS patchNoFilterOrder;
DELIMITER $$

CREATE PROCEDURE tmpRefTable(
	IN referenceTable varchar(50)
)
BEGIN
	DROP TABLE IF EXISTS `tmp_table`;
	CREATE TABLE `tmp_table` (
		`id` int(11) NOT NULL
	);
	SET @updateRecord = CONCAT('INSERT INTO `tmp_table` SELECT `id` FROM `', referenceTable, '`');
	PREPARE updateRecord FROM @updateRecord;
	EXECUTE updateRecord;
	DEALLOCATE PREPARE updateRecord;
END
$$
DELIMITER ;


DELIMITER $$
CREATE PROCEDURE patchOrder(
	IN updateTblName varchar(50),
	IN updateTblColumn varchar(50)
)
BEGIN

	DECLARE flag INT DEFAULT 0;
	DECLARE filterId VARCHAR(250);
	DECLARE system_cursor CURSOR FOR SELECT id from tmp_table;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;

	OPEN system_cursor;

	forloop : LOOP
		FETCH system_cursor INTO filterId;
		IF flag = 1 THEN
	      LEAVE forloop;
		END IF;
		SET @rank:=0;
		SET @updateRecord = CONCAT('UPDATE `', updateTblName,'` SET `order`=@rank:=@rank+1 WHERE `', updateTblColumn, '` = \'', filterId,'\' ORDER BY `order`');
		PREPARE updateRecord FROM @updateRecord;
		EXECUTE updateRecord;
		DEALLOCATE PREPARE updateRecord;
		END LOOP forloop;
		CLOSE system_cursor;
END
$$

DELIMITER ;

DELIMITER $$
CREATE PROCEDURE patchNoFilterOrder()
BEGIN
	DECLARE flag INT DEFAULT 0;
    DECLARE tblName VARCHAR(100);
	DECLARE tblName_cursor CURSOR FOR 
		SELECT TABLE_NAME 
		FROM information_schema.COLUMNS
		WHERE COLUMN_NAME = 'order'
		AND TABLE_SCHEMA = DATABASE()
		AND TABLE_NAME NOT IN (
			'area_administratives',
			'areas',
			'assessment_grading_options',
			'bank_branches',
			'config_item_options',
			'contact_types',
			'custom_field_options',
			'custom_forms_fields',
			'custom_table_columns',
			'custom_table_rows',
			'education_cycles',
			'education_field_of_studies',
			'education_grades',
			'education_levels',
			'education_programmes',
			'field_option_values',
			'import_mapping',
			'infrastructure_custom_field_options',
			'infrastructure_custom_forms_fields',
			'infrastructure_custom_table_columns',
			'infrastructure_custom_table_rows',
			'infrastructure_types',
			'institution_custom_field_options',
			'institution_custom_forms_fields',
			'institution_custom_table_columns',
			'institution_custom_table_rows',
			'rubric_sections',
			'rubric_criterias',
			'rubric_template_options',
			'security_functions',
			'security_roles',
			'staff_custom_field_options',
			'staff_custom_forms_fields',
			'staff_custom_table_columns',
			'staff_custom_table_rows',
			'student_custom_field_options',
			'student_custom_forms_fields',
			'student_custom_table_columns',
			'student_custom_table_rows',
			'survey_forms_questions',
			'survey_question_choices',
			'survey_table_columns',
			'survey_table_rows'
		)
		AND TABLE_NAME NOT LIKE 'z_%';
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;
    
	OPEN tblName_cursor;
    
    forloop : LOOP
    FETCH tblName_cursor INTO tblName;

    IF flag = 1 THEN
      LEAVE forloop;
	END IF;
    
    SET @rank = 0;
    SET @updateRecord = CONCAT('UPDATE `', tblName,'` SET `order`=@rank:=@rank+1  ORDER BY `order`');
	PREPARE updateRecord FROM @updateRecord;
	EXECUTE updateRecord;
    DEALLOCATE PREPARE updateRecord;
    
    END LOOP forloop;
    CLOSE tblName_cursor;
END
$$

DELIMITER ;

-- patch all tables with order field but without filter
CALL patchNoFilterOrder();

-- area_administratives
CALL tmpRefTable('area_administratives');
CALL patchOrder('area_administratives', 'parent_id');

-- areas
CALL tmpRefTable('areas');
CALL patchOrder('areas', 'parent_id');

-- assessment_grading_options
CALL tmpRefTable('assessment_grading_types');
CALL patchOrder('assessment_grading_options', 'assessment_grading_type_id');

-- bank_branches
CALL tmpRefTable('banks');
CALL patchOrder('bank_branches', 'bank_id');

-- config_item_options
DROP TABLE IF EXISTS `tmp_table`;
CREATE TABLE `tmp_table` (
	`id` VARCHAR(200) NOT NULL
);
INSERT INTO `tmp_table` SELECT DISTINCT(option_type) FROM `config_item_options`;
CALL patchOrder('config_item_options', 'option_type');

-- contact_types
CALL tmpRefTable('contact_options');
CALL patchOrder('contact_types', 'contact_option_id');

-- custom_field_options
CALL tmpRefTable('custom_fields');
CALL patchOrder('custom_field_options', 'custom_field_id');

-- custom_table_columns
CALL patchOrder('custom_table_columns', 'custom_field_id');

-- custom_table_rows
CALL patchOrder('custom_table_rows', 'custom_field_id');

-- custom_forms_fields
CALL tmpRefTable('custom_forms');
CALL patchOrder('custom_forms_fields', 'custom_form_id');

-- education_cycles
CALL tmpRefTable('education_levels');
CALL patchOrder('education_cycles', 'education_level_id');

-- education_field_of_studies
CALL tmpRefTable('education_programme_orientations');
CALL patchOrder('education_field_of_studies', 'education_programme_orientation_id');

-- education_grades
CALL tmpRefTable('education_programmes');
CALL patchOrder('education_grades', 'education_programme_id');

-- education_levels
CALL tmpRefTable('education_systems');
CALL patchOrder('education_levels', 'education_system_id');

-- education_programmes
CALL tmpRefTable('education_cycles');
CALL patchOrder('education_programmes', 'education_cycle_id');

-- field_option_values
CALL tmpRefTable('field_options');
CALL patchOrder('field_option_values', 'field_option_id');

-- infrastructure_custom_field_options
CALL tmpRefTable('infrastructure_custom_fields');
CALL patchOrder('infrastructure_custom_field_options', 'infrastructure_custom_field_id');

-- infrastructure_custom_table_columns
CALL patchOrder('infrastructure_custom_table_columns', 'infrastructure_custom_field_id');

-- infrastructure_custom_table_rows
CALL patchOrder('infrastructure_custom_table_rows', 'infrastructure_custom_field_id');

-- infrastructure_custom_forms_fields
CALL tmpRefTable('infrastructure_custom_forms');
CALL patchOrder('infrastructure_custom_forms_fields', 'infrastructure_custom_form_id');

-- infrastructure_types
CALL tmpRefTable('infrastructure_levels');
CALL patchOrder('infrastructure_types', 'infrastructure_level_id');

-- institution_custom_field_options
CALL tmpRefTable('institution_custom_fields');
CALL patchOrder('institution_custom_field_options', 'institution_custom_field_id');

-- institution_custom_table_columns
CALL patchOrder('institution_custom_table_columns', 'institution_custom_field_id');

-- institution_custom_table_rows
CALL patchOrder('institution_custom_table_rows', 'institution_custom_field_id');

-- institution_custom_forms_fields
CALL tmpRefTable('institution_custom_forms');
CALL patchOrder('institution_custom_forms_fields', 'institution_custom_form_id');

-- rubric_criterias
CALL tmpRefTable('rubric_sections');
CALL patchOrder('rubric_criterias', 'rubric_section_id');

-- rubric_template_options
CALL tmpRefTable('rubric_templates');
CALL patchOrder('rubric_template_options', 'rubric_template_id');

-- rubric_sections
CALL patchOrder('rubric_sections', 'rubric_template_id');

-- security_roles
CALL tmpRefTable('security_groups');

DROP PROCEDURE IF EXISTS patchSecurityRoleOrder;
DELIMITER $$
CREATE PROCEDURE patchSecurityRoleOrder(
	IN updateTblName varchar(50),
	IN updateTblColumn varchar(50)
)
BEGIN

	DECLARE flag INT DEFAULT 0;
	DECLARE filterId VARCHAR(250);
	DECLARE system_cursor CURSOR FOR SELECT id from tmp_table;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;

	OPEN system_cursor;

	forloop : LOOP
		FETCH system_cursor INTO filterId;
		IF flag = 1 THEN
	      LEAVE forloop;
		END IF;
		SET @rank:=0;
		SET @updateRecord = CONCAT('UPDATE `', updateTblName,'` SET `order`=@rank:=@rank+1 WHERE `', updateTblColumn, '` = \'', filterId,'\' ORDER BY `order`');
		PREPARE updateRecord FROM @updateRecord;
		EXECUTE updateRecord;
		DEALLOCATE PREPARE updateRecord;
		END LOOP forloop;
		CLOSE system_cursor;

	SET @rank:=0;
	SET @updateRecord = CONCAT('UPDATE `', updateTblName,'` SET `order`=@rank:=@rank+1 WHERE `', updateTblColumn, '` IN (-1, 0) ORDER BY `order`');
	PREPARE updateRecord FROM @updateRecord;
	EXECUTE updateRecord;
	DEALLOCATE PREPARE updateRecord;
END
$$

DELIMITER ;

CALL patchSecurityRoleOrder('security_roles', 'security_group_id');
DROP PROCEDURE IF EXISTS patchSecurityRoleOrder;

-- staff_custom_field_options
CALL tmpRefTable('staff_custom_fields');
CALL patchOrder('staff_custom_field_options', 'staff_custom_field_id');

-- staff_custom_table_columns
CALL patchOrder('staff_custom_table_columns', 'staff_custom_field_id');

-- staff_custom_table_rows
CALL patchOrder('staff_custom_table_rows', 'staff_custom_field_id');

-- staff_custom_forms_fields
CALL tmpRefTable('staff_custom_forms');
CALL patchOrder('staff_custom_forms_fields', 'staff_custom_form_id');

-- student_custom_field_options
CALL tmpRefTable('student_custom_fields');
CALL patchOrder('student_custom_field_options', 'student_custom_field_id');

-- staff_custom_table_columns
CALL patchOrder('student_custom_table_columns', 'student_custom_field_id');

-- staff_custom_table_rows
CALL patchOrder('student_custom_table_rows', 'student_custom_field_id');

-- staff_custom_forms_fields
CALL tmpRefTable('student_custom_forms');
CALL patchOrder('student_custom_forms_fields', 'student_custom_form_id');

-- survey_question_choices
CALL tmpRefTable('survey_questions');
CALL patchOrder('survey_question_choices', 'survey_question_id');

-- survey_table_columns
CALL patchOrder('survey_table_columns', 'survey_question_id');

-- survey_table_rows
CALL patchOrder('survey_table_rows', 'survey_question_id');

-- drop procedures and tmp table
DROP PROCEDURE IF EXISTS patchOrder;
DROP PROCEDURE IF EXISTS tmpRefTable;
DROP PROCEDURE IF EXISTS patchNoFilterOrder;
DROP TABLE IF EXISTS `tmp_table`;


-- POCOR-2658
-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2658', NOW());

-- labels
UPDATE `labels` SET `field_name` = 'Area Administrative' WHERE `module` = 'Institutions' AND `field` = 'area_administrative_id';
UPDATE `labels` SET `field_name` = 'Area Education' WHERE `module` = 'Institutions' AND `field` = 'area_id';


-- 3.4.16
-- db_version
UPDATE config_items SET value = '3.4.16' WHERE code = 'db_version';
