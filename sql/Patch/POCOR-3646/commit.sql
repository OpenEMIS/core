-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3646', NOW());

-- security_users
UPDATE `security_users`
SET `username` = `openemis_no`
WHERE `username` IS NULL;

ALTER TABLE `security_users`
CHANGE COLUMN `username` `username` VARCHAR(50) NOT NULL ;
