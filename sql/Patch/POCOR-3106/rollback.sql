-- security functions
ALTER TABLE `security_functions` DROP `description`;
UPDATE `security_functions` SET `_edit` = NULL WHERE `id` = 2011;
-- UPDATE `security_functions`
--     SET `name` = 'Students',
--         `_view` = 'index|view',
--         `_edit` = 'edit',
--         `_add` = 'add',
--         `_delete` = 'remove',
--         `_execute` = 'excel'
--     WHERE `id` = 2000;

-- Translation table
-- DELETE FROM `translations` WHERE `en` = 'Programme edit will only take effect when student edit permission is granted';
-- DELETE FROM `translations` WHERE `en` = 'Overview edit will only take effect when student edit and classes permission is granted';

-- security role functions
UPDATE `security_role_functions` SET `_edit` = 0 WHERE `security_function_id` = 2011;


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3106';
