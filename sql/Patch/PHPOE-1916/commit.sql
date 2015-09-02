INSERT INTO `db_patches` VALUES ('PHPOE-1916');

CREATE TABLE `z_1916_academic_periods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `current` char(1) NOT NULL DEFAULT '0',
  `available` char(1) NOT NULL DEFAULT '1',
  `visible` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;

INSERT INTO `z_1916_academic_periods`
SELECT `id`, `current`, `available`, `visible`
FROM `academic_periods`;

ALTER TABLE `academic_periods` 
CHANGE COLUMN `available` `editable` CHAR(1) NOT NULL DEFAULT '1';

Update `academic_periods` SET `editable` = 1, `visible` = 1 WHERE `current` = 1; 