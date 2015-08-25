-- db_patches
CREATE TABLE IF NOT EXISTS `db_patches` (
  `issue` varchar(15) NOT NULL,
  PRIMARY KEY (`issue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `db_patches` VALUES ('PHPOE-1807');

-- security_functions
UPDATE `security_functions` SET `_execute` = 'Forms.download' WHERE `id` = 5027;

-- institution_site_surveys
ALTER TABLE `institution_site_surveys` CHANGE `status` `status` INT(1) NOT NULL DEFAULT '0' COMMENT '-1 -> Expired, 0 -> New, 1 -> Draft, 2 -> Completed';
