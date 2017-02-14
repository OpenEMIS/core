-- POCOR-3304
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3304', NOW());

-- security_functions
-- salaries institution
UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` BETWEEN 3020 AND 3035;
UPDATE `security_functions` SET `order` = 3020 WHERE `id` = 3023; -- Bank Account

UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` BETWEEN 3021 AND 3023;
UPDATE `security_functions` SET `name` = 'Salary Details' WHERE `id` = 3020;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('3036', 'Salary List', 'Staff', 'Institutions', 'Staff - Finance', '3000', 'Salaries.index', NULL, NULL, NULL, NULL, '3021', '1', NULL, NULL, '1', NOW());

-- salaries directory
UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` BETWEEN 7034 AND 7047;
UPDATE `security_functions` SET `name` = 'Salary Details' WHERE `id` = 7034;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('7048', 'Salary List', 'Directories', 'Directory', 'Staff - Finance', '7000', 'StaffSalaries.index', NULL, NULL, NULL, NULL, '7034', '1', NULL, NULL, '1', NOW());


-- POCOR-3387
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3387', NOW());

-- field_options
-- change order of Sectors
UPDATE `field_options`
SET `order` = 5
WHERE `id` = 5;

-- change order of Providers
UPDATE `field_options`
SET `order` = 6
WHERE `id` = 4;


-- 3.6.6
UPDATE config_items SET value = '3.6.6' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
