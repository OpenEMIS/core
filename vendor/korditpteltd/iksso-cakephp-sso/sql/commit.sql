-- authentication_types
CREATE TABLE `authentication_types` (
  `id` INT NOT NULL,
  `name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains list of authentication type in the system ';

INSERT INTO `authentication_types` (`id`, `name`) VALUES (1, 'Google');
INSERT INTO `authentication_types` (`id`, `name`) VALUES (2, 'SAML');
INSERT INTO `authentication_types` (`id`, `name`) VALUES (3, 'OAuth');

-- system_authentications
CREATE TABLE `system_authentications` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `code` CHAR(16) NOT NULL,
  `name` VARCHAR(100) DEFAULT NULL,
  `status` INT NOT NULL,
  `allow_create_user` INT(1) NOT NULL,
  `mapped_username` VARCHAR(50) NOT NULL,
  `mapped_first_name` VARCHAR(50) DEFAULT NULL,
  `mapped_last_name` VARCHAR(50) DEFAULT NULL,
  `mapped_date_of_birth` VARCHAR(50) DEFAULT NULL,
  `mapped_gender` VARCHAR(50) DEFAULT NULL,
  `mapped_role` VARCHAR(50) DEFAULT NULL,
  `mapped_email` VARCHAR(50) DEFAULT NULL,
  `authentication_type_id` INT NOT NULL COMMENT 'links to authentication_types.id',
  PRIMARY KEY (`id`),
  INDEX `authentication_type_id` (`authentication_type_id`),
  UNIQUE INDEX `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains user specified authentication';


-- idp_google - Table for storing authentication related information for Google
CREATE TABLE `idp_google` (
  `system_authentication_id` INT NOT NULL COMMENT 'links to system_authenticatons.id',
  `client_id` VARCHAR(150) NOT NULL,
  `client_secret` VARCHAR(150) NOT NULL,
  `redirect_uri` VARCHAR(150) NOT NULL,
  `hd` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`system_authentication_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains Google authentication attributes';

-- idp_oauth - Table for storing authentication related information for OAuth
CREATE TABLE `idp_oauth` (
  `system_authentication_id` INT NOT NULL COMMENT 'links to system_authenticatons.id',
  `client_id` VARCHAR(150) NOT NULL,
  `client_secret` VARCHAR(150) NOT NULL,
  `redirect_uri` VARCHAR(200) NOT NULL,
  `well_known_uri` VARCHAR(200) DEFAULT NULL,
  `authorization_endpoint` VARCHAR(200) NOT NULL,
  `token_endpoint` VARCHAR(200) NOT NULL,
  `userinfo_endpoint` VARCHAR(200) NOT NULL,
  `issuer` VARCHAR(200) NOT NULL,
  `jwks_uri` VARCHAR(200) NOT NULL,
  PRIMARY KEY (`system_authentication_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains OAuth authentication attributes';

-- idp_saml - Table for storing authentication related information for SAML
CREATE TABLE `idp_saml` (
  `system_authentication_id` INT NOT NULL COMMENT 'links to system_authenticatons.id',
  `idp_entity_id` VARCHAR(200) NOT NULL,
  `idp_sso` VARCHAR(200) NOT NULL,
  `idp_sso_binding` VARCHAR(100) NOT NULL,
  `idp_slo` VARCHAR(200) NOT NULL,
  `idp_slo_binding` VARCHAR(100) NOT NULL,
  `idp_x509cert` TEXT NOT NULL,
  `idp_cert_fingerprint` VARCHAR(100) DEFAULT NULL,
  `idp_cert_fingerprint_algorithm` VARCHAR(10) DEFAULT NULL,
  `sp_entity_id` VARCHAR(200) NOT NULL,
  `sp_acs` VARCHAR(200) NOT NULL,
  `sp_slo` VARCHAR(100) NOT NULL,
  `sp_name_id_format` VARCHAR(100) DEFAULT NULL,
  `sp_private_key` TEXT DEFAULT NULL,
  `sp_metadata` TEXT NOT NULL,
  PRIMARY KEY (`system_authentication_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains SAML authentication attributes';

CREATE TABLE `security_user_logins` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `security_user_id` INT(11) DEFAULT NULL COMMENT 'links to security_users.id',
  `login_date_time` DATETIME DEFAULT NULL,
  `login_period` INT(6) NOT NULL,
  `session_id` VARCHAR(45) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  PRIMARY KEY (`id`, `login_period`),
  KEY `security_user_id` (`security_user_id`),
  KEY `login_date_time` (`login_date_time`),
  KEY `login_period` (`login_period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all user logins'
PARTITION BY HASH(login_period)
PARTITIONS 101;

-- optional configuration for toggling local login
UPDATE `config_items` SET `name`='Enable Local Login', `code`='enable_local_login', `label`='Enable Local Login',
`value`= 1, `default_value`='1', `option_type`='yes_no' WHERE `id`=1001;
