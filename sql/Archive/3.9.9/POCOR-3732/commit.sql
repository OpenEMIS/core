-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3732', NOW());

-- institutions
CREATE TABLE `z_3732_institutions`  LIKE `institutions`;
INSERT INTO `z_3732_institutions` SELECT * FROM `institutions`;

ALTER TABLE `institutions` CHANGE `code` `code` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

-- workflow_transitions
CREATE TABLE `z_3732_workflow_transitions`  LIKE `workflow_transitions`;
INSERT INTO `z_3732_workflow_transitions` SELECT * FROM `workflow_transitions`;

ALTER TABLE `workflow_transitions` DROP `modified_user_id`;
ALTER TABLE `workflow_transitions` DROP `modified`;

-- staff_behaviours
RENAME TABLE `staff_behaviours` TO `z_3732_staff_behaviours`;

DROP TABLE IF EXISTS `staff_behaviours`;
CREATE TABLE `staff_behaviours` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` text NOT NULL,
  `date_of_behaviour` date NOT NULL,
  `time_of_behaviour` time DEFAULT NULL,
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
  `staff_behaviour_category_id` int(11) NOT NULL COMMENT 'links to staff_behaviour_categories.id',
  `behaviour_classification_id` int(11) NOT NULL COMMENT 'links to behaviour_classifications.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`),
  KEY `institution_id` (`institution_id`),
  KEY `staff_behaviour_category_id` (`staff_behaviour_category_id`),
  KEY `behaviour_classification_id` (`behaviour_classification_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This table contains all behavioural records of staff';


INSERT INTO `staff_behaviours` (`id`, `description`, `date_of_behaviour`, `time_of_behaviour`, `staff_id`, `institution_id`, `staff_behaviour_category_id`, `behaviour_classification_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, CONCAT(`description`, ' - ', `action`), `date_of_behaviour`, `time_of_behaviour`, `staff_id`, `institution_id`, `staff_behaviour_category_id`, `behaviour_classification_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3732_staff_behaviours`;

-- institution_cases
DROP TABLE IF EXISTS `institution_cases`;
CREATE TABLE IF NOT EXISTS `institution_cases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text DEFAULT NULL,
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `assignee_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to security_users.id',
  `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `status_id` (`status_id`),
  KEY `assignee_id` (`assignee_id`),
  KEY `institution_id` (`institution_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all the cases in a particular institution';

-- institution_case_records
DROP TABLE IF EXISTS `institution_case_records`;
CREATE TABLE IF NOT EXISTS `institution_case_records` (
  `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `institution_case_id` int(11) NOT NULL COMMENT 'links to institution_cases.id',
  `record_id` int(11) NOT NULL,
  `feature` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`institution_case_id`, `record_id`, `feature`),
  KEY `id` (`id`),
  KEY `institution_case_id` (`institution_case_id`),
  KEY `record_id` (`record_id`),
  KEY `feature` (`feature`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of records associates with cases';

-- workflow_rules
DROP TABLE IF EXISTS `workflow_rules`;
CREATE TABLE `workflow_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rule` varchar(255) NOT NULL,
  `feature` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `workflow_id` int(11) NOT NULL COMMENT 'links to workflows.id',
  `modified_user_id` int(5) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(5) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `workflow_id` (`workflow_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains workflow rules associates with a specific workflow';

-- workflow_models
SET @modelId := 12;
INSERT INTO `workflow_models` (`id`, `name`, `model`, `filter`, `is_school_based`, `created_user_id`, `created`) VALUES
(@modelId, 'Institutions > Cases', 'Institution.InstitutionCases', null, 1, 1, NOW());

-- Pre-insert workflows - Apply To All
INSERT INTO `workflows` (`code`, `name`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('CASES-1001', 'Cases - General', @modelId, 1, NOW());

SET @workflowId := 0;
SELECT `id` INTO @workflowId FROM `workflows` WHERE `code` = 'CASES-1001';

-- Pre-insert workflow_steps
SET @openStatusId := 0;
SET @pendingStatusId := 0;
SET @closedStatusId := 0;
INSERT INTO `workflow_steps` (`name`, `category`, `is_editable`, `is_removable`, `is_system_defined`, `workflow_id`, `created_user_id`, `created`) VALUES
('Open', 1, 1, 1, 1, @workflowId, 1, NOW()),
('Pending For Approval', 2, 0, 0, 1, @workflowId, 1, NOW()),
('Closed', 3, 0, 0, 1, @workflowId, 1, NOW());

SELECT `id` INTO @openStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `category` = 1;
SELECT `id` INTO @pendingStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `category` = 2;
SELECT `id` INTO @closedStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `category` = 3 AND `name` = 'Closed';

-- Pre-insert workflow_actions
INSERT INTO `workflow_actions` (`name`, `description`, `action`, `visible`, `comment_required`, `allow_by_assignee`, `event_key`, `workflow_step_id`, `next_workflow_step_id`, `created_user_id`, `created`) VALUES
('Submit For Approval', NULL, 0, 1, 0, 1, NULL, @openStatusId, @pendingStatusId, 1, NOW()),
('Cancel', NULL, 1, 1, 0, 1, NULL, @openStatusId, @closedStatusId, 1, NOW()),
('Approve', NULL, 0, 1, 0, 0, NULL, @pendingStatusId, @closedStatusId, 1, NOW()),
('Reject', NULL, 1, 1, 0, 0, NULL, @pendingStatusId, @openStatusId, 1, NOW()),
('Approve', NULL, 0, 0, 0, 0, NULL, @closedStatusId, 0, 1, NOW()),
('Reject', NULL, 1, 0, 0, 0, NULL, @closedStatusId, 0, 1, NOW()),
('Reopen', NULL, NULL, 1, 0, 0, NULL, @closedStatusId, @openStatusId, 1, NOW());

-- security_functions
UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` >= 5043 AND `order` < 6000;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES
(1056, 'Cases', 'Institutions', 'Institutions', 'Cases', 1000, 'Cases.index|Cases.view', 'Cases.edit', NULL, 'Cases.remove', NULL, 1056, 1, 1, NOW()),
(5067, 'Rules', 'Workflows', 'Administration', 'Workflows', 5000, 'Rules.index|Rules.view', 'Rules.edit', 'Rules.add', 'Rules.remove', NULL, 5043, 1, 1, NOW());
