-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-1905', NOW());

INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created` )
VALUES
('Minimum Length', 'password_min_length', 'Password', 'Min Length', '6', '6', 0 , 1 , '', '', NULL , NULL , 1 , now());

INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created` )
VALUES
('Has at least 1 Uppercase Character', 'password_has_uppercase', 'Password', 'Has Uppercase', '0', '0', 0 , 1 , 'Dropdown', 'yes_no', NULL , NULL , 1 , now());

-- added in after test fail - 'didnt implement lowercase'
INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created` )
VALUES
('Has at least 1 Lowercase Character', 'password_has_lowercase', 'Password', 'Has Lowercase', '0', '0', 0 , 1 , 'Dropdown', 'yes_no', NULL , NULL , 1 , now());

INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created` )
VALUES
('Has at least 1 Number', 'password_has_number', 'Password', 'Has Number', '0', '0', 0 , 1 , 'Dropdown', 'yes_no', NULL , NULL , 1 , now());

INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created` )
VALUES
('Has at least 1 Non-alphanumeric Character', 'password_has_non_alpha', 'Password', 'Has Non Alpha', '0', '0', 0 , 1 , 'Dropdown', 'yes_no', NULL , NULL , 1 , now());