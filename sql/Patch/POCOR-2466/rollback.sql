-- security_user table
ALTER TABLE `security_users` DROP `email` ;

-- Security functions (permission)
UPDATE `security_functions` SET `_view` = 'Logs.index' WHERE `id` = 5031;
UPDATE `security_functions` SET `_delete` = NULL WHERE `id` = 5031;
DELETE FROM `security_functions` WHERE `id` = 5062;
DELETE FROM `security_functions` WHERE `id` = 5063;
UPDATE `security_functions` SET `order` = `order` - 2 WHERE `order` BETWEEN 5033 AND 5062;

-- contact_options
INSERT INTO `contact_options` (`id`, `name`, `order`, `visible`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ('4', 'Email', '4', '1', NULL, NULL, NULL, NULL, '1', NOW());

-- alert_roles table
RENAME TABLE `alerts_roles` TO `alert_roles`;

ALTER TABLE `alert_roles`
    DROP PRIMARY KEY,
    ADD PRIMARY KEY(`id`),
    DROP INDEX `alert_rule_id`,
    DROP INDEX `security_role_id`;

-- alert_logs
ALTER TABLE `alert_logs` ADD `type` VARCHAR(20) NOT NULL AFTER `destination`;

-- alerts table
ALTER TABLE `alert_rules` CHANGE `threshold` `threshold` INT(5) NOT NULL;
ALTER TABLE `alert_rules` CHANGE `enabled` `status` INT(1) NOT NULL DEFAULT '1';
ALTER TABLE `alert_rules` CHANGE `feature` `code` VARCHAR(50) NOT NULL;
RENAME TABLE `alert_rules` TO `alerts`;


-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-2466';
