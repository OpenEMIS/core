-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3420', NOW());

-- code here
-- 38 insitution_postal_code
-- 39 student_identification
-- 41 student_postal_code
-- 45 staff_identification
-- 47 staff_postal_code
DELETE FROM `config_items` WHERE `id` IN (38, 39, 41, 45, 47);

INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES  ('38', 'Postal Code', 'postal_code', 'Custom Validation', 'Postal Code', '', '', '1', '1', '', '', '108', '2014-04-02 16:48:24', '0', NOW());

-- Translation table
INSERT INTO `translations` (`code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `editable`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES (NULL, 'Please enter a valid format', NULL, NULL, NULL, NULL, NULL, '1', NULL, NULL, '0', NOW());
