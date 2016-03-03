-- POCOR-1905
-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-1905', NOW());

INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created` )
VALUES
('Minimum Length', 'password_min_length', 'Password', 'Min Length', '6', '6', 0 , 1 , '', '', NULL , NULL , 1 , now());

INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created` )
VALUES
('Has at least 1 Uppercase Character', 'password_has_uppercase', 'Password', 'Has Uppercase', '0', '0', 0 , 1 , 'Dropdown', 'yes_no', NULL , NULL , 1 , now());

INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created` )
VALUES
('Has at least 1 Number', 'password_has_number', 'Password', 'Has Number', '0', '0', 0 , 1 , 'Dropdown', 'yes_no', NULL , NULL , 1 , now());

INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created` )
VALUES
('Has at least 1 Non-alphanumeric Character', 'password_has_non_alpha', 'Password', 'Has Non Alpha', '0', '0', 0 , 1 , 'Dropdown', 'yes_no', NULL , NULL , 1 , now());


-- POCOR-2208
-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2208', NOW());

UPDATE labels SET field_name = 'Deletable' WHERE module = 'WorkflowSteps' AND module_name = 'Workflow -> Steps' AND field_name = 'Removable';


-- POCOR-2603
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `created_user_id`, `created`) VALUES
(uuid(), 'Accounts', 'password', 'Students -> Accounts | Staff -> Accounts | Security -> Accounts', 'New Password', NULL, NULL, 1, 1, now()),
(uuid(), 'Accounts', 'retype_password', 'Students -> Accounts | Staff -> Accounts | Security -> Accounts', 'Retype New Password', NULL, NULL, 1, 1, now()),
(uuid(), 'StudentAccount', 'password', 'Institution -> Students -> Accounts', 'New Password', NULL, NULL, 1, 1, now()),
(uuid(), 'StudentAccount', 'retype_password', 'Institution -> Students -> Accounts', 'Retype New Password', NULL, NULL, 1, 1, now()),
(uuid(), 'StaffAccount', 'password', 'Institution -> Staff -> Accounts', 'New Password', NULL, NULL, 1, 1, now()),
(uuid(), 'StaffAccount', 'retype_password', 'Institution -> Staff -> Accounts', 'Retype New Password', NULL, NULL, 1, 1, now())
;


-- POCOR-2658
-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2658', NOW());

-- labels
UPDATE `labels` SET `field_name` = 'Area Administrative' WHERE `module` = 'Institutions' AND `field` = 'area_administrative_id';
UPDATE `labels` SET `field_name` = 'Area Education' WHERE `module` = 'Institutions' AND `field` = 'area_id';


-- 3.4.16
-- db_version
UPDATE config_items SET value = '3.4.16' WHERE code = 'db_version';
