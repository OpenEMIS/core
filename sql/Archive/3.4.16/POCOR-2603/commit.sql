-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2603', NOW());

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `created_user_id`, `created`) VALUES
(uuid(), 'Accounts', 'password', 'Students -> Accounts | Staff -> Accounts | Security -> Accounts', 'New Password', NULL, NULL, 1, 1, now()),
(uuid(), 'Accounts', 'retype_password', 'Students -> Accounts | Staff -> Accounts | Security -> Accounts', 'Retype New Password', NULL, NULL, 1, 1, now()),
(uuid(), 'StudentAccount', 'password', 'Institution -> Students -> Accounts', 'New Password', NULL, NULL, 1, 1, now()),
(uuid(), 'StudentAccount', 'retype_password', 'Institution -> Students -> Accounts', 'Retype New Password', NULL, NULL, 1, 1, now()),
(uuid(), 'StaffAccount', 'password', 'Institution -> Staff -> Accounts', 'New Password', NULL, NULL, 1, 1, now()),
(uuid(), 'StaffAccount', 'retype_password', 'Institution -> Staff -> Accounts', 'Retype New Password', NULL, NULL, 1, 1, now())
;