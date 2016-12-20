-- POCOR-3593
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3593', NOW());

-- security_users
ALTER TABLE `security_users`
ADD COLUMN `nationality_id` INT NULL AFTER `date_of_death`,
ADD COLUMN `identity_type_id` INT NULL AFTER `nationality_id`,
ADD COLUMN `external_reference` VARCHAR(50) NULL AFTER `identity_number`;

UPDATE `security_users`
INNER JOIN `user_nationalities` ON `user_nationalities`.`security_user_id` = `security_users`.`id`
SET `security_users`.`nationality_id` = `user_nationalities`.`nationality_id`;

UPDATE `security_users`
INNER JOIN `nationalities`
        ON `nationalities`.`id` = `security_users`.`nationality_id`
INNER JOIN `user_identities`
        ON `user_identities`.`identity_type_id` = `nationalities`.`identity_type_id`
        AND `user_identities`.`security_user_id` = `security_users`.`id`
SET `security_users`.`identity_type_id` = `user_identities`.`identity_type_id`, `security_users`.`identity_number` = `user_identities`.`number`;

INSERT INTO `config_item_options` (`id`, `option_type`, `option`, `value`, `order`, `visible`) VALUES ('102', 'external_data_source_type', 'Custom', 'Custom', '3', '1');

CREATE TABLE `z_3593_external_data_source_attributes` LIKE `external_data_source_attributes`;

INSERT INTO `z_3593_external_data_source_attributes` SELECT * FROM `external_data_source_attributes`;

DELETE FROM `external_data_source_attributes`;


-- POCOR-3632
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3632', NOW());

-- security_functions
UPDATE `security_functions`
SET `order` = `order` + 1
WHERE `order` > 5010 AND `order` < 6000;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('5058', 'Assessment Periods', 'Assessments', 'Administration', 'Assessments', '5000', 'AssessmentPeriods.index|AssessmentPeriods.view', 'AssessmentPeriods.edit', 'AssessmentPeriods.add', 'AssessmentPeriods.remove', NULL, '5011', '1', NULL, NULL, NULL, '1', '2015-12-19 02:41:00');


-- POCOR-3633
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3633', NOW());

-- security_function
UPDATE `security_functions` SET `_edit`='StudentUser.edit|StudentUser.pull' WHERE `id`='2000';


-- POCOR-3623
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3623', NOW());

-- backup user_identities
DROP TABLE IF EXISTS `z_3623_user_identities`;
CREATE TABLE `z_3623_user_identities` LIKE `user_identities`;

INSERT INTO `z_3623_user_identities`
SELECT * FROM `user_identities` UI
WHERE NOT EXISTS (
        SELECT 1
    FROM `security_users` SU
    WHERE UI.`security_user_id` = SU.`id`
);

-- delete user_identities
DELETE FROM `user_identities`
WHERE NOT EXISTS (
        SELECT 1
    FROM `security_users`
    WHERE `user_identities`.`security_user_id` = `security_users`.`id`
);


-- 3.8.3
UPDATE config_items SET value = '3.8.3' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
