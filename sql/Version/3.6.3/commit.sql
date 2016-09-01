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
