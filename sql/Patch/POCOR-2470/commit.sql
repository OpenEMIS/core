-- system_patches
INSERT INTO `system_patches` VALUES ('POCOR-2470', NOW());

-- reports
DROP TABLE IF EXISTS `reports`;
CREATE TABLE IF NOT EXISTS `reports` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
 `query` text COLLATE utf8mb4_unicode_ci NOT NULL,
 `filter` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 `excel_template_name` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
 `excel_template` longblob NOT NULL,
 `format` int(1) NOT NULL DEFAULT 1 COMMENT '1 -> Excel',
 `modified_user_id` int(11) DEFAULT NULL,
 `modified` datetime DEFAULT NULL,
 `created_user_id` int(11) NOT NULL,
 `created` datetime NOT NULL,
 PRIMARY KEY (`id`),
 KEY `modified_user_id` (`modified_user_id`),
 KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the information for the custom reports';
