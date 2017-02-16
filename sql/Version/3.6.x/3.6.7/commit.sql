-- POCOR-3409
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3409', NOW());

-- config_item_options
ALTER TABLE `config_item_options`
CHANGE COLUMN `id` `id` INT(11) NOT NULL COMMENT '' ;

INSERT INTO `config_item_options` (`id`, `option_type`, `option`, `value`, `order`, `visible`) VALUES (37, 'authentication_type', 'OAuth 2.0 with OpenID Connect', 'OAuth2OpenIDConnect', 5, 1);


-- POCOR-3420
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


-- 3.6.7
UPDATE config_items SET value = '3.6.7' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
