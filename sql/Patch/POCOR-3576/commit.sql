-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3576', NOW());

-- report_templates
DROP TABLE IF EXISTS `report_templates`;
CREATE TABLE IF NOT EXISTS `report_templates` (
  `id` int(11) NOT NULL,
  `model` varchar(100) NOT NULL,
  `file_name` varchar(250) DEFAULT NULL,
  `file_type` varchar (255) DEFAULT NULL,
  `file_content` longblob,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains template for a specific report';

INSERT INTO `report_templates` (`id`, `model`, `file_name`, `file_type`, `file_content`) VALUES
(1, 'Institution.AssessmentResults', NULL, NULL, NULL);
