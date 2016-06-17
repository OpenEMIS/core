-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2435', NOW());

-- security_functions
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES
(1038, 'Undo Student Status', 'Institutions', 'Institutions', 'Students', 1000, NULL, NULL, NULL, NULL, 'Undo.index|Undo.add|Undo.reconfirm', 1038, 1, 1, '0000-00-00 00:00:00');
