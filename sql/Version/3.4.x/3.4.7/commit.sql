INSERT INTO `db_patches` VALUES ('PHPOE-1808', NOW());

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `created_user_id`, `created`) VALUES (uuid(), 'StudentUser', 'openemis_no', 'Institutions -> Students -> General', 'OpenEMIS ID', 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `created_user_id`, `created`) VALUES (uuid(), 'StaffUser', 'openemis_no', 'Institutions -> Staff -> General', 'OpenEMIS ID', 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `created_user_id`, `created`) VALUES (uuid(), 'StudentAttendances', 'openemis_no', 'Institutions -> Students -> General', 'OpenEMIS ID', 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `created_user_id`, `created`) VALUES (uuid(), 'StaffAttendances', 'openemis_no', 'Institutions -> Staff -> General', 'OpenEMIS ID', 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `created_user_id`, `created`) VALUES (uuid(), 'Directories', 'openemis_no', 'Institutions -> Staff -> General', 'OpenEMIS ID', 1, NOW());
-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1227', NOW());

-- backup tables
RENAME TABLE `staff_healths` TO `z_1227_staff_healths`;
RENAME TABLE `staff_health_allergies` TO `z_1227_staff_health_allergies`;
RENAME TABLE `staff_health_consultations` TO `z_1227_staff_health_consultations`;
RENAME TABLE `staff_health_families` TO `z_1227_staff_health_families`;
RENAME TABLE `staff_health_histories` TO `z_1227_staff_health_histories`;
RENAME TABLE `staff_health_immunizations` TO `z_1227_staff_health_immunizations`;
RENAME TABLE `staff_health_medications` TO `z_1227_staff_health_medications`;
RENAME TABLE `staff_health_tests` TO `z_1227_staff_health_tests`;

RENAME TABLE `student_healths` TO `z_1227_student_healths`;
RENAME TABLE `student_health_allergies` TO `z_1227_student_health_allergies`;
RENAME TABLE `student_health_consultations` TO `z_1227_student_health_consultations`;
RENAME TABLE `student_health_families` TO `z_1227_student_health_families`;
RENAME TABLE `student_health_histories` TO `z_1227_student_health_histories`;
RENAME TABLE `student_health_immunizations` TO `z_1227_student_health_immunizations`;
RENAME TABLE `student_health_medications` TO `z_1227_student_health_medications`;
RENAME TABLE `student_health_tests` TO `z_1227_student_health_tests`;

-- Add new tables

-- user_healths
DROP TABLE IF EXISTS `user_healths`;
CREATE TABLE IF NOT EXISTS `user_healths` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `blood_type` varchar(3) NOT NULL,
  `doctor_name` varchar(150) NOT NULL,
  `doctor_contact` varchar(11) NOT NULL,
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
  `description` varchar(200) NOT NULL,
  `severe` int(1) NOT NULL DEFAULT '0',
  `comment` text DEFAULT NULL,
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
  `date` date NOT NULL,
  `description` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
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
  `current` int(1) NOT NULL DEFAULT '0',
  `comment` text DEFAULT NULL,
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
  `current` int(1) NOT NULL DEFAULT '0',
  `comment` text DEFAULT NULL,
  `health_condition_id` int(11) NOT NULL,
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
  `date` date NOT NULL,
  `dosage` varchar(20) NOT NULL,
  `comment` text DEFAULT NULL,  
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
  `date` date NOT NULL,
  `result` text DEFAULT NULL,
  `comment` text DEFAULT NULL,
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
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
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
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
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
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
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
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
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
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
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
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
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

-- field_options
SET @ordering := 0;
SELECT max(`order`) INTO @ordering FROM `field_options`;

SET @ordering := @ordering +1;
INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `created_user_id`, `created`) VALUES
('Health', 'AllergyTypes', 'Allergy Types', 'Health', '{"model":"Health.AllergyTypes"}', @ordering, 1, 1, NOW());

SET @ordering := @ordering +1;
INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `created_user_id`, `created`) VALUES
('Health', 'Conditions', 'Conditions', 'Health', '{"model":"Health.Conditions"}', @ordering, 1, 1, NOW());

SET @ordering := @ordering +1;
INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `created_user_id`, `created`) VALUES
('Health', 'ConsultationTypes', 'Consultation Types', 'Health', '{"model":"Health.ConsultationTypes"}', @ordering, 1, 1, NOW());

SET @ordering := @ordering +1;
INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `created_user_id`, `created`) VALUES
('Health', 'ImmunizationTypes', 'Immunization Types', 'Health', '{"model":"Health.ImmunizationTypes"}', @ordering, 1, 1, NOW());

SET @ordering := @ordering +1;
INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `created_user_id`, `created`) VALUES
('Health', 'Relationships', 'Relationships', 'Health', '{"model":"Health.Relationships"}', @ordering, 1, 1, NOW());

SET @ordering := @ordering +1;
INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `created_user_id`, `created`) VALUES
('Health', 'TestTypes', 'Test Types', 'Health', '{"model":"Health.TestTypes"}', @ordering, 1, 1, NOW());

-- security_functions
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES 
(7037, 'Overview', 'Directories', 'Directory', 'Health', 7000, 'Healths.index|Healths.view', 'Healths.edit', 'Healths.add', 'Healths.remove', NULL, 7037, 1, 1, NOW()),
(7038, 'Allergies', 'Directories', 'Directory', 'Health', 7000, 'HealthAllergies.index|HealthAllergies.view', 'HealthAllergies.edit', 'HealthAllergies.add', 'HealthAllergies.remove', NULL, 7038, 1, 1, NOW()),
(7039, 'Consultations', 'Directories', 'Directory', 'Health', 7000, 'HealthConsultations.index|HealthConsultations.view', 'HealthConsultations.edit', 'HealthConsultations.add', 'HealthConsultations.remove', NULL, 7039, 1, 1, NOW()),
(7040, 'Families', 'Directories', 'Directory', 'Health', 7000, 'HealthFamilies.index|HealthFamilies.view', 'HealthFamilies.edit', 'HealthFamilies.add', 'HealthFamilies.remove', NULL, 7040, 1, 1, NOW()),
(7041, 'Histories', 'Directories', 'Directory', 'Health', 7000, 'HealthHistories.index|HealthHistories.view', 'HealthHistories.edit', 'HealthHistories.add', 'HealthHistories.remove', NULL, 7041, 1, 1, NOW()),
(7042, 'Immunizations', 'Directories', 'Directory', 'Health', 7000, 'HealthImmunizations.index|HealthImmunizations.view', 'HealthImmunizations.edit', 'HealthImmunizations.add', 'HealthImmunizations.remove', NULL, 7042, 1, 1, NOW()),
(7043, 'Medications', 'Directories', 'Directory', 'Health', 7000, 'HealthMedications.index|HealthMedications.view', 'HealthMedications.edit', 'HealthMedications.add', 'HealthMedications.remove', NULL, 7043, 1, 1, NOW()),
(7044, 'Tests', 'Directories', 'Directory', 'Health', 7000, 'HealthTests.index|HealthTests.view', 'HealthTests.edit', 'HealthTests.add', 'HealthTests.remove', NULL, 7044, 1, 1, NOW());

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES 
(2021, 'Overview', 'Students', 'Institutions', 'Students - Health', 2000, 'Healths.index|Healths.view', 'Healths.edit', 'Healths.add', 'Healths.remove', NULL, 2021, 1, 1, NOW()),
(2022, 'Allergies', 'Students', 'Institutions', 'Students - Health', 2000, 'HealthAllergies.index|HealthAllergies.view', 'HealthAllergies.edit', 'HealthAllergies.add', 'HealthAllergies.remove', NULL, 2022, 1, 1, NOW()),
(2023, 'Consultations', 'Students', 'Institutions', 'Students - Health', 2000, 'HealthConsultations.index|HealthConsultations.view', 'HealthConsultations.edit', 'HealthConsultations.add', 'HealthConsultations.remove', NULL, 2023, 1, 1, NOW()),
(2024, 'Families', 'Students', 'Institutions', 'Students - Health', 2000, 'HealthFamilies.index|HealthFamilies.view', 'HealthFamilies.edit', 'HealthFamilies.add', 'HealthFamilies.remove', NULL, 2024, 1, 1, NOW()),
(2025, 'Histories', 'Students', 'Institutions', 'Students - Health', 2000, 'HealthHistories.index|HealthHistories.view', 'HealthHistories.edit', 'HealthHistories.add', 'HealthHistories.remove', NULL, 2025, 1, 1, NOW()),
(2026, 'Immunizations', 'Students', 'Institutions', 'Students - Health', 2000, 'HealthImmunizations.index|HealthImmunizations.view', 'HealthImmunizations.edit', 'HealthImmunizations.add', 'HealthImmunizations.remove', NULL, 2026, 1, 1, NOW()),
(2027, 'Medications', 'Students', 'Institutions', 'Students - Health', 2000, 'HealthMedications.index|HealthMedications.view', 'HealthMedications.edit', 'HealthMedications.add', 'HealthMedications.remove', NULL, 2027, 1, 1, NOW()),
(2028, 'Tests', 'Students', 'Institutions', 'Students - Health', 2000, 'HealthTests.index|HealthTests.view', 'HealthTests.edit', 'HealthTests.add', 'HealthTests.remove', NULL, 2028, 1, 1, NOW());

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES 
(3028, 'Overview', 'Staff', 'Institutions', 'Staff - Health', 3000, 'Healths.index|Healths.view', 'Healths.edit', 'Healths.add', 'Healths.remove', NULL, 3028, 1, 1, NOW()),
(3029, 'Allergies', 'Staff', 'Institutions', 'Staff - Health', 3000, 'HealthAllergies.index|HealthAllergies.view', 'HealthAllergies.edit', 'HealthAllergies.add', 'HealthAllergies.remove', NULL, 3029, 1, 1, NOW()),
(3030, 'Consultations', 'Staff', 'Institutions', 'Staff - Health', 3000, 'HealthConsultations.index|HealthConsultations.view', 'HealthConsultations.edit', 'HealthConsultations.add', 'HealthConsultations.remove', NULL, 3030, 1, 1, NOW()),
(3031, 'Families', 'Staff', 'Institutions', 'Staff - Health', 3000, 'HealthFamilies.index|HealthFamilies.view', 'HealthFamilies.edit', 'HealthFamilies.add', 'HealthFamilies.remove', NULL, 3031, 1, 1, NOW()),
(3032, 'Histories', 'Staff', 'Institutions', 'Staff - Health', 3000, 'HealthHistories.index|HealthHistories.view', 'HealthHistories.edit', 'HealthHistories.add', 'HealthHistories.remove', NULL, 3032, 1, 1, NOW()),
(3033, 'Immunizations', 'Staff', 'Institutions', 'Staff - Health', 3000, 'HealthImmunizations.index|HealthImmunizations.view', 'HealthImmunizations.edit', 'HealthImmunizations.add', 'HealthImmunizations.remove', NULL, 3033, 1, 1, NOW()),
(3034, 'Medications', 'Staff', 'Institutions', 'Staff - Health', 3000, 'HealthMedications.index|HealthMedications.view', 'HealthMedications.edit', 'HealthMedications.add', 'HealthMedications.remove', NULL, 3034, 1, 1, NOW()),
(3035, 'Tests', 'Staff', 'Institutions', 'Staff - Health', 3000, 'HealthTests.index|HealthTests.view', 'HealthTests.edit', 'HealthTests.add', 'HealthTests.remove', NULL, 3035, 1, 1, NOW());

INSERT INTO `db_patches` VALUES ('PHPOE-2291', NOW());

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `created_user_id`, `created`) VALUES (uuid(), 'StudentPromotion', 'fromAcademicPeriod', 'Institution > Promotion', 'From Academic Period', 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `created_user_id`, `created`) VALUES (uuid(), 'StudentPromotion', 'toAcademicPeriod', 'Institution > Promotion', 'To Academic Period', 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `created_user_id`, `created`) VALUES (uuid(), 'StudentPromotion', 'fromGrade', 'Institution > Promotion', 'From Grade', 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `created_user_id`, `created`) VALUES (uuid(), 'StudentPromotion', 'toGrade', 'Institution > Promotion', 'To Grade', 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `created_user_id`, `created`) VALUES (uuid(), 'StudentPromotion', 'status', 'Institution > Promotion', 'Status', 1, NOW());

-- 
-- PHPOE-832
--

INSERT INTO `db_patches` VALUES ('PHPOE-832', NOW());

CREATE TABLE `z_832_config_items` LIKE `config_items`;
INSERT INTO `z_832_config_items` SELECT * FROM `config_items`;

INSERT INTO `config_items`
(`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES
(NULL, 'Google Map Center Longitude', 'google_map_center_longitude', 'Map', 'Google Map Center Longitude', '0', '0', '1', '1', '', '', 1, NOW()),
(NULL, 'Google Map Center Latitude', 'google_map_center_latitude', 'Map', 'Google Map Center Latitude', '0', '0', '1', '1', '', '', 1, NOW()),
(NULL, 'Google Map Zoom', 'google_map_zoom', 'Map', 'Google Map Zoom', '10', '10', '1', '1', '', '', 1, NOW());

UPDATE config_items SET value = '3.4.7' WHERE code = 'db_version';
