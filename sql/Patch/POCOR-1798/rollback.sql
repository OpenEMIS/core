DROP TABLE IF EXISTS `employment_types`;
DROP TABLE IF EXISTS `extracurricular_types`;
DROP TABLE IF EXISTS `identity_types`;
DROP TABLE IF EXISTS `languages`;
DROP TABLE IF EXISTS `license_types`;
DROP TABLE IF EXISTS `special_need_types`;

SELECT `id` INTO @fieldOptionId FROM `field_options` WHERE `code` = 'EmploymentTypes';
UPDATE field_options SET params = NULL WHERE id = @fieldOptionId;
SELECT `id` INTO @fieldOptionId FROM `field_options` WHERE `code` = 'ExtracurricularTypes';
UPDATE field_options SET params = NULL WHERE id = @fieldOptionId;
SELECT `id` INTO @fieldOptionId FROM `field_options` WHERE `code` = 'IdentityTypes';
UPDATE field_options SET params = NULL WHERE id = @fieldOptionId;
SELECT `id` INTO @fieldOptionId FROM `field_options` WHERE `code` = 'Languages';
UPDATE field_options SET params = NULL WHERE id = @fieldOptionId;
SELECT `id` INTO @fieldOptionId FROM `field_options` WHERE `code` = 'LicenseTypes';
UPDATE field_options SET params = NULL WHERE id = @fieldOptionId;
SELECT `id` INTO @fieldOptionId FROM `field_options` WHERE `code` = 'SpecialNeedTypes';
UPDATE field_options SET params = NULL WHERE id = @fieldOptionId;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-1798';
