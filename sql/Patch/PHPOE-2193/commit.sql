-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2193', NOW());

-- `user_activities`
CREATE TABLE `user_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model` varchar(200) NOT NULL,
  `model_reference` int(11) NOT NULL,
  `field` varchar(200) NOT NULL,
  `field_type` varchar(128) NOT NULL,
  `old_value` varchar(255) NOT NULL,
  `new_value` varchar(255) NOT NULL,
  `operation` varchar(10) NOT NULL,
  `security_user_id` int(11) NOT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `model_reference` (`model_reference`),
  KEY `security_user_id` (`security_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- student_activities
ALTER TABLE `student_activities` 
RENAME TO  `z_2193_student_activities` ;

-- staff_activites
ALTER TABLE `staff_activities` 
RENAME TO  `z_2193_staff_activities` ;

-- guardian_activities
ALTER TABLE `guardian_activities` 
RENAME TO  `z_2193_guardian_activities` ;