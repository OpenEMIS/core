-- POCOR-3092
DELETE FROM `translations` WHERE `en` IN (
    '%s with %s',
    'Transfer of student %s from %s',
    '%s in %s',
    '%s of %s',
    '%s from %s',
    'Transfer of staff %s to %s',
    'Staff Transfer Approved of %s from %s',
    'Admission of student %s',
    'Withdraw request of %s',
    '%s in %s on %s',
    '%s applying for session %s in %s',
    'Results of %s');

INSERT INTO `translations`
SELECT * FROM `z_3092_translations`;

DROP TABLE `z_3092_translations`;

DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3092';


-- POCOR-3699
-- `qualification_specialisations`
RENAME TABLE `z_3699_qualification_specialisations` TO `qualification_specialisations`;

-- `qualification_specialisation_subjects`
RENAME TABLE `z_3699_qualification_specialisation_subjects` TO `qualification_specialisation_subjects`;

-- `qualification_institutions`
RENAME TABLE `z_3699_qualification_institutions` TO `qualification_institutions`;

-- `staff_qualifications`
DROP TABLE IF EXISTS `staff_qualifications`;
RENAME TABLE `z_3699_staff_qualifications` TO `staff_qualifications`;

-- `qualification_titles`
DROP TABLE IF EXISTS `qualification_titles`;

-- `staff_qualifications_subjects`
DROP TABLE IF EXISTS `staff_qualifications_subjects`;

-- `labels`
DELETE FROM `labels` WHERE `id` IN ('5c3ddc98-0aec-11e7-b9c5-525400b263eb', 'a72ed550-1449-11e7-9f11-525400b263eb');

-- `system_patches`
DELETE FROM `system_patches` WHERE `issue`='POCOR-3699';


-- POCOR-3927
-- security_functions
DROP TABLE IF EXISTS `security_functions`;
RENAME TABLE `z_3927_security_functions` TO `security_functions`;

-- staff_trainings
DROP TABLE IF EXISTS `staff_trainings`;
RENAME TABLE `z_3927_staff_trainings` TO `staff_trainings`;

-- alerts Table
DELETE FROM `alerts` WHERE `name` = 'LicenseRenewal';

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3927';


-- POCOR-3876
-- labels
DELETE FROM `labels`
WHERE `id` = 'b7b9aad6-1ff1-11e7-a840-525400b263eb';

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3876';


-- 3.9.10
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.9.10' WHERE code = 'db_version';
