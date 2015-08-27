-- db_patches
CREATE TABLE IF NOT EXISTS `db_patches` (
  `issue` varchar(15) NOT NULL,
  PRIMARY KEY (`issue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `db_patches` VALUES ('PHPOE-1391');

-- workflow_models
UPDATE `workflow_models` SET `model` = 'Staff.Leaves' WHERE `model` = 'StaffLeave';
