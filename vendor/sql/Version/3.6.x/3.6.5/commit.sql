-- POCOR-3352
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3352', NOW());

-- institution_shifts
ALTER TABLE `institution_shifts` ADD `previous_shift_id` INT NULL DEFAULT '0' COMMENT 'links to institution_shifts.id' AFTER `shift_option_id`;

UPDATE `institution_shifts`
SET `previous_shift_id` = 0;


-- POCOR-3379
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3379', NOW());

-- security_functions
UPDATE `security_functions` SET `order` = '1002' WHERE `id` = '1002';

UPDATE `security_functions` SET `order` = '1003' WHERE `id` = '1001';

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `order`, `visible`, `created_user_id`, `created`) VALUES (1047, 'Contacts', 'Institutions', 'Institutions', 'General', '8', 'Contacts.index|Contacts.view', 'Contacts.edit', 1001, 1, 1, NOW());


-- POCOR-3339
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3339', NOW());

-- change workflow code and name to 'change in assignment'
UPDATE `workflows`
SET `code` = 'CHANGE-IN-ASSIGNMENT-01', `name` = 'Change in Assignment'
WHERE `code` = 'STAFF-POSITION-PROFILE-01';


-- POCOR-3302
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3302', NOW());

-- institutions
ALTER TABLE `institutions`
ADD COLUMN `is_academic` INT(1) NOT NULL DEFAULT 1 COMMENT '0 -> Non-academic institution\n1 -> Academic Institution' AFTER `shift_type`;


-- POCOR-3106
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


-- POCOR-3371
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3371', NOW());

-- staff_qualifications
ALTER TABLE `staff_qualifications` CHANGE `graduate_year` `graduate_year` INT(4) NULL;


-- 3.6.5
UPDATE config_items SET value = '3.6.5' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
