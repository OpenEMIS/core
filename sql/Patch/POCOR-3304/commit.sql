-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3304', NOW());


-- security_functions
-- salaries institution
UPDATE `security_functions`
    SET `name` = 'Salary Details',
        `order` = 3036
    WHERE `id` = 3020;

-- bank account
UPDATE `security_functions` SET `order` = 3020 WHERE `id` = 3023;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('3036', 'Salary List', 'Staff', 'Institutions', 'Staff - Finance', '3000', 'Salaries.index', NULL, NULL, NULL, NULL, '3023', '1', NULL, NULL, '1', NOW());


-- salaries directory
UPDATE `security_functions`
    SET `name` = 'Salary Details',
        `order` = 7048
    WHERE `id` = 7034;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('7048', 'Salary List', 'Directories', 'Directory', 'Staff - Finance', '7000', 'StaffSalaries.index', NULL, NULL, NULL, NULL, '7034', '1', NULL, NULL, '1', NOW());
