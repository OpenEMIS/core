-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-2760', NOW());

UPDATE `security_functions` SET _delete = NULL WHERE id = 5003;
-- SELECT * FROM `security_functions` WHERE id = 5003;

-- BACKING UP
CREATE TABLE z_2760_security_role_functions LIKE security_role_functions;
INSERT INTO z_2760_security_role_functions SELECT * FROM security_role_functions WHERE security_function_id = 5003;

-- DELETING ASSOCIATED RECORDS
UPDATE security_role_functions SET _delete = 0 WHERE security_function_id = 5003;