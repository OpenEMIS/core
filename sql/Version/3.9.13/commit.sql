-- POCOR-3880
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


-- 3.9.13
UPDATE config_items SET value = '3.9.13' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
