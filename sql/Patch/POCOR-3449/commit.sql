-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3449', NOW());

CREATE TABLE `staff_training_applications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `training_course_id` int(11) NOT NULL COMMENT 'links to training_courses.id',
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `assignee_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to security_users.id',
  `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`),
  KEY `training_course_id` (`training_course_id`),
  KEY `status_id` (`status_id`),
  KEY `assignee_id` (`assignee_id`),
  KEY `institution_id` (`institution_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the course applications for a particular staff';
