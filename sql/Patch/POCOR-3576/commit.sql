-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3576', NOW());

-- excel_templates
DROP TABLE IF EXISTS `excel_templates`;
CREATE TABLE IF NOT EXISTS `excel_templates` (
  `id` int(11) NOT NULL,
  `module` varchar(100) NOT NULL,
  `file_name` varchar(250) DEFAULT NULL,
  `file_type` varchar (255) DEFAULT NULL,
  `file_content` longblob,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains template for a specific report';
