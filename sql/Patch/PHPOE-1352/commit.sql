-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1352');

-- workflow_models
INSERT INTO `workflow_models` (`name`, `model`, `filter`, `created_user_id`, `created`) VALUES
('Institution - Survey', 'Institution.InstitutionSurveys', NULL, 1, '0000-00-00 00:00:00');

-- workflow_actions
ALTER TABLE `workflow_actions` ADD `workflow_event_id` INT(11) NULL DEFAULT '0' AFTER `next_workflow_step_id`;

-- New table - workflow_events
DROP TABLE IF EXISTS `workflow_events`;
CREATE TABLE IF NOT EXISTS `workflow_events` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `description` text DEFAULT NULL,
  `event_key` varchar(200) NOT NULL,
  `method` varchar(200) NOT NULL,
  `workflow_model_id` int(11) NOT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `workflow_events`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `workflow_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- workflow_events - data
SET @modelId := 0;
SELECT `id` INTO @modelId FROM `workflow_models` WHERE `model` = 'Institution.InstitutionSurveys';

TRUNCATE TABLE `workflow_events`;
INSERT INTO `workflow_events` (`name`, `description`, `event_key`, `method`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('Institution Surveys On Approve', NULL, 'Workflow.onApprove', 'onApprove', @modelId, 1, '0000-00-00 00:00:00'),
('Institution Surveys On Reject', NULL, 'Workflow.onReject', 'onReject', @modelId, 1, '0000-00-00 00:00:00');
