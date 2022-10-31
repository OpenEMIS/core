-- code here
DELETE FROM `translations` WHERE `en` = 'Please enter a valid format';

DELETE FROM `config_items` WHERE `id` = 38; -- 38 postal code

INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES  ('38', 'Institution Postal Code', 'institution_postal_code', 'Custom Validation', 'Institution Postal Code', '', '', '1', '1', '', '', '108', '2014-04-02 16:48:24', '0', NOW()),
        ('39', 'Student OpenEMIS ID', 'student_identification', 'Custom Validation', 'Student OpenEMIS ID', '', '', '1', '1', '', '', '108', '2014-04-02 16:48:24', '0', NOW()),
        ('41', 'Student Postal Code', 'student_postal_code', 'Custom Validation', 'Student Postal Code', '', '', '1', '1', '', '', '108', '2014-04-02 16:48:24', '0', NOW()),
        ('45', 'Staff OpenEMIS ID', 'staff_identification', 'Custom Validation', 'Staff OpenEMIS ID', '', '', '1', '1', '', '', '108', '2014-04-02 16:48:25', '0', NOW()),
        ('47', 'Staff Postal Code', 'staff_postal_code', 'Custom Validation', 'Staff Postal Code', '', '', '1', '1', '', '', '108', '2014-04-02 16:48:25', '0', NOW());

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3420';
