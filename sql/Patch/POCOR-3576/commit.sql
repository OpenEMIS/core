-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3576', NOW());

-- excel_templates
DROP TABLE IF EXISTS `excel_templates`;
CREATE TABLE IF NOT EXISTS `excel_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module` varchar(100) NOT NULL,
  `file_name` varchar(250) DEFAULT NULL,
  `file_content` longblob,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains excel template for a specific report';

-- Labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('ad8fa33a-c0d8-11e6-90e8-525400b263eb', 'ExcelTemplates', 'file_content', 'CustomExcels -> ExcelTemplates', 'Attachment', NULL, NULL, 1, NULL, NULL, 1, NOW());

-- security_functions
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(5059, 'Excel Templates', 'CustomExcels', 'Administration', 'CustomExcels', '5000', 'ExcelTemplates.index|ExcelTemplates.view', 'ExcelTemplates.edit', NULL, NULL, 'ExcelTemplates.download', 5059, 1, NULL, NULL, NULL, 1, NOW());
