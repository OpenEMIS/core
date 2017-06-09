-- security_users
-- No rollback due to size of the records

ALTER TABLE `security_users`
CHANGE COLUMN `username` `username` VARCHAR(50) NULL ;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3646';
