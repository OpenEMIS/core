-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1227', NOW());

-- Add new tables

-- user_healths
DROP TABLE IF EXISTS `user_healths`;
CREATE TABLE IF NOT EXISTS `user_healths` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_name` varchar(150) NOT NULL,
  `doctor_contact` varchar(11) NOT NULL,
  `blood_type` varchar(3) NOT NULL,
  `medical_facility` varchar(200) DEFAULT NULL,
  `health_insurance` int(1) NOT NULL DEFAULT '0',
  `security_user_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `security_user_id` (`security_user_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- user_health_allergies
DROP TABLE IF EXISTS `user_health_allergies`;
CREATE TABLE IF NOT EXISTS `user_health_allergies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comment` text DEFAULT NULL,
  `description` varchar(200) NOT NULL,
  `severe` int(1) NOT NULL DEFAULT '0',
  `health_allergy_type_id` int(11) NOT NULL,
  `security_user_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `health_allergy_type_id` (`health_allergy_type_id`),
  INDEX `security_user_id` (`security_user_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- user_health_consultations
DROP TABLE IF EXISTS `user_health_consultations`;
CREATE TABLE IF NOT EXISTS `user_health_consultations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `date` date NOT NULL,
  `health_consultation_type_id` int(11) NOT NULL,
  `security_user_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `health_consultation_type_id` (`health_consultation_type_id`),
  INDEX `security_user_id` (`security_user_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- user_health_families
DROP TABLE IF EXISTS `user_health_families`;
CREATE TABLE IF NOT EXISTS `user_health_families` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comment` text DEFAULT NULL,
  `current` int(1) NOT NULL DEFAULT '0',
  `health_relationship_id` int(4) NOT NULL,
  `health_condition_id` int(6) NOT NULL,
  `security_user_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `health_relationship_id` (`health_relationship_id`),
  INDEX `health_condition_id` (`health_condition_id`),
  INDEX `security_user_id` (`security_user_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- user_health_histories
DROP TABLE IF EXISTS `user_health_histories`;
CREATE TABLE IF NOT EXISTS `user_health_histories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comment` text DEFAULT NULL,
  `current` int(1) NOT NULL DEFAULT '0',
  `health_condition_id` int(6) NOT NULL,
  `security_user_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `health_condition_id` (`health_condition_id`),
  INDEX `security_user_id` (`security_user_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- user_health_immunizations
DROP TABLE IF EXISTS `user_health_immunizations`;
CREATE TABLE IF NOT EXISTS `user_health_immunizations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comment` text DEFAULT NULL,
  `dosage` varchar(20) NOT NULL,
  `date` date NOT NULL,
  `health_immunization_type_id` int(11) NOT NULL,
  `security_user_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `health_immunization_type_id` (`health_immunization_type_id`),
  INDEX `security_user_id` (`security_user_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- user_health_medications
DROP TABLE IF EXISTS `user_health_medications`;
CREATE TABLE IF NOT EXISTS `user_health_medications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `dosage` varchar(20) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `security_user_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `security_user_id` (`security_user_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- user_health_tests
DROP TABLE IF EXISTS `user_health_tests`;
CREATE TABLE IF NOT EXISTS `user_health_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comment` text DEFAULT NULL,
  `result` text DEFAULT NULL,
  `date` date NOT NULL,
  `health_test_type_id` int(11) NOT NULL,
  `security_user_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `health_test_type_id` (`health_test_type_id`),
  INDEX `security_user_id` (`security_user_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- health_allergy_types
DROP TABLE IF EXISTS `health_allergy_types`;
CREATE TABLE IF NOT EXISTS `health_allergy_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- health_consultation_types
DROP TABLE IF EXISTS `health_consultation_types`;
CREATE TABLE IF NOT EXISTS `health_consultation_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- health_relationships
DROP TABLE IF EXISTS `health_relationships`;
CREATE TABLE IF NOT EXISTS `health_relationships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- health_conditions
DROP TABLE IF EXISTS `health_conditions`;
CREATE TABLE IF NOT EXISTS `health_conditions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- health_immunization_types
DROP TABLE IF EXISTS `health_immunization_types`;
CREATE TABLE IF NOT EXISTS `health_immunization_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- health_test_types
DROP TABLE IF EXISTS `health_test_types`;
CREATE TABLE IF NOT EXISTS `health_test_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
