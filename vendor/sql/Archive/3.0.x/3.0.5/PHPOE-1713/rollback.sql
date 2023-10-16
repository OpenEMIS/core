UPDATE `security_functions` SET `name` = 'Classes' WHERE `id` = 1006;

DELETE FROM `security_functions` WHERE `id` = 1007;

UPDATE `security_functions` SET
`id` = `id` - 1,
`order` = `order` - 1
WHERE `id` > 1006 AND `id` < 2000;

UPDATE `security_role_functions` SET
`security_function_id` = `security_function_id` - 1
WHERE `security_function_id` > 1006 AND `security_function_id` < 2000;
