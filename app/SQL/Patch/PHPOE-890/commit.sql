INSERT INTO `navigations` (`id`, `module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `attributes`, `parent`, `is_wizard`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(148, 'Institution', null, 'InstitutionSites', 'FINANCE', 'Fees', 'fee', '^fee', NULL, 3, 0, 141, 1, NULL, NULL, 1, '0000-00-00 00:00:00');
INSERT INTO `navigations` (`id`, `module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `attributes`, `parent`, `is_wizard`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(149, 'Institution', null, 'InstitutionSites', 'FINANCE', 'Students', 'feeStudent', '^feeStudent', NULL, 3, 0, 142, 1, NULL, NULL, 1, '0000-00-00 00:00:00');


DROP TABLE IF EXISTS `institution_site_fees`;
CREATE TABLE IF NOT EXISTS `institution_site_fees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `institution_site_id` int(11) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `education_grade_id` int(11) NOT NULL,
  `total_fee` decimal(11,2) NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_site_id` (`institution_site_id`),
  KEY `school_year_id` (`school_year_id`),
  KEY `education_grade_id` (`education_grade_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `institution_site_fee_types`;
CREATE TABLE IF NOT EXISTS `institution_site_fee_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `institution_site_fee_id` int(11) NOT NULL,
  `fee_type_id` int(11) NOT NULL,
  `fee` decimal(11,2) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_site_fee_id` (`institution_site_fee_id`),
  KEY `fee_type_id` (`fee_type_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `institution_site_fee_students`;
CREATE TABLE IF NOT EXISTS `institution_site_fee_students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `institution_site_fee_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `total_paid` decimal(11,2) DEFAULT NULL,
  `total_outstanding` decimal(11,2) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_site_fee_id` (`institution_site_fee_id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


INSERT INTO `field_options` (`id`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(70, 'FeeType', 'Fee Types', 'Finance', NULL, 70, 1, NULL, NULL, 1, NOW());


INSERT INTO `field_option_values` (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `field_option_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(19, 'Tuition', 1, 1, 1, 0, NULL, NULL, 70, NULL, NULL, 1, NOW()),
(20, 'Sports', 2, 1, 1, 0, NULL, NULL, 70, NULL, NULL, 1, NOW()),
(21, 'Computer', 3, 1, 1, 0, NULL, NULL, 70, NULL, NULL, 1, NOW()),
(22, 'Other', 4, 1, 1, 0, NULL, NULL, 70, NULL, NULL, 1, NOW());
