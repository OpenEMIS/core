-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3646', NOW());

-- security_users
UPDATE `security_users`
SET `username` = `openemis_no`
WHERE `username` IS NULL;

ALTER TABLE `security_users`
CHANGE COLUMN `username` `username` VARCHAR(100) NOT NULL ;

UPDATE `security_functions` SET `_add`='StudentUser.add|getUniqueOpenemisId|generatePassword' WHERE `id`=1043;
UPDATE `security_functions` SET `_add`='StaffUser.add|getUniqueOpenemisId|generatePassword' WHERE `id`=1044;

