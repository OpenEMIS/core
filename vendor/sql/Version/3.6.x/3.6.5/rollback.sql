-- POCOR-3352
-- institution_shifts
ALTER TABLE `institution_shifts` DROP `previous_shift_id`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3352';


-- POCOR-3379
-- security_functions
DELETE FROM `security_functions` WHERE `id` = 1047;

UPDATE `security_functions` SET `order` = '1001' WHERE `id` = '1002';

UPDATE `security_functions` SET `order` = '1002' WHERE `id` = '1001';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3379';


-- POCOR-3339
-- restore workflow code and name
UPDATE `workflows`
SET `code` = 'STAFF-POSITION-PROFILE-01', `name` = 'Staff Position Profile'
WHERE `code` = 'CHANGE-IN-ASSIGNMENT-01';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3339';


-- POCOR-3302
-- institutions
ALTER TABLE `institutions`
DROP COLUMN `is_academic`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3302';


-- POCOR-3106
-- security functions
ALTER TABLE `security_functions` DROP `description`;

-- Programmes permission
UPDATE `security_functions`
    SET `controller` = 'Students',
        `_view` = 'Programmes.index|Programmes.view',
        `_edit` = NULL
    WHERE `id` = 2011;

-- Overview permission
UPDATE `security_functions`
    SET `name` = 'Students',
        `controller` = 'Students',
        `_view` = 'index|view',
        `_edit` = 'edit',
        `_add` = 'add',
        `_delete` = 'remove',
        `_execute` = 'excel'
    WHERE `id` = 2000;

-- Studentlist permission
UPDATE `security_functions`
    SET `_view` = 'Students.index|Students.view|StudentUser.view|StudentSurveys.index|StudentSurveys.view',
        `_edit` = 'Students.edit|StudentUser.edit|StudentSurveys.edit',
        `_execute` = 'Students.excel|StudentUser.excel'
    WHERE `id` = 1012;

-- Translation table
DELETE FROM `translations` WHERE `en` = 'Overview edit will only take effect when classes permission is granted';

-- security role functions
UPDATE `security_role_functions` SET `_edit` = 0 WHERE `security_function_id` = 2011;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3106';


-- POCOR-3371
-- staff_qualifications
-- ALTER TABLE `staff_qualifications` CHANGE `graduate_year` `graduate_year` INT(4) NOT NULL;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3371';


-- 3.6.4
UPDATE config_items SET value = '3.6.4' WHERE code = 'db_version';
