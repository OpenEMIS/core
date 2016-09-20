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
