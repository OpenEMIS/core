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


-- 3.4.16
-- db_version
UPDATE config_items SET value = '3.4.16' WHERE code = 'db_version';
