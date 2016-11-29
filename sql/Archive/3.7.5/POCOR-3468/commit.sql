-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3468', NOW());

-- config_product_lists
ALTER TABLE `config_product_lists`
ADD COLUMN `deletable` INT(1) NOT NULL DEFAULT 1 AFTER `url`,
ADD COLUMN `file_name` VARCHAR(250) NULL AFTER `deletable`,
ADD COLUMN `file_content` LONGBLOB NULL AFTER `file_name`;

UPDATE `config_product_lists`
SET `deletable` = 0;
