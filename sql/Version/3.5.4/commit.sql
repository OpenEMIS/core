-- POCOR-2450
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES('POCOR-2450', NOW());

INSERT INTO `custom_field_types` (`id`, `code`, `name`, `value`, `description`, `format`, `is_mandatory`, `is_unique`, `visible`) VALUES
(10, 'COORDINATES', 'Coordinates', 'text_value', '', 'OpenEMIS', 1, 0, 1);

-- backup tables
CREATE TABLE `z_2450_custom_modules` LIKE `custom_modules`;
INSERT INTO `z_2450_custom_modules` SELECT * FROM `custom_modules`;

UPDATE `custom_modules` SET `supported_field_types`='TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE,DATE,TIME,STUDENT_LIST,COORDINATES' WHERE `code`='Institution';
UPDATE `custom_modules` SET `supported_field_types`='TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE,DATE,TIME,COORDINATES' WHERE `code`='Student';
UPDATE `custom_modules` SET `supported_field_types`='TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE,DATE,TIME,COORDINATES' WHERE `code`='Staff';
UPDATE `custom_modules` SET `supported_field_types`='TEXT,NUMBER,TEXTAREA,DROPDOWN,CHECKBOX,TABLE,DATE,TIME,COORDINATES' WHERE `code`='Infrastructure';
UPDATE `custom_modules` SET `supported_field_types`='TEXT,NUMBER,DROPDOWN,COORDINATES' WHERE `code`='Institution > Students';


-- POCOR-2588
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2588', NOW());

-- CREATING EDITABLE COLUMBS
ALTER TABLE `academic_period_levels` ADD `editable` INT(1) NOT NULL DEFAULT TRUE AFTER `level`, ADD INDEX (`editable`);
UPDATE `academic_period_levels` SET `editable` = '0' WHERE `academic_period_levels`.`name` = 'Year';


-- BACKUP institution_students
CREATE TABLE z_2588_institution_students LIKE institution_students;
INSERT INTO z_2588_institution_students SELECT * FROM institution_students;

-- Table structure for table `z_2588_academic_period_parent`
CREATE TABLE IF NOT EXISTS `z_2588_academic_period_parent` (
  `period_name` varchar(50) NOT NULL,
  `period_id` int(11) NOT NULL,
  `parent_name` varchar(50) NOT NULL,
  `parent_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Indexes for table `z_2588_academic_period_parent`
ALTER TABLE `z_2588_academic_period_parent`
  ADD KEY `period_id` (`period_id`,`parent_id`);

ALTER TABLE `z_2588_academic_period_parent` CHANGE `parent_id` `parent_id` INT(11) NULL;
-- end z_2588_academic_period_parent


-- POPULATING z_2588_academic_period_parent WITH DATA OF YEAR PARENT
INSERT INTO z_2588_academic_period_parent (period_name, period_id, parent_name, parent_id)
SELECT t1.name, t1.id, '', (SELECT t2.id
       FROM academic_periods t2
       INNER JOIN academic_period_levels ON (t2.academic_period_level_id = academic_period_levels.id)
       WHERE t2.lft < t1.lft AND t2.rght > t1.rght AND academic_period_levels.name = 'Year'
       LIMIT 1)
AS year_parent_id FROM academic_periods t1;

-- REMOVING ENTRIES WITHOUT YEAR PARENT SO IT WILL NOT BE PART OF THE INNER JOIN
DELETE FROM z_2588_academic_period_parent WHERE parent_id IS NULL;

-- UPDATING ENTRIES WITH YEAR NAME FOR EASY VISUAL CHECKING
UPDATE z_2588_academic_period_parent
    INNER JOIN academic_periods ON (z_2588_academic_period_parent.parent_id = academic_periods.id)
    SET parent_name = academic_periods.name;

-- UPDATING ALL STUDENT RECORDS TO USE ACADEMIC PERIOD OF YEAR LEVEL IF AVAILABLE
UPDATE institution_students
    INNER JOIN z_2588_academic_period_parent ON (institution_students.academic_period_id = z_2588_academic_period_parent.period_id)
        SET institution_students.academic_period_id = z_2588_academic_period_parent.parent_id;


-- 3.5.4
UPDATE config_items SET value = '3.5.4' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
