INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3931', NOW());

ALTER TABLE `authentication_type_attributes`
RENAME TO  `z_3931_authentication_type_attributes` ;

CREATE TABLE `system_authentications` (
  `id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `status` INT NOT NULL,
  `authentication_type_id` INT NOT NULL COMMENT 'links to authentication_types.id',
  `authentication_types_id` INT NOT NULL COMMENT 'links to area_administratives.id',
  PRIMARY KEY (`id`),
  INDEX `authentication_type_id` (`authentication_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains user specified authentication';

CREATE TABLE `authentication_types` (
  `id` INT NOT NULL,
  `name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains list of authentication type in the system ';

CREATE TABLE `authentication_type_attributes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `is_mandatory` INT NOT NULL,
  `authentication_type_id` INT NOT NULL COMMENT 'links to authentication_types.id',
  PRIMARY KEY (`id`),
  INDEX `authentication_type_id` (`authentication_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains list of authentication type attributes that the user can map';

CREATE TABLE `system_authentication_type_attributes` (
  `system_authentication_id` INT NOT NULL COMMENT 'links to system_authentications.id',
  `authentication_type_attribute_id` INT NOT NULL COMMENT 'links to authentication_type_attributes.id',
  `value` TEXT NULL,
  PRIMARY KEY (`system_authentication_id`, `authentication_type_attribute_id`),
  INDEX `authentication_type_attribute_id` (`authentication_type_attribute_id`),
  INDEX `system_authentication_id` (`system_authentication_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains list of user defined authentication type attribute mapping';
