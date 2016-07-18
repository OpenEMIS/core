-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3067', NOW());


-- code here
-- Directoriy module
UPDATE `security_functions` SET `name` = 'Guardian Relation' WHERE id = 7009;
UPDATE `security_functions` SET `order` = 7048 WHERE id = 7009;

UPDATE `security_functions` SET `name` = 'Guardian Profile' WHERE id = 7047;
UPDATE `security_functions` SET `_view` = 'StudentGuardianUser.index|StudentGuardianUser.view' WHERE id = 7047;
UPDATE `security_functions` SET `_edit` = 'StudentGuardianUser.edit' WHERE id = 7047;
UPDATE `security_functions` SET `order` = 7009 WHERE id = 7047;

UPDATE `security_functions` SET `order` = 7047 WHERE id = 7009;

-- institutions module
UPDATE `security_functions` SET `name` = 'Guardian Relation' WHERE id = 2010;
UPDATE `security_functions` SET `order` = 2030 WHERE id = 2010;
UPDATE `security_functions` SET `category` = 'Students - Guardians' WHERE id = 2010;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ('2029', 'Guardian Profile', 'Students', 'Institutions', 'Students - Guardians', '2000', 'GuardianUser.index|GuardianUser.view', 'GuardianUser.edit', 'GuardianUser.add', NULL, NULL, '2002', '1', NULL, NULL, '1', NOW());

UPDATE `security_functions` SET `order` = 2029 WHERE id = 2010;
