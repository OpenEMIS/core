-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2470', NOW());

-- standard_reports
CREATE TABLE `standard_reports` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '',
  `name` VARCHAR(50) NOT NULL COMMENT '',
  `target` VARCHAR(50) NULL COMMENT '',
  `main_table` VARCHAR(50) NOT NULL COMMENT '',
  `table_alias` VARCHAR(50) NOT NULL COMMENT '',
  `query` TEXT NOT NULL COMMENT '',
  `modified_user_id` INT NULL COMMENT '',
  `modified` INT NULL COMMENT '',
  `created_user_id` INT NOT NULL COMMENT '',
  `created` INT NOT NULL COMMENT '',
  PRIMARY KEY (`id`)  COMMENT '');

-- custom_reports
CREATE TABLE `custom_reports` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '',
  `name` VARCHAR(50) NOT NULL COMMENT '',
  `target` VARCHAR(50) NULL COMMENT '',
  `main_table` VARCHAR(50) NOT NULL COMMENT '',
  `table_alias` VARCHAR(50) NOT NULL COMMENT '',
  `query` TEXT NOT NULL COMMENT '',
  `modified_user_id` INT NULL COMMENT '',
  `modified` INT NULL COMMENT '',
  `created_user_id` INT NOT NULL COMMENT '',
  `created` INT NOT NULL COMMENT '',
  PRIMARY KEY (`id`)  COMMENT '');