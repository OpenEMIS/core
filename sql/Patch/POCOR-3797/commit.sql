-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3797', NOW());

-- competency_criterias
RENAME TABLE `competency_criterias` TO `z_3797_competency_criterias`;

DROP TABLE IF EXISTS `competency_criterias`;
CREATE TABLE `competency_criterias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `competency_item_id` int(11) NOT NULL COMMENT 'links to competency_items.id',
  `competency_template_id` int(11) NOT NULL COMMENT 'links to competency_templates.id',
  `competency_grading_type_id` int(11) NOT NULL COMMENT 'links to competency_grading_types.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`,`academic_period_id`,`competency_item_id`,`competency_template_id`),
  KEY `id` (`id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `competency_item_id` (`competency_item_id`),
  KEY `competency_template_id` (`competency_template_id`),
  KEY `competency_grading_type_id` (`competency_grading_type_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of competency criterias for a given competency item';

INSERT INTO `competency_criterias` (`id`, `code`, `name`, `academic_period_id`, `competency_item_id`, `competency_template_id`, `competency_grading_type_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, NULL, `name`, `academic_period_id`, `competency_item_id`, `competency_template_id`, `competency_grading_type_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_3797_competency_criterias`;
