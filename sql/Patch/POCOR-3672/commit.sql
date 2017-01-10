-- system_patches
INSERT INTO `system_patches` ('issue', 'created') VALUES ('POCOR-3672', NOW());

-- security_user_logins
ALTER TABLE `security_user_logins`
CHANGE COLUMN `security_user_id` `security_user_id` INT(11) NOT NULL COMMENT 'links to security_users.id' ,
CHANGE COLUMN `login_date_time` `login_date_time` DATETIME NOT NULL ,
ADD COLUMN `session_id` VARCHAR(45) NOT NULL AFTER `login_date_time`,
ADD COLUMN `ip_address` VARCHAR(45) NOT NULL AFTER `session_id`;

-- single_logout
CREATE TABLE `single_logout` (
  `username` VARCHAR(256) NOT NULL,
  `url` VARCHAR(256) NOT NULL,
  `session_id` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`username`, `url`, `session_id`),
  INDEX `username` (`username`),
  INDEX `url` (`url`),
  INDEX `session_id` (`session_id`));

-- config_product_list
ALTER TABLE `config_product_lists`
ADD COLUMN `auto_login_url` TEXT NULL AFTER `url`,
ADD COLUMN `auto_logout_url` TEXT NULL AFTER `auto_login_url`;
