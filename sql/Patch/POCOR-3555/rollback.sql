-- security_functions
UPDATE `security_functions` SET `_edit` = 'StudentAccount.edit' WHERE `name` = 'Accounts' AND `category` = 'Students';
UPDATE `security_functions` SET `_edit` = 'StaffAccount.edit' WHERE `name` = 'Accounts' AND `category` = 'Staff';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3555';
