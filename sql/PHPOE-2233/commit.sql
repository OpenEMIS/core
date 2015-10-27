-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2233', NOW());

-- workflow_statuses
ALTER TABLE `workflow_statuses` 
ADD COLUMN `code` VARCHAR(100) NOT NULL COMMENT '' AFTER `workflow_model_id`,
ADD COLUMN `modified_user_id` INT(11) NULL DEFAULT NULL COMMENT '' AFTER `name`,
ADD COLUMN `modified` DATETIME NULL DEFAULT NULL COMMENT '' AFTER `modified_user_id`,
ADD COLUMN `created_user_id` INT(11) NOT NULL COMMENT '' AFTER `modified`,
ADD COLUMN `created` DATETIME NOT NULL COMMENT '' AFTER `created_user_id`;

UPDATE `workflow_statuses` SET `code`='COMPLETED', `created_user_id`=1, `created`=NOW() WHERE `id`=1;
UPDATE `workflow_statuses` SET `code`='NOT_COMPLETED', `created_user_id`=1, `created`=NOW() WHERE `id`=2;