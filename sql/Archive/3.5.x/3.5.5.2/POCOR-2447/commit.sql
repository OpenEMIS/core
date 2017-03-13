-- db_patches
INSERT INTO db_patches (`issue`, `created`) VALUES ('POCOR-2447', NOW());

-- survey_rules
CREATE TABLE `survey_rules` (
  `id` CHAR(36) NOT NULL COMMENT '',
  `survey_form_id` INT NOT NULL COMMENT '',
  `survey_question_id` INT NOT NULL COMMENT '',
  `dependent_question_id` INT NOT NULL COMMENT '',
  `show_options` TEXT NOT NULL COMMENT '',
  `enabled` INT NOT NULL COMMENT '',
  `modified` DATETIME NULL COMMENT '',
  `modified_user_id` INT NULL COMMENT '',
  `created` DATETIME NOT NULL COMMENT '',
  `created_user_id` INT NOT NULL COMMENT '',
  PRIMARY KEY (`survey_form_id`, `survey_question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;