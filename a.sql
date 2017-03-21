-- security_user_sessions
CREATE TABLE `security_user_sessions` (
  `id` VARCHAR(40) NOT NULL default '',
  `username` VARCHAR(50) NOT NULL default '',
  PRIMARY KEY (`id`, `username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of user sessions';
