-- POCOR-3409
-- config_items
UPDATE `config_items`
SET `value`='Local'
WHERE `id`='1001' AND `value`='OAuth2OpenIDConnect';

-- config_item_options
DELETE FROM `config_item_options` WHERE `id` = 37;

ALTER TABLE `config_item_options`
CHANGE COLUMN `id` `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '' ;

ALTER TABLE `config_item_options`
CHANGE COLUMN `id` `id` INT(11) NOT NULL COMMENT '' ;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3409';


-- POCOR-3420
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


-- 3.6.6
UPDATE config_items SET value = '3.6.6' WHERE code = 'db_version';
