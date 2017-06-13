-- security_users
-- No rollback due to size of the records

ALTER TABLE `security_users`
CHANGE COLUMN `username` `username` VARCHAR(50) NULL ;

UPDATE `security_functions` SET `_add`='StudentUser.add|getUniqueOpenemisId' WHERE `id`=1043;
UPDATE `security_functions` SET `_add`='StaffUser.add|getUniqueOpenemisId' WHERE `id`=1044;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3646';
