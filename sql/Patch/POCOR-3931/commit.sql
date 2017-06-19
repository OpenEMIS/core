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
  `name` VARCHAR(100) NULL,
  `authentication_type_id` INT NOT NULL COMMENT 'links to authentication_types.id',
  `status` INT NOT NULL,
  `mapped_username` VARCHAR(50) NOT NULL,
  `allow_create_user` INT(1) NOT NULL,
  `mapped_first_name` VARCHAR(50) NULL,
  `mapped_last_name` VARCHAR(50) NULL,
  `mapped_date_of_birth` VARCHAR(50) NULL,
  `mapped_gender` VARCHAR(50) NULL,
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
  PRIMARY KEY (`system_authentication_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains SAML authentication attributes';

INSERT INTO `authentication_types` (`id`, `name`) VALUES (1, 'Google');
INSERT INTO `authentication_types` (`id`, `name`) VALUES (2, 'SAML');
INSERT INTO `authentication_types` (`id`, `name`) VALUES (3, 'OAuth');
