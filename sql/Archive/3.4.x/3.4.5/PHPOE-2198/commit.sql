-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2198', NOW());

-- procedure
DROP PROCEDURE IF EXISTS updateSecurityRoleFunctions;
DELIMITER $$
CREATE PROCEDURE updateSecurityRoleFunctions()
BEGIN
	UPDATE `security_role_functions`
	SET `_edit` = 1, `modified_user_id` = 1, `modified` = NOW()
	WHERE `security_function_id` = 5023 AND `security_role_id` = (
		SELECT `id` FROM `security_roles` WHERE `name` = 'Group Administrator' AND `security_group_id` = -1
	);
    
	IF ROW_COUNT() = 0 THEN 
		INSERT INTO `security_role_functions` (`_edit`, `security_role_id`, `security_function_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
        VALUES (1, (SELECT `id` FROM `security_roles` WHERE `name` = 'Group Administrator' AND `security_group_id` = -1), 5023, 1, NOW(), 1, NOW());
	END IF;
END;
$$
DELIMITER ;
CALL updateSecurityRoleFunctions;
DROP PROCEDURE IF EXISTS updateSecurityRoleFunctions;