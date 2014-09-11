UPDATE `navigations` SET `header` = 'Finance' WHERE `header` = 'FINANCE';
UPDATE `navigations` SET `header` = 'Details' WHERE `header` = 'DETAILS';

-- Delete Student Fees navigations
SET @ordering := 0;
SELECT `order` into @ordering FROM `navigations` WHERE `controller` = 'Students' AND `header` = 'Finance' AND `action` = 'StudentFee';
UPDATE `navigations` SET `order` = `order` - 1 WHERE `order` > @ordering;
DELETE FROM `navigations` WHERE `controller` = 'Students' AND `header` = 'Finance' AND `action` = 'StudentFee';

-- Delete Fees navigations
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

-- Delete Fees Permissions for all modules
UPDATE `security_functions` SET `name` = 'Fees' WHERE `controller` = 'InstitutionSites' AND `category` = 'Finance' AND `name` = 'Fee';
UPDATE `security_functions` SET `name` = 'Students' WHERE `controller` = 'InstitutionSites' AND `category` = 'Finance' AND `name` = 'Student';
UPDATE `security_functions` SET `name` = 'Finance' WHERE `controller` = 'Students' AND `category` = 'Details' AND `name` IN ('Fee', 'Fees');
UPDATE `security_functions` SET `name` = 'Fees' WHERE `controller` = 'Students' AND `category` = 'Finance' AND `name` = 'Fee';

SELECT `order` into @ordering FROM `security_functions` WHERE `controller` = 'InstitutionSites' AND `category` = 'Finance' AND `name` = 'Fees';
UPDATE `security_functions` SET `order` = `order` - 2 WHERE `order` > @ordering;
DELETE FROM `security_functions` WHERE `controller` = 'InstitutionSites' AND `category` = 'Finance' AND `name` IN ('Fees', 'Students');

SELECT `order` into @ordering FROM `security_functions` WHERE `controller` = 'Students' AND `category` = 'Finance' AND `name` = 'Fees';
UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` > @ordering;
DELETE FROM `security_functions` WHERE `controller` = 'Students' AND `category` = 'Finance' AND `name` = 'Fees';

DROP TABLE IF EXISTS `student_fees`;
DROP TABLE IF EXISTS `institution_site_fees`;
DROP TABLE IF EXISTS `institution_site_fee_types`;
DROP TABLE IF EXISTS `institution_site_student_fees`;
DROP TABLE IF EXISTS `institution_site_student_fee_transactions`;
