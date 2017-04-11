DELETE FROM labels WHERE module = 'Accounts' AND field = 'password';
DELETE FROM labels WHERE module = 'Accounts' AND field = 'retype_password';
DELETE FROM labels WHERE module = 'StudentAccount' AND field = 'password';
DELETE FROM labels WHERE module = 'StudentAccount' AND field = 'retype_password';
DELETE FROM labels WHERE module = 'StaffAccount' AND field = 'password';
DELETE FROM labels WHERE module = 'StaffAccount' AND field = 'retype_password';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2603';