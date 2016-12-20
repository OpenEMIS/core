-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3588', NOW());

-- examination_items
RENAME TABLE `examination_items` TO `z_3588_examination_items`;

DROP TABLE IF EXISTS `examination_items`;
CREATE TABLE IF NOT EXISTS `examination_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `code` varchar(20) NOT NULL,
  `weight` decimal(6,2) DEFAULT '0.00',
  `examination_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `examination_id` int(11) NOT NULL COMMENT 'links to examinations.id',
  `education_subject_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to education_subjects.id',
  `examination_grading_type_id` int(11) NOT NULL COMMENT 'links to examination_grading_types.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `examination_id` (`examination_id`),
  KEY `education_subject_id` (`education_subject_id`),
  KEY `examination_grading_type_id` (`examination_grading_type_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the examination subjects for a particular examination';

INSERT INTO `examination_items` (`name`, `code`, `weight`, `examination_date`, `start_time`, `end_time`, `examination_id`, `education_subject_id`, `examination_grading_type_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `Subjects`.`name`, `Subjects`.`code`, `Items`.`weight`, `Items`.`examination_date`, `Items`.`start_time`, `Items`.`end_time`, `Items`.`examination_id`, `Items`.`education_subject_id`, `Items`.`examination_grading_type_id`, `Items`.`modified_user_id`, `Items`.`modified`, `Items`.`created_user_id`, `Items`.`created`
FROM `z_3588_examination_items` `Items`
LEFT JOIN `education_subjects` `Subjects`
ON `Subjects`.`id` = `Items`.`education_subject_id`;
