-- labels
DELETE FROM `labels` WHERE `module` = 'BankAccounts' AND `field` = 'remarks';
DELETE FROM `labels` WHERE `module` = 'InstitutionBankAccounts' AND `field` = 'remarks';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1787';
