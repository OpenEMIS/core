-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1807');

-- security_functions
UPDATE `security_functions` SET `_execute` = 'Forms.download' WHERE `id` = 5027;

-- institution_site_surveys
ALTER TABLE `institution_site_surveys` CHANGE `status` `status` INT(1) NOT NULL DEFAULT '0' COMMENT '-1 -> Expired, 0 -> New, 1 -> Draft, 2 -> Completed';

-- security_rest_sessions
DROP TABLE IF EXISTS `security_rest_sessions`;
CREATE TABLE IF NOT EXISTS `security_rest_sessions` (
  `id` char(36) NOT NULL,
  `access_token` char(40) NOT NULL,
  `refresh_token` char(40) NOT NULL,
  `expiry_date` datetime NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime NOT NULL,
  `created_user_id` int(11) DEFAULT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `security_rest_sessions`
  ADD PRIMARY KEY (`id`);
