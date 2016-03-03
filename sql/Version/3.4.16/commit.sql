-- POCOR-1905
-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-1905', NOW());

INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created` )
VALUES
('Minimum Length', 'password_min_length', 'Password', 'Min Length', '6', '6', 0 , 1 , '', '', NULL , NULL , 1 , now());

INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created` )
VALUES
('Has at least 1 Uppercase Character', 'password_has_uppercase', 'Password', 'Has Uppercase', '0', '0', 0 , 1 , 'Dropdown', 'yes_no', NULL , NULL , 1 , now());

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
ADD INDEX `absence_type_id` (`absence_type_id` ASC)  COMMENT '';

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


-- POCOR-2658
-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2658', NOW());

-- labels
UPDATE `labels` SET `field_name` = 'Area Administrative' WHERE `module` = 'Institutions' AND `field` = 'area_administrative_id';
UPDATE `labels` SET `field_name` = 'Area Education' WHERE `module` = 'Institutions' AND `field` = 'area_id';


-- 3.4.16
-- db_version
UPDATE config_items SET value = '3.4.16' WHERE code = 'db_version';
