SET @ordering := 0;
SELECT `order` into @ordering FROM `navigations` WHERE `controller` = 'Students' AND `action` = 'add';
-- SELECT @ordering;

DELETE FROM `navigations` WHERE `controller` = 'Students' AND `action` = 'add';

UPDATE `institution_site_students` SET `student_status_id` = (SELECT `old_id` from `field_option_values` WHERE `field_option_id` = 56 and `id` = `student_status_id`);

DELETE FROM `field_option_values` WHERE `field_option_id` = 56;
DELETE FROM `field_options` WHERE `id` = 56;
ALTER TABLE `field_option_values` DROP `old_id`;

UPDATE `navigations` SET `order` = `order` - 1 WHERE `order` > @ordering;
UPDATE `navigations` SET `action` = 'add', `pattern` = 'add$' WHERE `controller` = 'Students' AND `action` = 'create';
UPDATE `security_functions` SET `_add` = 'add' WHERE `controller` = 'Students' AND `category` = 'General' AND `_add` = 'create';
UPDATE `navigations` SET `action` = 'students', `pattern` = 'students' WHERE `controller` = 'InstitutionSites' AND `action` = 'InstitutionSiteStudent';

ALTER TABLE `institution_site_students` DROP `institution_site_id` ;
ALTER TABLE `institution_site_students` DROP `education_programme_id` ;
