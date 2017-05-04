-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3516', NOW());


-- custom_field_types
CREATE TABLE `z_3516_custom_field_types`  LIKE `custom_field_types`;
INSERT INTO `z_3516_custom_field_types` SELECT * FROM `custom_field_types`;

UPDATE `custom_field_types` SET `id` = `id`+1 WHERE `id` >= 3 order by id desc;

INSERT INTO `custom_field_types` (`id`, `code`, `name`, `value`, `description`, `format`, `is_mandatory`, `is_unique`, `visible`)
VALUES (3, 'DECIMAL', 'Decimal', 'decimal_value', '', 'OpenEMIS', '1', '0', '1');


-- custom_field_values
RENAME TABLE `custom_field_values` TO `z_3516_custom_field_values`;

DROP TABLE IF EXISTS `custom_field_values`;
CREATE TABLE IF NOT EXISTS `custom_field_values` (
    `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
    `text_value` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `number_value` int(11) DEFAULT NULL,
    `decimal_value` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `textarea_value` text COLLATE utf8mb4_unicode_ci,
    `date_value` date DEFAULT NULL,
    `time_value` time DEFAULT NULL,
    `file` longblob,
    `custom_field_id` int(11) NOT NULL COMMENT 'links to custom_fields.id',
    `custom_record_id` int(11) NOT NULL COMMENT 'links to custom_records.id',
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `number_value` (`number_value`),
    KEY `custom_field_id` (`custom_field_id`),
    KEY `custom_record_id` (`custom_record_id`),
    KEY `modified_user_id` (`modified_user_id`),
    KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `custom_field_values` (`id`, `text_value`, `number_value`, `decimal_value`, `textarea_value`, `date_value`, `time_value`, `file`, `custom_field_id`, `custom_record_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `text_value`, `number_value`, NULL, `textarea_value`, `date_value`, `time_value`, `file`, `custom_field_id`, `custom_record_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3516_custom_field_values`;


-- institution_custom_field_values
RENAME TABLE `institution_custom_field_values` TO `z_3516_institution_custom_field_values`;

DROP TABLE IF EXISTS `institution_custom_field_values`;
CREATE TABLE IF NOT EXISTS `institution_custom_field_values` (
    `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
    `text_value` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `number_value` int(11) DEFAULT NULL,
    `decimal_value` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `textarea_value` text COLLATE utf8mb4_unicode_ci,
    `date_value` date DEFAULT NULL,
    `time_value` time DEFAULT NULL,
    `file` longblob,
    `institution_custom_field_id` int(11) NOT NULL COMMENT 'links to institution_custom_fields.id',
    `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `number_value` (`number_value`),
    KEY `institution_custom_field_id` (`institution_custom_field_id`),
    KEY `institution_id` (`institution_id`),
    KEY `modified_user_id` (`modified_user_id`),
    KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `institution_custom_field_values` (`id`, `text_value`, `number_value`, `decimal_value`, `textarea_value`, `date_value`, `time_value`, `file`, `institution_custom_field_id`, `institution_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `text_value`, `number_value`, NULL, `textarea_value`, `date_value`, `time_value`, `file`, `institution_custom_field_id`, `institution_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3516_institution_custom_field_values`;


-- infrastructure_custom_field_values
RENAME TABLE `infrastructure_custom_field_values` TO `z_3516_infrastructure_custom_field_values`;

DROP TABLE IF EXISTS `infrastructure_custom_field_values`;
CREATE TABLE IF NOT EXISTS `infrastructure_custom_field_values` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_value` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `decimal_value` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `textarea_value` text COLLATE utf8mb4_unicode_ci,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob,
  `infrastructure_custom_field_id` int(11) NOT NULL COMMENT 'links to infrastructure_custom_fields.id',
  `institution_infrastructure_id` int(11) NOT NULL COMMENT 'links to institution_infrastructures.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `number_value` (`number_value`),
  KEY `infrastructure_custom_field_id` (`infrastructure_custom_field_id`),
  KEY `institution_infrastructure_id` (`institution_infrastructure_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `infrastructure_custom_field_values` (`id`, `text_value`, `number_value`, `decimal_value`, `textarea_value`, `date_value`, `time_value`, `file`, `infrastructure_custom_field_id`, `institution_infrastructure_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `text_value`, `number_value`, NULL, `textarea_value`, `date_value`, `time_value`, `file`, `infrastructure_custom_field_id`, `institution_infrastructure_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3516_infrastructure_custom_field_values`;


-- room_custom_field_values
RENAME TABLE `room_custom_field_values` TO `z_3516_room_custom_field_values`;

DROP TABLE IF EXISTS `room_custom_field_values`;
CREATE TABLE IF NOT EXISTS `room_custom_field_values` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_value` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `decimal_value` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `textarea_value` text COLLATE utf8mb4_unicode_ci,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob,
  `infrastructure_custom_field_id` int(11) NOT NULL,
  `institution_room_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `number_value` (`number_value`),
  KEY `infrastructure_custom_field_id` (`infrastructure_custom_field_id`),
  KEY `institution_room_id` (`institution_room_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `room_custom_field_values` (`id`, `text_value`, `number_value`, `decimal_value`, `textarea_value`, `date_value`, `time_value`, `file`, `infrastructure_custom_field_id`, `institution_room_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `text_value`, `number_value`, NULL, `textarea_value`, `date_value`, `time_value`, `file`, `infrastructure_custom_field_id`, `institution_room_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3516_room_custom_field_values`;


-- staff_custom_field_values
RENAME TABLE `staff_custom_field_values` TO `z_3516_staff_custom_field_values`;

DROP TABLE IF EXISTS `staff_custom_field_values`;
CREATE TABLE IF NOT EXISTS `staff_custom_field_values` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_value` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `decimal_value` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `textarea_value` text COLLATE utf8mb4_unicode_ci,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob,
  `staff_custom_field_id` int(11) NOT NULL COMMENT 'links to staff_custom_fields.id',
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `number_value` (`number_value`),
  KEY `staff_custom_field_id` (`staff_custom_field_id`),
  KEY `staff_id` (`staff_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `staff_custom_field_values` (`id`, `text_value`, `number_value`, `decimal_value`, `textarea_value`, `date_value`, `time_value`, `file`, `staff_custom_field_id`, `staff_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `text_value`, `number_value`, NULL, `textarea_value`, `date_value`, `time_value`, `file`, `staff_custom_field_id`, `staff_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3516_staff_custom_field_values`;


-- student_custom_field_values
RENAME TABLE `student_custom_field_values` TO `z_3516_student_custom_field_values`;

DROP TABLE IF EXISTS `student_custom_field_values`;
CREATE TABLE IF NOT EXISTS `student_custom_field_values` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_value` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `decimal_value` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `textarea_value` text COLLATE utf8mb4_unicode_ci,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob,
  `student_custom_field_id` int(11) NOT NULL COMMENT 'links to student_custom_fields.id',
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `number_value` (`number_value`),
  KEY `student_custom_field_id` (`student_custom_field_id`),
  KEY `student_id` (`student_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `student_custom_field_values` (`id`, `text_value`, `number_value`, `decimal_value`, `textarea_value`, `date_value`, `time_value`, `file`, `student_custom_field_id`, `student_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `text_value`, `number_value`, NULL, `textarea_value`, `date_value`, `time_value`, `file`, `student_custom_field_id`, `student_id`, `modified`, `created_user_id`, `created`
FROM `z_3516_student_custom_field_values`;


-- institution_survey_answers
RENAME TABLE `institution_survey_answers` TO `z_3516_institution_survey_answers`;

DROP TABLE IF EXISTS `institution_survey_answers`;
CREATE TABLE IF NOT EXISTS `institution_survey_answers` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_value` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `decimal_value` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `textarea_value` text COLLATE utf8mb4_unicode_ci,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob,
  `survey_question_id` int(11) NOT NULL COMMENT 'links to survey_questions.id',
  `institution_survey_id` int(11) NOT NULL COMMENT 'links to institution_surveys.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `number_value` (`number_value`),
  KEY `survey_question_id` (`survey_question_id`),
  KEY `institution_survey_id` (`institution_survey_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the answers to each question in a form';

INSERT INTO `institution_survey_answers` (`id`, `text_value`, `number_value`, `decimal_value`, `textarea_value`, `date_value`, `time_value`, `file`, `survey_question_id`, `institution_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `text_value`, `number_value`, NULL, `textarea_value`, `date_value`, `time_value`, `file`, `survey_question_id`, `institution_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3516_institution_survey_answers`;


-- institution_student_survey_answers
RENAME TABLE `institution_student_survey_answers` TO `z_3516_institution_student_survey_answers`;

DROP TABLE IF EXISTS `institution_student_survey_answers`;
CREATE TABLE IF NOT EXISTS `institution_student_survey_answers` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_value` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `decimal_value` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `textarea_value` text COLLATE utf8mb4_unicode_ci,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob,
  `survey_question_id` int(11) NOT NULL COMMENT 'links to survey_questions.id',
  `institution_student_survey_id` int(11) NOT NULL COMMENT 'links to institution_student_surveys.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `number_value` (`number_value`),
  KEY `survey_question_id` (`survey_question_id`),
  KEY `institution_student_survey_id` (`institution_student_survey_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the student list answers of a survey';

INSERT INTO `institution_student_survey_answers` (`id`, `text_value`, `number_value`, `decimal_value`, `textarea_value`, `date_value`, `time_value`, `file`, `survey_question_id`, `institution_student_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `text_value`, `number_value`, NULL, `textarea_value`, `date_value`, `time_value`, `file`, `survey_question_id`, `institution_student_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3516_institution_student_survey_answers`;


-- institution_repeater_survey_answers
RENAME TABLE `institution_repeater_survey_answers` TO `z_3516_institution_repeater_survey_answers`;

DROP TABLE IF EXISTS `institution_repeater_survey_answers`;
CREATE TABLE IF NOT EXISTS `institution_repeater_survey_answers` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_value` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `decimal_value` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `textarea_value` text COLLATE utf8mb4_unicode_ci,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob,
  `survey_question_id` int(11) NOT NULL COMMENT 'links to survey_questions.id',
  `institution_repeater_survey_id` int(11) NOT NULL COMMENT 'links to institution_repeater_surveys.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `number_value` (`number_value`),
  KEY `survey_question_id` (`survey_question_id`),
  KEY `institution_repeater_survey_id` (`institution_repeater_survey_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains repeater type answers of a survey';

INSERT INTO `institution_repeater_survey_answers` (`id`, `text_value`, `number_value`, `decimal_value`, `textarea_value`, `date_value`, `time_value`, `file`, `survey_question_id`, `institution_repeater_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `text_value`, `number_value`, NULL, `textarea_value`, `date_value`, `time_value`, `file`, `survey_question_id`, `institution_repeater_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3516_institution_repeater_survey_answers`;

