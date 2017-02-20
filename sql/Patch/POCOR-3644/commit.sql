-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3644', NOW());

-- security_functions
UPDATE `security_functions`
SET `order` = `order` + 1
WHERE `order` >= 2018 AND `order` < 3000;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES ('2030', 'Textbooks', 'Institutions', 'Institutions', 'Students - Academic', '2000', 'StudentTextbooks.index|StudentTextbooks.view', NULL, NULL, NULL, NULL, '2018', '1', NULL, NULL, NULL, '1', '2017-02-14 00:00:00');