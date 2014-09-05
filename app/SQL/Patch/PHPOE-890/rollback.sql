-- UPDATE `navigations` SET `header` = 'Finance' WHERE `header` = 'FINANCE';

-- Delete Fees navigations
SET @ordering := 0;
SELECT `order` into @ordering FROM `navigations` WHERE `controller` = 'InstitutionSites' AND `header` = 'Finance' AND `title` = 'Students';
UPDATE `navigations` SET `order` = `order` - 2 WHERE `order` > @ordering;
DELETE FROM `navigations` WHERE `controller` = 'InstitutionSites' AND `header` = 'Finance' AND `title` IN ('Fees', 'Students');

-- Delete Finance Report navigations
SELECT `order` into @ordering FROM `navigations` WHERE `module` = 'Institution' AND `header` = 'Reports' AND `title` = 'Finance';
UPDATE `navigations` SET `order` = `order` - 1 WHERE `order` > @ordering;
DELETE FROM `navigations` WHERE `module` = 'Institution' AND `header` = 'Reports' AND `title` = 'Finance';


-- Delete FeeType from Field Option
SET @fieldOptionId := 0;
SELECT `id` INTO @fieldOptionId FROM `field_options` WHERE `code` = 'FeeType';
DELETE FROM `field_option_values` WHERE `field_option_id` = @fieldOptionId;
DELETE FROM `field_options` WHERE `id` = @fieldOptionId;




DELETE FROM `navigations` WHERE `controller` = 'InstitutionSites' AND `header` = 'Finance' AND `title` = 'Students';
DELETE FROM `navigations` WHERE `controller` = 'InstitutionReports' AND `title` = 'Finance';

DROP TABLE IF EXISTS `institution_site_fees`;
DROP TABLE IF EXISTS `institution_site_fee_types`;
DROP TABLE IF EXISTS `institution_site_student_fees`;
DROP TABLE IF EXISTS `institution_site_student_fee_transactions`;

DELETE FROM field_option_values where field_option_id=70;

DELETE FROM security_functions where id between 195 and 198;