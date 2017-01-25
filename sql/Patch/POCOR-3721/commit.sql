-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3721', NOW());

-- license_classifications
DROP TABLE IF EXISTS `license_classifications`;
CREATE TABLE `license_classifications` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This is a field option table containing the list of user-defined classification of licences used by staff_licenses';

INSERT INTO `license_classifications` (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'Teaching License - Provisional', 1, 1, 0, 0, 'PROVISIONAL', 'PROVISIONAL', NULL, NULL, 1, NOW()),
(2, 'Teaching License - Full', 2, 1, 0, 0, 'FULL', 'FULL', NULL, NULL, 1, NOW());

-- staff_licenses_classifications
DROP TABLE IF EXISTS `staff_licenses_classifications`;
CREATE TABLE `staff_licenses_classifications` (
  `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `staff_license_id` int(11) NOT NULL COMMENT 'links to staff_licenses.id',
  `license_classification_id` int(11) NOT NULL COMMENT 'links to license_classifications.id',
  PRIMARY KEY (`staff_license_id`,`license_classification_id`),
  INDEX `staff_license_id` (`staff_license_id`),
  INDEX `license_classification_id` (`license_classification_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of licenses classifications linked to a particular staff license';
