-- guidance_types
DROP TABLE `guidance_types`;

-- institution_counsellings
DROP TABLE `institution_counsellings`;

-- security_functions
DELETE FROM security_functions WHERE id = 1061;

-- security_role_functions
DELETE FROM security_role_functions WHERE security_function_id = 1061;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3953';
