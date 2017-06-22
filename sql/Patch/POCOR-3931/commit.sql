INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3931', NOW());

ALTER TABLE `authentication_type_attributes`
RENAME TO  `z_3931_authentication_type_attributes` ;

CREATE TABLE `authentication_types` (
  `id` INT NOT NULL,
  `name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains list of authentication type in the system ';

CREATE TABLE `system_authentications` (
  `id` INT NOT NULL,
  `code` CHAR(16) NOT NULL,
  `name` VARCHAR(100) NULL,
  `authentication_type_id` INT NOT NULL COMMENT 'links to authentication_types.id',
  `status` INT NOT NULL,
  `mapped_username` VARCHAR(50) NOT NULL,
  `allow_create_user` INT(1) NOT NULL,
  `mapped_first_name` VARCHAR(50) NULL,
  `mapped_last_name` VARCHAR(50) NULL,
  `mapped_date_of_birth` VARCHAR(50) NULL,
  `mapped_gender` VARCHAR(50) NULL,
  `mapped_role` VARCHAR(50) NULL,
  PRIMARY KEY (`id`),
  INDEX `authentication_type_id` (`authentication_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains user specified authentication';

CREATE TABLE `idp_google` (
  `system_authentication_id` INT NOT NULL COMMENT 'links to system_authenticatons.id',
  `client_id` VARCHAR(150) NOT NULL,
  `client_secret` VARCHAR(150) NOT NULL,
  `redirect_uri` VARCHAR(150) NOT NULL,
  `hd` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`system_authentication_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains Google authentication attributes';

CREATE TABLE `idp_oauth` (
  `system_authentication_id` INT NOT NULL COMMENT 'links to system_authenticatons.id',
  `client_id` VARCHAR(150) NOT NULL,
  `client_secret` VARCHAR(150) NOT NULL,
  `redirect_uri` VARCHAR(200) NOT NULL,
  `well-known_uri` VARCHAR(200) NULL,
  `authorization_endpoint` VARCHAR(200) NOT NULL,
  `token_endpoint` VARCHAR(200) NOT NULL,
  `userinfo_endpoint` VARCHAR(200) NOT NULL,
  `issuer` VARCHAR(200) NOT NULL,
  `jwks_uri` VARCHAR(200) NOT NULL,
  PRIMARY KEY (`system_authentication_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains OAuth authentication attributes';

CREATE TABLE `idp_saml` (
  `system_authentication_id` INT NOT NULL COMMENT 'links to system_authenticatons.id',
  `idp_entity_id` VARCHAR(200) NOT NULL,
  `idp_sso` VARCHAR(200) NOT NULL,
  `idp_sso_binding` VARCHAR(100) NOT NULL,
  `idp_slo` VARCHAR(200) NOT NULL,
  `idp_slo_binding` VARCHAR(100) NOT NULL,
  `idp_x509cert` TEXT NOT NULL,
  `idp_cert_fingerprint` VARCHAR(100) NULL,
  `idp_cert_fingerprint_algorithm` VARCHAR(10) NULL,
  `sp_entity_id` VARCHAR(200) NOT NULL,
  `sp_acs` VARCHAR(200) NOT NULL,
  `sp_slo` VARCHAR(100) NOT NULL,
  `sp_name_id_format` VARCHAR(100) NULL,
  `sp_private_key` TEXT NULL,
  `sp_metadata` TEXT NOT NULL,
  PRIMARY KEY (`system_authentication_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains SAML authentication attributes';

INSERT INTO `authentication_types` (`id`, `name`) VALUES (1, 'Google');
INSERT INTO `authentication_types` (`id`, `name`) VALUES (2, 'SAML');
INSERT INTO `authentication_types` (`id`, `name`) VALUES (3, 'OAuth');

SET @id = 1;

INSERT IGNORE INTO system_authentications
SELECT
  @id,
  CONCAT('IDP', LEFT(MD5(1), 13)),
    (SELECT DISTINCT authentication_type FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Google'),
    1 as `authentication_type_id`,
    1 as `status`,
    'email' as mapped_username,
    (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Google' AND attribute_field = 'allow_create_user') as allow_create_user,
    null as mapped_first_name,
    null as mapped_last_name,
    null as mapped_date_of_birth,
    null as mapped_gender,
    null as mapped_role;

INSERT IGNORE INTO idp_google
Select
  @id as system_authentication_id,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Google' AND attribute_field = 'client_id') as client_id,
    (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Google' AND attribute_field = 'client_secret') as client_secret,
    (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Google' AND attribute_field = 'redirect_uri') as redirect_uri,
    (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Google' AND attribute_field = 'hd') as hd
    ;

SET @id = 1 + (SELECT COUNT(id) FROM system_authentications);

INSERT IGNORE INTO system_authentications
SELECT
  @id,
  CONCAT('IDP', LEFT(MD5(2), 13)),
  (SELECT DISTINCT authentication_type FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Saml2'),
  2 as `authentication_type_id`,
  1 as `status`,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Saml2' AND attribute_field = 'saml_username_mapping') as mapped_username,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Saml2' AND attribute_field = 'allow_create_user') as allow_create_user,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Saml2' AND attribute_field = 'saml_first_name_mapping') as mapped_first_name,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Saml2' AND attribute_field = 'saml_last_name_mapping') as mapped_last_name,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Saml2' AND attribute_field = 'saml_date_of_birth_mapping') as mapped_date_of_birth,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Saml2' AND attribute_field = 'saml_gender_mapping') as mapped_gender,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Saml2' AND attribute_field = 'saml_role_mapping') as mapped_role;

INSERT IGNORE INTO idp_saml
Select
  @id as system_authentication_id,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Saml2' AND attribute_field = 'idp_entity_id') as idp_entity_id,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Saml2' AND attribute_field = 'idp_sso') as idp_sso,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Saml2' AND attribute_field = 'idp_sso_binding') as idp_sso_binding,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Saml2' AND attribute_field = 'idp_slo') as idp_slo,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Saml2' AND attribute_field = 'idp_slo_binding') as idp_slo_binding,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Saml2' AND attribute_field = 'idp_x509cert') as idp_x509cert,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Saml2' AND attribute_field = 'idp_certFingerprint') as idp_cert_fingerprint,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Saml2' AND attribute_field = 'idp_certFingerprintAlgorithm') as idp_cert_fingerprint_algorithm,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Saml2' AND attribute_field = 'sp_entity_id') as sp_entity_id,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Saml2' AND attribute_field = 'sp_acs') as sp_acs,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Saml2' AND attribute_field = 'sp_slo') as sp_slo,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Saml2' AND attribute_field = 'sp_name_id_format') as sp_name_id_format,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Saml2' AND attribute_field = 'sp_privateKey') as sp_privateKey,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'Saml2' AND attribute_field = 'sp_metadata') as sp_metadata;

SET @id = 1 + (SELECT COUNT(id) FROM system_authentications);

INSERT IGNORE INTO system_authentications
SELECT
  @id,
  CONCAT('IDP', LEFT(MD5(3), 13)),
  (SELECT DISTINCT authentication_type FROM z_3931_authentication_type_attributes WHERE authentication_type = 'OAuth2OpenIDConnect'),
  3 as `authentication_type_id`,
  1 as `status`,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'OAuth2OpenIDConnect' AND attribute_field = 'username_mapping') as mapped_username,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'OAuth2OpenIDConnect' AND attribute_field = 'allow_create_user') as allow_create_user,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'OAuth2OpenIDConnect' AND attribute_field = 'firstName_mapping') as mapped_first_name,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'OAuth2OpenIDConnect' AND attribute_field = 'lastName_mapping') as mapped_last_name,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'OAuth2OpenIDConnect' AND attribute_field = 'dob_mapping') as mapped_date_of_birth,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'OAuth2OpenIDConnect' AND attribute_field = 'gender_mapping') as mapped_gender,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'OAuth2OpenIDConnect' AND attribute_field = 'role_mapping') as mapped_role;

INSERT IGNORE INTO idp_oauth
Select
  @id as system_authentication_id,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'OAuth2OpenIDConnect' AND attribute_field = 'client_id') as client_id,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'OAuth2OpenIDConnect' AND attribute_field = 'client_secret') as client_secret,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'OAuth2OpenIDConnect' AND attribute_field = 'redirect_uri') as redirect_uri,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'OAuth2OpenIDConnect' AND attribute_field = 'openid_configuration') as `well-known_uri`,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'OAuth2OpenIDConnect' AND attribute_field = 'auth_uri') as authorization_endpoint,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'OAuth2OpenIDConnect' AND attribute_field = 'token_uri') as token_endpoint,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'OAuth2OpenIDConnect' AND attribute_field = 'userInfo_uri') as userinfo_endpoint,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'OAuth2OpenIDConnect' AND attribute_field = 'issuer') as issuer,
  (SELECT `value` FROM z_3931_authentication_type_attributes WHERE authentication_type = 'OAuth2OpenIDConnect' AND attribute_field = 'jwk_uri') as jwks_uri;
    ;
