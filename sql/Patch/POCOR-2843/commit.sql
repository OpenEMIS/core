-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2843', NOW());

-- security_rest_sessions
ALTER TABLE `security_rest_sessions` 
RENAME TO  `z_2843_security_rest_sessions` ;

CREATE TABLE `security_rest_sessions` (
  `id` char(36) NOT NULL,
  `access_token` char(40) NOT NULL,
  `refresh_token` char(40) NOT NULL,
  `expiry_date` datetime NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;