-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3106', NOW());


-- Security functions
ALTER TABLE `security_functions` ADD `description` VARCHAR( 255 ) AFTER `visible`;

-- Programmes permission
UPDATE `security_functions`
    SET `controller` = 'Institutions',
        `_view` = 'StudentProgrammes.index|StudentProgrammes.view',
        `_edit` = 'Students.edit|StudentProgrammes.edit'
    WHERE `id` = 2011;

-- Overview permission
UPDATE `security_functions`
    SET `name` = 'Overview',
        `controller` = 'Institutions',
        `_view` = 'StudentUser.view',
        `_edit` = 'StudentUser.edit',
        `_add` = NULL,
        `_delete` = NULL,
        `_execute` = 'StudentUser.excel',
        `description` = 'Overview edit will only take effect when classes permission is granted.'
    WHERE `id` = 2000;

-- Studentlist permission
UPDATE `security_functions`
    SET `_view` = 'Students.index|Students.view|StudentSurveys.index|StudentSurveys.view',
        `_edit` = 'StudentSurveys.edit',
        `_execute` = 'Students.excel'
    WHERE `id` = 1012;

-- Translation table
INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `editable`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES (NULL, NULL, 'Overview edit will only take effect when classes permission is granted', NULL, NULL, NULL, NULL, NULL, '1', NULL, NULL, '2', NOW());

-- Security Role functions
-- copy the value on the student edit permission into the programme edit permission
UPDATE `security_role_functions` t1, (SELECT `_edit`, `security_role_id` FROM `security_role_functions` WHERE `security_function_id` = 1012) t2
    SET t1.`_edit` = t2.`_edit`
    WHERE t1.`security_role_id` = t2.`security_role_id`
    AND `security_function_id` = 2011;
