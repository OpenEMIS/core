-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2233', NOW());

-- workflow_statuses
ALTER TABLE `workflow_statuses` 
ADD COLUMN `code` VARCHAR(50) NOT NULL COMMENT '' AFTER `id`,
CHANGE COLUMN `name` `name` VARCHAR(150) NOT NULL COMMENT '' AFTER `code`,
ADD COLUMN `is_editable` INT(1) NOT NULL COMMENT '' AFTER `name`,
ADD COLUMN `is_removable` INT(1) NOT NULL COMMENT '' AFTER `is_editable`,
ADD COLUMN `modified_user_id` INT(11) NULL DEFAULT NULL COMMENT '' AFTER `workflow_model_id`,
ADD COLUMN `modified` DATETIME NULL DEFAULT NULL COMMENT '' AFTER `modified_user_id`,
ADD COLUMN `created_user_id` INT(11) NOT NULL COMMENT '' AFTER `modified`,
ADD COLUMN `created` DATETIME NOT NULL COMMENT '' AFTER `created_user_id`;

UPDATE `workflow_statuses` SET `code`='COMPLETED', `created_user_id`=1, `created`=NOW() WHERE `id`=1;
UPDATE `workflow_statuses` SET `code`='NOT_COMPLETED', `created_user_id`=1, `created`=NOW() WHERE `id`=2;

-- workflow_statuses_steps
ALTER TABLE `workflow_status_mappings` 
RENAME TO  `workflow_statuses_steps` ;

-- security_functions
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `order`, `visible`, `created_user_id`, `created`) 
VALUES (5038, 'Statuses', 'Workflows', 'Administration', 'Workflows', 5000, 'Statuses.index|Statuses.view', 'Statuses.edit', 'Statuses.add', 'Statuses.remove', 5038, 1, 1, NOW());

UPDATE `config_items` SET `value` = '3.2.10' WHERE `code` = 'db_version';
