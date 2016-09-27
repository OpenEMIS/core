-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3379', NOW());

-- security_functions
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `order`, `visible`, `created_user_id`, `created`) VALUES (1047, 'Contacts', 'Institutions', 'Institutions', 'General', '8', 'Contacts.index|Contacts.view', 'Contacts.edit', 1003, 1, 1, NOW());