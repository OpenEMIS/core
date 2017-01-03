-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3683', NOW());

-- assessment_periods
RENAME TABLE `assessment_periods` TO `z_3683_assessment_periods`;

DROP TABLE IF EXISTS `assessment_periods`;
CREATE TABLE IF NOT EXISTS `assessment_periods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `date_enabled` date NOT NULL,
  `date_disabled` date NOT NULL,
  `weight` decimal(6,2) DEFAULT '0.00',
  `academic_term` varchar(250) DEFAULT NULL,
  `assessment_id` int(11) NOT NULL COMMENT 'links to assessments.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `assessment_id` (`assessment_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of periods for a specific assessment';

INSERT INTO `assessment_periods` (`id`, `code`, `name`, `start_date`, `end_date`, `date_enabled`, `date_disabled`, `weight`, `academic_term`, `assessment_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `code`, `name`, `start_date`, `end_date`, `date_enabled`, `date_disabled`, `weight`, NULL, `assessment_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_3683_assessment_periods`;
