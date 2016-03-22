-- workflow_statuses
ALTER TABLE `workflow_statuses` 
DROP COLUMN `created`,
DROP COLUMN `created_user_id`,
DROP COLUMN `modified`,
DROP COLUMN `modified_user_id`,
DROP COLUMN `is_removable`,
DROP COLUMN `is_editable`,
DROP COLUMN `code`;

-- workflow_statuses_steps
ALTER TABLE `workflow_statuses_steps`
RENAME TO `workflow_status_mappings`;

-- security_functions
DELETE FROM `security_functions` WHERE `id` = 5038;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2233';

UPDATE `config_items` SET `value` = '3.2.9' WHERE `code` = 'db_version';
