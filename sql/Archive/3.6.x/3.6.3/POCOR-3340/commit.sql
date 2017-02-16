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
