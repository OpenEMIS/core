DROP TABLE IF EXISTS `student_statuses`;
CREATE TABLE IF NOT EXISTS `student_statuses` (
  `id` int(3) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `international_code` varchar(20) DEFAULT NULL,
  `national_code` varchar(20) DEFAULT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

--
-- Dumping data for table `student_statuses`
--

INSERT INTO `student_statuses` (`id`, `name`, `international_code`, `national_code`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'Current', '', '', 1, 1, 1, '2013-12-09 18:02:29', 1, '2013-11-12 00:00:00'),
(2, 'Transferred', '', '', 2, 1, NULL, NULL, 1, '2013-12-09 18:02:29'),
(3, 'Dropout', '', '', 3, 1, NULL, NULL, 1, '2013-12-09 18:02:29'),
(4, 'Expelled', '', '', 4, 1, NULL, NULL, 1, '2013-12-09 18:02:29'),
(5, 'Graduated', '', '', 5, 1, NULL, NULL, 1, '2013-12-09 18:02:29');

DROP TABLE IF EXISTS `staff_statuses`;
CREATE TABLE IF NOT EXISTS `staff_statuses` (
  `id` int(3) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `international_code` varchar(20) DEFAULT NULL,
  `national_code` varchar(20) DEFAULT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

--
-- Dumping data for table `staff_statuses`
--

INSERT INTO `staff_statuses` (`id`, `name`, `international_code`, `national_code`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'Current', '', '', 1, 1, 1, '2013-12-10 09:52:09', 1, '2013-11-12 00:00:00'),
(2, 'Transferred', '', '', 2, 1, NULL, NULL, 1, '2013-12-10 09:52:09'),
(3, 'Resigned', '', '', 3, 1, NULL, NULL, 1, '2013-12-10 09:52:09'),
(4, 'Leave', '', '', 4, 1, NULL, NULL, 1, '2013-12-10 09:52:09'),
(5, 'Terminated', '', '', 5, 1, NULL, NULL, 1, '2013-12-10 09:52:09');

-- Student
UPDATE `navigations` SET `pattern` = 'index$|advanced' WHERE `controller` = 'Students' AND `action` = 'index';
SET @ordering := 0;
SELECT `order` into @ordering FROM `navigations` WHERE `controller` = 'Students' AND `action` = 'InstitutionSiteStudent/add';
UPDATE `navigations` SET `order` = `order` - 1 WHERE `order` > @ordering;
DELETE FROM `navigations` WHERE `controller` = 'Students' AND `action` = 'InstitutionSiteStudent/add';

UPDATE `institution_site_students` SET `student_status_id` = (SELECT `old_id` from `field_option_values` WHERE `field_option_id` = 56 and `id` = `student_status_id`);

DELETE FROM `field_option_values` WHERE `field_option_id` = 56;
DELETE FROM `field_options` WHERE `id` = 56;

ALTER TABLE `institution_site_students` DROP `institution_site_id` ;
ALTER TABLE `institution_site_students` DROP `education_programme_id` ;

SELECT `order` into @ordering FROM `navigations` WHERE `controller` = 'Students' AND `action` = 'Programme';
UPDATE `navigations` SET `order` = `order` - 1 WHERE `order` > @ordering;
DELETE FROM `navigations` WHERE `controller` = 'Students' AND `action` = 'Programme';

-- Re-insert Student link to Institution Sites
INSERT INTO `navigations` (`id`, `module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `attributes`, `parent`, `is_wizard`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(10, 'Institution', NULL, 'InstitutionSites', 'DETAILS', 'Students', 'students', 'students', NULL, 3, 0, 9, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

-- Staff
UPDATE `navigations` SET `pattern` = 'index$|advanced' WHERE `controller` = 'Staff' AND `action` = 'index';
SET @ordering := 0;
SELECT `order` into @ordering FROM `navigations` WHERE `controller` = 'Staff' AND `action` = 'InstitutionSiteStaff/add';
UPDATE `navigations` SET `order` = `order` - 1 WHERE `order` > @ordering;
DELETE FROM `navigations` WHERE `controller` = 'Staff' AND `action` = 'InstitutionSiteStaff/add';

-- Institution Site Positions
UPDATE `navigations` SET `action` = 'positions', `pattern` = '^positions|^positionsHistory' WHERE `controller` = 'InstitutionSites' AND `title` = 'Positions';
UPDATE `security_functions` SET `_view` = 'positions|positionsView' WHERE `controller` =  'InstitutionSites' AND `name` = 'Positions';

-- Student Security
UPDATE `security_functions` SET `_view` = 'view', `_add` = 'add' WHERE `controller` = 'Students' AND `parent_id` = -1;
SET @ordering := 0;
SELECT `order` into @ordering FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Programmes';
UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` > @ordering;
DELETE FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Programmes';

-- Staff Security
UPDATE `security_functions` SET `_view` = 'view', `_add` = 'add' WHERE `controller` = 'Staff' AND `parent_id` = -1;

-- Re-insert Staff link to Institution Sites
INSERT INTO `navigations` (`id`, `module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `attributes`, `parent`, `is_wizard`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(11, 'Institution', NULL, 'InstitutionSites', 'DETAILS', 'Staff', 'staff', 'staff', NULL, 3, 0, 10, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

-- Staff Status
SET @staffStatusId := 0;
SELECT `id` INTO @staffStatusId FROM `field_options` WHERE `code` = 'StaffStatus';
UPDATE `institution_site_staff` SET `staff_status_id` = (SELECT `old_id` from `field_option_values` WHERE `field_option_id` = @staffStatusId and `id` = `staff_status_id`);
DELETE FROM `field_option_values` WHERE `field_option_id` = @staffStatusId;
DELETE FROM `field_options` WHERE `id` = @staffStatusId;

ALTER TABLE `field_option_values` DROP `old_id`;
