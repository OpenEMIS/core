-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3720', NOW());

-- assessment_items
RENAME TABLE `assessment_items` TO `z_3720_assessment_items`;

DROP TABLE IF EXISTS `assessment_items`;
CREATE TABLE IF NOT EXISTS `assessment_items` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `weight` decimal(6,2) DEFAULT '0.00',
  `classification` varchar(250) DEFAULT NULL,
  `assessment_id` int(11) NOT NULL COMMENT 'links to assessments.id',
  `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `assessment_id` (`assessment_id`),
  INDEX `education_subject_id` (`education_subject_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of assessment items for a specific assessment';

INSERT INTO `assessment_items` (`id`, `weight`, `classification`, `assessment_id`, `education_subject_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `weight`, NULL, `assessment_id`, `education_subject_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_3720_assessment_items`;
