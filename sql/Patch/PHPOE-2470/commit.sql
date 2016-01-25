-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2470', NOW());

-- reports
CREATE TABLE `reports` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '',
  `name` VARCHAR(50) NOT NULL COMMENT '',
  `target` VARCHAR(50) NULL COMMENT '',
  `query` TEXT NOT NULL COMMENT '',
  `report_type` INT NOT NULL COMMENT '1 => Standard Report, 2 => Custom Report',
  `modified_user_id` INT NULL COMMENT '',
  `modified` INT NULL COMMENT '',
  `created_user_id` INT NOT NULL COMMENT '',
  `created` INT NOT NULL COMMENT '',
  PRIMARY KEY (`id`));