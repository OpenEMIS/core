-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3451', NOW());

-- institution_visit_requests
DROP TABLE IF EXISTS `institution_visit_requests`;
CREATE TABLE IF NOT EXISTS `institution_visit_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_of_visit` date NOT NULL,
  `comment` text,
  `file_name` varchar(250) DEFAULT NULL,
  `file_content` longblob,
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `assignee_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to security_users.id',
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `quality_visit_type_id` int(11) NOT NULL COMMENT 'links to quality_visit_types.id',
  `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `status_id` (`status_id`),
  KEY `assignee_id` (`assignee_id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `quality_visit_type_id` (`quality_visit_type_id`),
  KEY `institution_id` (`institution_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all visit requested by the institutions';

-- security_functions
DELETE FROM `security_functions` WHERE `id` = 1047;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (1047, 'Visit Requests', 'Institutions', 'Institutions', 'Quality', 1000, 'VisitRequests.index|VisitRequests.view', 'VisitRequests.edit', 'VisitRequests.add', 'VisitRequests.remove', 'VisitRequests.download', 1047, 1, 1, NOW());
