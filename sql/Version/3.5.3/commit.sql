-- POCOR-2863
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-2863', NOW());

-- institution_class_students
ALTER TABLE `institution_class_students`
RENAME TO  `z_2863_institution_class_students` ;

CREATE TABLE `institution_class_students` (
  `id` CHAR(36) NOT NULL COMMENT '',
  `student_id` INT(11) NOT NULL COMMENT '',
  `institution_class_id` INT(11) NOT NULL COMMENT '',
  `education_grade_id` INT(11) NOT NULL COMMENT '',
  `academic_period_id` INT(11) NOT NULL COMMENT '',
  `institution_id` INT(11) NOT NULL COMMENT '',
  `student_status_id` INT(11) NOT NULL COMMENT '',
  `modified_user_id` INT(11) NULL COMMENT '',
  `modified` DATETIME NULL COMMENT '',
  `created_user_id` INT(11) NOT NULL COMMENT '',
  `created` DATETIME NOT NULL COMMENT '',
  PRIMARY KEY (`student_id`, `institution_class_id`, `education_grade_id`),
  UNIQUE INDEX `id` (`id`),
  INDEX `academic_period_id` (`academic_period_id`),
  INDEX `institution_id` (`institution_id`),
  INDEX `student_status_id` (`student_status_id`)
)ENGINE=InnoDB COLLATE utf8mb4_unicode_ci;

INSERT IGNORE INTO institution_class_students
SELECT `z_2863_institution_class_students`.`id`,
  `z_2863_institution_class_students`.`student_id`,
  `z_2863_institution_class_students`.`institution_class_id`,
  `z_2863_institution_class_students`.`education_grade_id`,
  `institution_classes`.`academic_period_id`,
  `institution_classes`.`institution_id`,
  `z_2863_institution_class_students`.`student_status_id`,
  `z_2863_institution_class_students`.`modified_user_id`,
  `z_2863_institution_class_students`.`modified`,
  `z_2863_institution_class_students`.`created_user_id`,
  `z_2863_institution_class_students`.`created`
FROM `z_2863_institution_class_students`
INNER JOIN `institution_classes` ON `z_2863_institution_class_students`.`institution_class_id` = `institution_classes`.`id`;


-- POCOR-2786
-- db_patches
INSERT IGNORE INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-2786', NOW());

UPDATE `db_patches` SET `created` = NOW() WHERE `issue` = 'POCOR-2786';

-- security_users
CREATE TABLE IF NOT EXISTS `z_2786_security_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gender_id` int(1) NOT NULL,
  `date_of_birth` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `z_2786_security_users` (`id`, `gender_id`, `date_of_birth`)
SELECT `id`, `gender_id`, `date_of_birth` FROM `security_users` WHERE `gender_id` = 0;

SET @genderId := 0;
SELECT `id` INTO @genderId FROM `genders` WHERE `name` = 'Male';

UPDATE `security_users` SET `gender_id` = @genderId WHERE `gender_id` = 0;

INSERT IGNORE INTO `z_2786_security_users` (`id`, `gender_id`, `date_of_birth`)
SELECT `id`, `gender_id`, `date_of_birth` FROM `security_users` WHERE `date_of_birth` = '0000-00-00';

UPDATE `security_users` SET `date_of_birth` = '1900-01-01' WHERE `date_of_birth` = '0000-00-00';

-- institution_students
CREATE TABLE IF NOT EXISTS `z_2786_institution_students` (
  `id` char(36) NOT NULL,
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `education_grade_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `start_year` int(4) NOT NULL,
  `end_date` date NOT NULL,
  `end_year` int(4) NOT NULL,
  `institution_id` int(11) NOT NULL,
  `created` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `z_2786_institution_students` (`id`, `student_id`, `education_grade_id`, `academic_period_id`, `start_date`, `start_year`, `end_date`, `end_year`, `institution_id`, `created`)
SELECT `institution_students`.`id`,
    `institution_students`.`student_id`,
    `institution_students`.`education_grade_id`,
    `institution_students`.`academic_period_id`,
    `institution_students`.`start_date`,
    `institution_students`.`start_year`,
    `institution_students`.`end_date`,
    `institution_students`.`end_year`,
    `institution_students`.`institution_id`,
    `institution_students`.`created`
FROM `institution_students`
INNER JOIN `academic_periods` ON `institution_students`.`academic_period_id` = `academic_periods`.`id`
WHERE `institution_students`.`start_date` < `academic_periods`.`start_date`
    OR `institution_students`.`end_date` > `academic_periods`.`end_date`
    OR `institution_students`.`created` = '0000-00-00'
    OR `institution_students`.`created` > NOW();

UPDATE `institution_students`
INNER JOIN `academic_periods`
    ON `institution_students`.`academic_period_id` = `academic_periods`.`id`
    AND `institution_students`.`start_date` < `academic_periods`.`start_date`
SET `institution_students`.`start_date` = `academic_periods`.`start_date`;

UPDATE `institution_students`
INNER JOIN `academic_periods`
    ON `institution_students`.`academic_period_id` = `academic_periods`.`id`
    AND `institution_students`.`end_date` > `academic_periods`.`end_date`
SET `institution_students`.`end_date` = `academic_periods`.`end_date`;

UPDATE `institution_students`
SET `institution_students`.`end_year` = YEAR(`institution_students`.`end_date`),
    `institution_students`.`start_year` = YEAR(`institution_students`.`start_date`);

UPDATE `institution_students`
SET `institution_students`.`created` = '2016-01-01'
WHERE `institution_students`.`created` = '0000-00-00'
    OR `institution_students`.`created` > NOW();


-- 3.5.3
UPDATE config_items SET value = '3.5.3' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
