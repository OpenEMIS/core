-- security_role_functions
DELETE FROM `security_role_functions` WHERE `security_function_id` = 3036; -- institution salary_list
DELETE FROM `security_role_functions` WHERE `security_function_id` = 7048; -- directory salary_list


-- security_functions
-- salaries institution
DELETE FROM `security_functions` WHERE `id` = 3036; -- institution salary_list

UPDATE `security_functions` SET `name` = 'Salaries' WHERE `id` = 3020;
UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` BETWEEN 3022 AND 3024;

UPDATE `security_functions` SET `order` = 3024 WHERE `id` = 3023; -- Bank Account
UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` BETWEEN 3021 AND 3036;


-- salaries directory
DELETE FROM `security_functions` WHERE `id` = 7048; -- directory salary_list

UPDATE `security_functions` SET `name` = 'Salaries' WHERE `id` = 7034;
UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` BETWEEN 7035 AND 7048;


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3304';
