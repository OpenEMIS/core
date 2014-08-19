SET @ordering := 0;
SELECT `order` into @ordering FROM `navigations` WHERE `controller` = 'Students' AND `action` = 'add';
UPDATE `navigations` SET `order` = `order` - 1 WHERE `order` > @ordering;
DELETE FROM `navigations` WHERE `controller` = 'Students' AND `action` = 'add';

UPDATE `institution_site_students` SET `student_status_id` = (SELECT `old_id` from `field_option_values` WHERE `field_option_id` = 56 and `id` = `student_status_id`);

DELETE FROM `field_option_values` WHERE `field_option_id` = 56;
DELETE FROM `field_options` WHERE `id` = 56;
ALTER TABLE `field_option_values` DROP `old_id`;

UPDATE `navigations` SET `pattern` = 'index$|advanced' WHERE `controller` = 'Students' AND `action` = 'index';

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

-- Re-insert Staff link to Institution Sites
INSERT INTO `navigations` (`id`, `module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `attributes`, `parent`, `is_wizard`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(11, 'Institution', NULL, 'InstitutionSites', 'DETAILS', 'Staff', 'staff', 'staff', NULL, 3, 0, 10, 1, NULL, NULL, 1, '0000-00-00 00:00:00');
