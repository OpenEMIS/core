-- security_role_functions
DELETE FROM `security_role_functions` WHERE `security_function_id` = 3036; -- institution salary_list
DELETE FROM `security_role_functions` WHERE `security_function_id` = 7048; -- directory salary_list

-- security_functions
DELETE FROM `security_functions` WHERE `id` = 3036; -- institution salary_list
UPDATE `security_functions` SET `order` = 3023 WHERE `id` = 3023; -- institution bank_account
UPDATE `security_functions`
    SET `name` = 'Salaries',
        `order` = 3020
    WHERE `id` = 3020; -- institution salary_details


DELETE FROM `security_functions` WHERE `id` = 7048; -- directory salary_list
UPDATE `security_functions`
    SET `name` = 'Salaries',
        `order` = 7034
    WHERE `id` = 7034; -- directory salary_details


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3304';
