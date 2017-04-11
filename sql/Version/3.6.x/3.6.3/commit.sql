-- POCOR-3257
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3257', NOW());

-- config_items
INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('100', 'Area Education API', 'area_api', 'Administrative Boundaries', 'Area Education API', '', '', '0', '1', '', '', NULL, NULL, '2', NOW());

-- Removed Auto_increments
ALTER TABLE `security_functions` CHANGE `id` `id` INT(11) NOT NULL;
ALTER TABLE `config_items` CHANGE `id` `id` INT(11) NOT NULL;

-- Security functions
UPDATE `security_functions`
    SET `_view` = 'index|view|AdministrativeBoundaries.view|ProductLists.view|Authentication.view',
        `_edit` = 'edit|AdministrativeBoundaries.edit|ProductLists.edit|Authentication.edit'
    WHERE `name` = 'Configurations';


-- POCOR-3258
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3258', NOW());

-- create config_product_lists table
CREATE TABLE `config_product_lists` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`name` varchar(50) NOT NULL,
`url` text NULL,
`modified_user_id` int(11) NULL,
`modified` datetime NULL,
`created_user_id` int(11) NOT NULL,
`created` datetime NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- config_items
INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`)
VALUES ('1000', 'Product Lists', 'product_lists', 'Product Lists', 'Product Lists', '0', '0', '0', '1', '', '', '1', NOW());

CREATE TABLE `z_3258_config_items` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `z_3258_config_items`
SELECT `id`, `code`
FROM `config_items`
WHERE `code` = 'authentication_type';

UPDATE `config_items`
SET `id` = 1001
WHERE `code` = 'authentication_type';


-- POCOR-3110
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3110', NOW());

-- import_mapping
INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `foreign_key`) VALUES ('Institution.Staff', 'end_date', '( DD/MM/YYYY )', '2', '0');
UPDATE `import_mapping` SET `order`='1' WHERE `model`='Institution.Staff' AND `column_name`='start_date';
UPDATE `import_mapping` SET `order`='5' WHERE `model`='Institution.Staff' AND `column_name`='institution_position_id';
UPDATE `import_mapping` SET `order`='6' WHERE `model`='Institution.Staff' AND `column_name`='staff_type_id';
UPDATE `import_mapping` SET `order`='7' WHERE `model`='Institution.Staff' AND `column_name`='staff_id';


-- POCOR-3319
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3319', NOW());

-- create backup institutions table
CREATE TABLE `z_3319_institutions` LIKE `institutions`;

INSERT INTO `z_3319_institutions`
SELECT * FROM `institutions`
WHERE `date_closed` IS NULL
OR `date_closed` = ''
OR `date_closed` = '0000-00-00';

-- remove incorrect date-closed and year-closed values
UPDATE `institutions`
SET `year_closed` = NULL, `date_closed` = NULL
WHERE `date_closed` IS NULL
OR `date_closed` = ''
OR `date_closed` = '0000-00-00';


-- POCOR-3193
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3193', NOW());

-- create backup institution_subject table
CREATE TABLE `z_3193_institution_subjects` LIKE `institution_subjects`;

INSERT INTO `z_3193_institution_subjects`
SELECT * FROM `institution_subjects` s
WHERE NOT EXISTS
(
    SELECT c.`institution_subject_id`
    FROM `institution_class_subjects` c
    WHERE s.`id` = c.`institution_subject_id`
);

-- delete orphan records from institution subjects
DELETE s
FROM `institution_subjects` s
WHERE NOT EXISTS
(
    SELECT c.`institution_subject_id`
    FROM `institution_class_subjects` c
    WHERE s.`id` = c.`institution_subject_id`
);


-- POCOR-3272
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3272', NOW());

-- create backup user contacts table
CREATE TABLE `z_3272_user_contacts` (
    `id` int(11) NOT NULL,
    `value` varchar(100) NOT NULL,
     PRIMARY KEY (`id`)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `z_3272_user_contacts`
SELECT `user_contacts`.`id`, `user_contacts`.`value`
FROM `user_contacts`
INNER JOIN `contact_types`
ON `contact_types`.`id` = `user_contacts`.`contact_type_id`
WHERE `contact_types`.`contact_option_id` IN (1, 2, 3);

-- remove any negative signs or hashes in numeric values
UPDATE `user_contacts`
INNER JOIN `contact_types`
ON `contact_types`.`id` = `user_contacts`.`contact_type_id`
SET `user_contacts`.`value` = REPLACE(`user_contacts`.`value`, '-', '')
WHERE `contact_types`.`contact_option_id` IN (1, 2, 3);


-- POCOR-3340
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3340', NOW());

-- workflow_actions
CREATE TABLE `z_3340_workflow_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_key` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `z_3340_workflow_actions`(`id`, `event_key`)
SELECT `id`, `event_key`
FROM `workflow_actions`
WHERE `event_key` = 'Workflow.onApprove';

UPDATE `workflow_actions`
SET `event_key` = NULL
WHERE `event_key` = 'Workflow.onApprove';

UPDATE `workflow_actions`
INNER JOIN `workflow_steps` ON `workflow_actions`.`workflow_step_id` = `workflow_steps`.`id`
INNER JOIN `workflows` ON `workflow_steps`.`workflow_id` = `workflows`.`id`
INNER JOIN `workflow_models` ON `workflow_models`.`id` = `workflows`.`workflow_model_id`
SET `workflow_actions`.`event_key` = 'Workflow.onApprove'
WHERE `workflow_actions`.`action` IS NOT NULL
AND `workflow_actions`.`name` = 'Approve'
AND `workflow_models`.`model` = 'Institution.StaffPositionProfiles'
AND `workflow_steps`.`name` = 'Pending Approval'
AND `workflow_steps`.`stage` IS NOT NULL;


-- POCOR-3138
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3138', NOW());

-- security_user
ALTER TABLE `security_users` ADD `identity_number` VARCHAR(50) NULL DEFAULT NULL AFTER `date_of_death`, ADD INDEX (`identity_number`);

#update identity_number based on the current default identity type
UPDATE `security_users` S
INNER JOIN (
    SELECT `security_user_id`, `number`
    FROM `user_identities` U1
    WHERE `created` = (
        SELECT MAX(U2.`created`)
        FROM `user_identities` U2
        WHERE U1.`security_user_id` = U2.`security_user_id`
        AND U2.`identity_type_id` = (
            SELECT `id`
            FROM `identity_types`
            WHERE `default` = 1)
        GROUP BY U2.`security_user_id`)
    AND `number` <> '') U
ON S.`id` = U.`security_user_id`
SET S.`identity_number` = U.`number`;

-- translations
INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT NULL, NULL, 'Please set other identity type as default before deleting the current one', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual
WHERE NOT EXISTS (SELECT * FROM `translations` WHERE `en` = 'Please set other identity type as default before deleting the current one');


-- POCOR-3338
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3338', NOW());

-- workflow_actions
CREATE TABLE `z_3338_workflow_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_key` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `z_3338_workflow_actions`(`id`, `event_key`)
SELECT `id`, `event_key`
FROM `workflow_actions`
WHERE `event_key` = 'Workflow.onDeleteRecord';

UPDATE `workflow_actions`
SET `event_key` = NULL
WHERE `event_key` = 'Workflow.onDeleteRecord';


-- 3.6.3
UPDATE config_items SET value = '3.6.3' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
