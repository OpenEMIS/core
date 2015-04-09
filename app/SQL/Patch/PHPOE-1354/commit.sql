DROP TABLE IF EXISTS `security_rest_sessions`;
CREATE TABLE `security_rest_sessions` (
  `id` char(36) NOT NULL,
  `access_token` char(40) NOT NULL,
  `refresh_token` char(40) NOT NULL,
  `expiry_date` datetime NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime NOT NULL,
  `created_user_id` int(11) DEFAULT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;