-- workflow_statuses
ALTER TABLE `workflow_statuses` 
DROP COLUMN `created`,
DROP COLUMN `created_user_id`,
DROP COLUMN `modified`,
DROP COLUMN `modified_user_id`,
DROP COLUMN `code`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2233';