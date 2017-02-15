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