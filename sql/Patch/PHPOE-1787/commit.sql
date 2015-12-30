INSERT INTO `db_patches` VALUES ('PHPOE-1787', NOW());

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `created_user_id`, `created`) VALUES 
(uuid(), 'BankAccounts', 'remarks', 'Students -> Bank Accounts | Staff -> Bank Accounts', 'Comments', 1, NOW()),
(uuid(), 'InstitutionBankAccounts', 'remarks', 'Institutions -> Bank Accounts', 'Comments', 1, NOW());
