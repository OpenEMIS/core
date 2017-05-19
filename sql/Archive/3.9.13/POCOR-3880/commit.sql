INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3880', NOW());

DELIMITER $$

DROP PROCEDURE IF EXISTS addRoleMapping

$$
CREATE PROCEDURE addRoleMapping()
BEGIN
    DECLARE checkOAuthExists INT(11);
    DECLARE checkSamlExists INT(11);
    SET checkOAuthExists = 0;
    SET checkSamlExists = 0;

    SELECT COUNT(*) INTO checkOAuthExists FROM authentication_type_attributes WHERE authentication_type = 'OAuth2OpenIDConnect';
    SELECT COUNT(*) INTO checkSamlExists FROM authentication_type_attributes WHERE authentication_type = 'Saml2';

    IF checkOAuthExists > 0 THEN
        INSERT INTO authentication_type_attributes (id, authentication_type, attribute_field, attribute_name, `value`, created, created_user_id) VALUES (uuid(), 'OAuth2OpenIDConnect', 'role_mapping', 'Role Mapping', '', NOW(), 1);
    END IF;
    IF checkSamlExists > 0 THEN
        INSERT INTO authentication_type_attributes (id, authentication_type, attribute_field, attribute_name, `value`, created, created_user_id) VALUES (uuid(), 'Saml2', 'saml_role_mapping', 'Role Mapping', '', NOW(), 1);
    END IF;
END;
$$

CALL addRoleMapping
$$

DROP PROCEDURE IF EXISTS addRoleMapping
$$

DELIMITER ;
