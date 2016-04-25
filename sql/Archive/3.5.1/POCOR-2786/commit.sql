-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2786', NOW());

-- security_users
CREATE TABLE `z_2786_security_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gender_id` int(1) NOT NULL,
  `date_of_birth` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `z_2786_security_users` (`id`, `gender_id`, `date_of_birth`)
SELECT `id`, `gender_id`, `date_of_birth` FROM `security_users` WHERE `gender_id` = 0;

SET @genderId := 0;
SELECT `id` INTO @genderId FROM `genders` WHERE `name` = 'Male';

UPDATE `security_users` SET `gender_id` = @genderId WHERE `gender_id` = 0;

INSERT IGNORE INTO `z_2786_security_users` (`id`, `gender_id`, `date_of_birth`)
SELECT `id`, `gender_id`, `date_of_birth` FROM `security_users` WHERE `date_of_birth` = '0000-00-00';

UPDATE `security_users` SET `date_of_birth` = '1900-01-01' WHERE `date_of_birth` = '0000-00-00';