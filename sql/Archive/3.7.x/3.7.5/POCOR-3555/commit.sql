-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3555', NOW());

-- security_functions
UPDATE `security_functions` SET `order` = '1030' WHERE `id` = '1029';
UPDATE `security_functions` SET `order` = '1031' WHERE `id` = '1030';
UPDATE `security_functions` SET `order` = '1032' WHERE `id` = '1031';
UPDATE `security_functions` SET `order` = '1033' WHERE `id` = '1032';
UPDATE `security_functions` SET `order` = '1034' WHERE `id` = '1033';
UPDATE `security_functions` SET `order` = '1038' WHERE `id` = '1035';
UPDATE `security_functions` SET `order` = '1040' WHERE `id` = '1036';
UPDATE `security_functions` SET `order` = '1049' WHERE `id` = '1038';

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_edit`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1049, 'Account Username', 'Institutions', 'Institutions', 'Students', 1012, 'StudentAccountUsername.edit', 1037, 1, NULL, NULL, 1, NOW()),
(1050, 'Account Username', 'Institutions', 'Institutions', 'Staff', 1016, 'StaffAccountUsername.edit', 1035, 1, NULL, NULL, 1, NOW());
