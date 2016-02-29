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
SELECT * FROM `institution_staff_absences` WHERE start_time IS NOT NULL OR end_time IS NOT NULL;

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
SELECT * FROM `institution_student_absences` WHERE start_time IS NOT NULL OR end_time IS NOT NULL;

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

