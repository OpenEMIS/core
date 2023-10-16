-- db_patches
CREATE TABLE IF NOT EXISTS `db_patches` (
  `issue` varchar(15) NOT NULL,
  PRIMARY KEY (`issue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `db_patches` VALUES ('PHPOE-1857');

-- labels
DELETE FROM `labels` WHERE `module` = 'StudentPromotion';

INSERT INTO `labels` (`module`, `field`, `en`, `created_user_id`, `created`) VALUES ('StudentPromotion', 'openemis_no', 'OpenEMIS ID', 1, NOW());
INSERT INTO `labels` (`module`, `field`, `en`, `created_user_id`, `created`) VALUES ('StudentPromotion', 'student_id', 'Student', 1, NOW());
INSERT INTO `labels` (`module`, `field`, `en`, `created_user_id`, `created`) VALUES ('StudentPromotion', 'education_grade_id', 'Next Grade', 1, NOW());
