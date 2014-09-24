UPDATE `navigations` SET `header` = 'Finance' WHERE `header` = 'FINANCE';
UPDATE `navigations` SET `header` = 'Details' WHERE `header` = 'DETAILS';

-- Insert Fees navigations
SET @ordering := 0;
SELECT `order` + 1 into @ordering FROM `navigations` WHERE `controller` = 'InstitutionSites' AND `header` = 'Finance' AND `action` = 'bankAccounts';
UPDATE `navigations` SET `order` = `order` + 2 WHERE `order` >= @ordering;

INSERT INTO `navigations` (`id`, `module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `parent`, `is_wizard`, `order`, `visible`, `created_user_id`, `created`) VALUES
(NULL, 'Institution', null, 'InstitutionSites', 'Finance', 'Fees', 'InstitutionSiteFee', 'InstitutionSiteFee', 3, 0, @ordering, 1, 1, '0000-00-00 00:00:00'),
(NULL, 'Institution', null, 'InstitutionSites', 'Finance', 'Students', 'InstitutionSiteStudentFee', 'InstitutionSiteStudentFee', 3, 0, @ordering+1, 1, 1, '0000-00-00 00:00:00');

-- Insert Finance Report navigations
SELECT `order` + 1 into @ordering FROM `navigations` WHERE `module` = 'Institution' AND `header` = 'Reports' AND `title` = 'Details';
UPDATE `navigations` SET `order` = `order` + 1 WHERE `order` >= @ordering;

INSERT INTO `navigations` (`id`, `module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `parent`, `is_wizard`, `order`, `visible`, `created_user_id`, `created`) VALUES
(NULL, 'Institution', null, 'InstitutionReports', 'Reports', 'Finance', 'finance', 'finance', 3, 0, @ordering, '1', '1', '0000-00-00 00:00:00');

-- Add FeeType to Field Options
SET @fieldOptionId := 0;
SELECT MAX(`id`)+1 INTO @fieldOptionId FROM `field_options`;
INSERT INTO `field_options` (`id`, `code`, `name`, `parent`, `order`, `visible`, `created_user_id`, `created`) VALUES
(@fieldOptionId, 'FeeType', 'Fee Types', 'Finance', @fieldOptionId, 1, 1, NOW());

INSERT INTO `field_option_values` (`id`, `name`, `order`, `visible`, `editable`, `default`, `field_option_id`, `created_user_id`, `created`) VALUES
(NULL, 'Registration', 1, 1, 1, 1, @fieldOptionId, 1, NOW()),
(NULL, 'Typing', 2, 1, 1, 0, @fieldOptionId, 1, NOW()),
(NULL, 'Computer', 3, 1, 1, 0, @fieldOptionId,  1, NOW()),
(NULL, 'Activity/Sports', 4, 1, 1, 0, @fieldOptionId, 1, NOW()),
(NULL, 'Lab', 5, 1, 1, 0, @fieldOptionId, 1, NOW()),
(NULL, 'Library', 6, 1, 1, 0, @fieldOptionId, 1, NOW()),
(NULL, 'ID', 7, 1, 1, 0, @fieldOptionId, 1, NOW()),
(NULL, 'Graduation', 8, 1, 1, 0, @fieldOptionId, 1, NOW()),
(NULL, 'Paper/Stationary', 9, 1, 1, 0, @fieldOptionId, 1, NOW()),
(NULL, 'Maintentance', 10, 1, 1, 0, @fieldOptionId, 1, NOW()),
(NULL, 'Security', 11, 1, 1, 0, @fieldOptionId, 1, NOW()),
(NULL, 'Handbook', 12, 1, 1, 0, @fieldOptionId, 1, NOW());

-- Insert Student Fees navigations
SET @ordering := 0;
SET @parentId := 0;
SELECT `order` + 1, `parent` into @ordering, @parentId FROM `navigations` WHERE `controller` = 'Students' AND `header` = 'Finance' AND `action` = 'bankAccounts';
UPDATE `navigations` SET `order` = `order` + 1 WHERE `order` >= @ordering;

INSERT INTO `navigations` (`id`, `module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `parent`, `is_wizard`, `order`, `visible`, `created_user_id`, `created`) VALUES
(NULL, 'Student', 'Students', 'Students', 'Finance', 'Fees', 'StudentFee', 'StudentFee', @parentId, 0, @ordering, 1, 1, '0000-00-00 00:00:00');

DROP TABLE IF EXISTS `institution_site_fees`;
CREATE TABLE IF NOT EXISTS `institution_site_fees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `total` decimal(11,2) NULL,
  `institution_site_id` int(11) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `education_grade_id` int(11) NOT NULL,
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
  `id` char(36) NOT NULL COMMENT 'To be compatible with CakePHP cascade delete',
  `institution_site_fee_id` int(11) NOT NULL,
  `fee_type_id` int(11) NOT NULL,
  `amount` decimal(11,2) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`institution_site_fee_id`, `fee_type_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `student_fees`;
CREATE TABLE IF NOT EXISTS `student_fees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `amount` decimal(11,2) NOT NULL,
  `payment_date` date NOT NULL,
  `comments` text DEFAULT NULL,
  `student_id` int(11) NOT NULL,
  `institution_site_fee_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `institution_site_fee_id` (`institution_site_fee_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- Insert InstitutionSite Fees
SELECT `order` + 1, `parent_id` INTO @ordering, @parentId FROM `security_functions` WHERE `controller` = 'InstitutionSites' AND `category` = 'Finance' AND `name` = 'Bank Accounts';
UPDATE `security_functions` SET `order` = `order` + 2 WHERE `order` >= @ordering;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES
(NULL, 'Fees', 'InstitutionSites', 'Institutions', 'Finance', @parentId, 'InstitutionSiteFee|InstitutionSiteFee.index|InstitutionSiteFee.view', '_view:InstitutionSiteFee.edit', '_view:InstitutionSiteFee.add', '_view:InstitutionSiteFee.remove', NULL, @ordering, 1, 1, '0000-00-00 00:00:00'),
(NULL, 'Students', 'InstitutionSites', 'Institutions', 'Finance', @parentId, 'InstitutionSiteStudentFee|InstitutionSiteStudentFee.index|InstitutionSiteStudentFee.view|InstitutionSiteStudentFee.viewPayments', '_view:InstitutionSiteStudentFee.edit', '_view:InstitutionSiteStudentFee.add', '_view:InstitutionSiteStudentFee.remove', NULL, @ordering+1, 1, 1, '0000-00-00 00:00:00');

-- Insert Student Fees
SELECT `order` + 1, `parent_id` INTO @ordering, @parentId FROM `security_functions` WHERE `controller` = 'Students' AND `category` = 'Finance' AND `name` = 'Bank Accounts';
UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` >= @ordering;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES
(NULL, 'Fees', 'Students', 'Students', 'Finance', @parentId, 'StudentFee|StudentFee.index|StudentFee.view', NULL, NULL, NULL, NULL, @ordering, 1, 1, '0000-00-00 00:00:00');

-- Insert InstitutionReports Fees
SELECT `order` + 1, `parent_id` INTO @ordering, @parentId FROM `security_functions` WHERE `controller` = 'InstitutionReports' AND `category` = 'Reports' AND `name` = 'Details';
UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` >= @ordering;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES
(NULL, 'Finance', 'InstitutionReports', 'Institutions', 'Reports', @parentId, 'finance', NULL, NULL, NULL, 'generate', @ordering, 1, 1, '0000-00-00 00:00:00');
