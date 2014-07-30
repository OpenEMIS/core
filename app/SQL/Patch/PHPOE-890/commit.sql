INSERT INTO `navigations` (`id`, `module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `attributes`, `parent`, `is_wizard`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(NULL, 'Institution', null, 'InstitutionSites', 'FINANCE', 'Fees', 'fee', '^fee', NULL, 3, 0, 141, 1, NULL, NULL, 1, '0000-00-00 00:00:00');
INSERT INTO `navigations` (`id`, `module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `attributes`, `parent`, `is_wizard`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(NULL, 'Institution', null, 'InstitutionSites', 'FINANCE', 'Students', 'studentFee', '^studentFee', NULL, 3, 0, 142, 1, NULL, NULL, 1, '0000-00-00 00:00:00');
INSERT INTO `navigations` (`id`, `module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `attributes`, `parent`, `is_wizard`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(NULL, 'Student', 'Students', 'Students', 'DETAILS', 'Fees', 'fee', '^fee', NULL, 62, 0, 81, 1, NULL, NULL, 1, '0000-00-00 00:00:00');


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

DROP TABLE IF EXISTS `institution_site_student_fees`;
CREATE TABLE IF NOT EXISTS `institution_site_student_fees` (
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

DROP TABLE IF EXISTS `institution_site_student_fee_transactions`;
CREATE TABLE IF NOT EXISTS `institution_site_student_fee_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `institution_site_student_fee_id` int(11) NOT NULL,
  `paid_date` date DEFAULT NULL,
  `paid` decimal(11,2) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_site_student_fee_id` (`institution_site_student_fee_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


INSERT INTO `field_options` (`id`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(70, 'FeeType', 'Fee Types', 'Finance', NULL, 70, 1, NULL, NULL, 1, NOW());


INSERT INTO `field_option_values` (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `field_option_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(NULL, 'Registration', 1, 1, 1, 0, NULL, NULL, 70, NULL, NULL, 1, NOW()),
(NULL, 'Typing', 2, 1, 1, 0, NULL, NULL, 70, NULL, NULL, 1, NOW()),
(NULL, 'Computer', 3, 1, 1, 0, NULL, NULL, 70, NULL, NULL, 1, NOW()),
(NULL, 'Activity/Sports', 4, 1, 1, 0, NULL, NULL, 70, NULL, NULL, 1, NOW()),
(NULL, 'Lab', 5, 1, 1, 0, NULL, NULL, 70, NULL, NULL, 1, NOW()),
(NULL, 'Library', 6, 1, 1, 0, NULL, NULL, 70, NULL, NULL, 1, NOW()),
(NULL, 'ID', 7, 1, 1, 0, NULL, NULL, 70, NULL, NULL, 1, NOW()),
(NULL, 'Graduation', 8, 1, 1, 0, NULL, NULL, 70, NULL, NULL, 1, NOW()),
(NULL, 'Paper/Stationary', 9, 1, 1, 0, NULL, NULL, 70, NULL, NULL, 1, NOW()),
(NULL, 'Maintentance', 10, 1, 1, 0, NULL, NULL, 70, NULL, NULL, 1, NOW()),
(NULL, 'Security', 11, 1, 1, 0, NULL, NULL, 70, NULL, NULL, 1, NOW()),
(NULL, 'Handbook', 12, 1, 1, 0, NULL, NULL, 70, NULL, NULL, 1, NOW());



INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(195, 'Fee', 'InstitutionSites', 'Institutions', 'Finance', 8, 'fee|feeView', '_view:feeEdit', '_view:feeAdd|feeAdd', '_view:feeDelete', NULL, 195, 1, NULL, NULL, 1, NOW()),
(196, 'Student', 'InstitutionSites', 'Institutions', 'Finance', 8, 'studentFee|studentFeeView|studentFeeViewTransaction', '_view:studentFeeAdd|_view:studentFeeAddTransaction', 'studentFeeAdd|studentFeeAddTransaction', '_view:studentFeeDeleteTransaction', NULL, 196, 1, NULL, NULL, 1, NOW()),
(197, 'Fee', 'Students', 'Students', 'Details', 66, 'fee|feeView', null, null, NULL, NULL, 197, 1, NULL, NULL, 1, NOW());


UPDATE `security_functions` set category = 'Finance' where id = '197';
UPDATE `navigations` SET `header`='FINANCE' WHERE `id`='150';

INSERT INTO `navigations` (`id`, `module`, `controller`, `header`, `title`, `action`, `pattern`, `parent`, `is_wizard`, `order`, `visible`, `created_user_id`, `created`) 
VALUES (NULL, 'Institution', 'InstitutionReports', 'REPORTS', 'Finance', 'finance', 'finance', '3', '0', '36', '1', '1', '0000-00-00 00:00:00');

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES (NULL, 'Finance', 'InstitutionReports', 'Institutions', 'Reports', '8', 'finance', 'generate', '198', '1', '1', '0000-00-00 00:00:00');
