-- PHPOE-1312
ALTER TABLE `sms_messages` CHANGE `message` `message` VARCHAR(160) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

DELETE FROM `config_items` WHERE `name` LIKE 'sms_content_length' AND `type` LIKE 'SMS';
