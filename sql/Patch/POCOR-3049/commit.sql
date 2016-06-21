-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3049', NOW());

-- security_functions
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `order`, `visible`, `created_user_id`, `created`) VALUES (5043, 'Rules', 'Surveys', 'Administration', 'Survey', 5000, 'Rules.index', 'Rules.edit', 5028, 1, 1, NOW());

UPDATE `security_functions` SET `order`='5029' WHERE `id`='5028';
