-- PHPOE-1312
ALTER TABLE `sms_messages` CHANGE `message` `message` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

INSERT INTO `config_items` (`id`, `name`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES (NULL, 'sms_content_length', 'SMS', 'SMS Content Length', '', '160', '1', '1', '', '', NULL, NULL, '1', '0000-00-00 00:00:00');
