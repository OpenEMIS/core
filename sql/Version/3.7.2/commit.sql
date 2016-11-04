-- POCOR-3450
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3450', NOW());

-- code here
CREATE TABLE IF NOT EXISTS `staff_appraisals` (
    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `title` VARCHAR(100) NOT NULL,
    `from` date NOT NULL,
    `to` date NOT NULL,
    `final_rating` DECIMAL(4,2) NOT NULL, -- 4 is the max digits, 2 is the max digits after the decimal point
    `comment` text DEFAULT NULL,
    `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
    `competency_set_id` int(11) NOT NULL COMMENT 'links to competency_sets.id',
    `staff_appraisal_type_id` int(11) NOT NULL COMMENT 'links to staff_appraisal_types.id',
    `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `staff_appraisals`
    ADD INDEX `academic_period_id` (`academic_period_id`),
    ADD INDEX `competency_set_id` (`competency_set_id`),
    ADD INDEX `staff_appraisal_type_id` (`staff_appraisal_type_id`),
    ADD INDEX `staff_id` (`staff_id`);


CREATE TABLE IF NOT EXISTS `staff_appraisal_types` (
    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `code` VARCHAR(100) NOT NULL,
    `name` VARCHAR(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `staff_appraisal_types` (`code`, `name`)
VALUES  ('SELF', 'Self'),
                ('SUPERVISOR', 'Supervisor'),
                ('PEER', 'Peer');


CREATE TABLE IF NOT EXISTS `competencies` (
    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(55) NOT NULL,
    `order` int(3) NOT NULL,
    `visible` int(1) NOT NULL DEFAULT '1',
    `editable` int(1) NOT NULL DEFAULT '1',
    `default` int(1) NOT NULL DEFAULT '0',
    `min` DECIMAL(4,2) NOT NULL DEFAULT '0',
    `max` DECIMAL(4,2) NOT NULL DEFAULT '10',
    `international_code` VARCHAR(50) DEFAULT NULL,
    `national_code` VARCHAR(50) DEFAULT NULL,
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `competency_sets` (
    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `order` int(3) NOT NULL,
    `visible` int(1) NOT NULL DEFAULT '1',
    `editable` int(1) NOT NULL DEFAULT '1',
    `default` int(1) NOT NULL DEFAULT '0',
    `international_code` VARCHAR(50) DEFAULT NULL,
    `national_code` VARCHAR(50) DEFAULT NULL,
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `competency_sets_competencies` (
    `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
    `competency_id` int(11) NOT NULL COMMENT 'links to competencies.id',
    `competency_set_id` int(11) NOT NULL COMMENT 'links to competency_sets.id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `competency_sets_competencies`
    ADD PRIMARY KEY (`competency_id`, `competency_set_id`),
    ADD UNIQUE KEY `id` (`id`),
    ADD KEY `competency_id` (`competency_id`),
    ADD KEY `competency_set_id` (`competency_set_id`);


CREATE TABLE IF NOT EXISTS `staff_appraisals_competencies` (
    `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
    `competency_id` int(11) NOT NULL COMMENT 'links to competencies.id',
    `staff_appraisal_id` int(11) NOT NULL COMMENT 'links to staff_appraisals.id',
    `rating` DECIMAL(4,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `staff_appraisals_competencies`
    ADD PRIMARY KEY (`competency_id`, `staff_appraisal_id`),
    ADD UNIQUE KEY `id` (`id`),
    ADD KEY `competency_id` (`competency_id`),
    ADD KEY `staff_appraisal_id` (`staff_appraisal_id`);


-- security_function (permission)
UPDATE `security_functions` SET `order` = `order` + 1 WHERE `id` BETWEEN 3000 AND 4000 AND `order` >= 3025;
UPDATE `security_functions` SET `order` = `order` + 1 WHERE `id` BETWEEN 7000 AND 8000 AND `order` >= 7033;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES  ('3037', 'Appraisals', 'Institutions', 'Institutions', 'Staff - Professional Development', '3000', 'StaffAppraisals.index|StaffAppraisals.view', 'StaffAppraisals.edit', 'StaffAppraisals.add', 'StaffAppraisals.remove', NULL, '3025', '1', NULL, NULL, NULL, '1', NOW()),
        ('7049', 'Appraisals', 'Directories', 'Directory', 'Staff - Professional Development', '7000', 'StaffAppraisals.index|StaffAppraisals.view', NULL, NULL, NULL, NULL, '7033', '1', NULL, NULL, NULL, '1', NOW());


-- POCOR-3451
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3451', NOW());

-- backup field_options tables
RENAME TABLE `field_options` TO `z_3451_field_options`;
RENAME TABLE `field_option_values` TO `z_3451_field_option_values`;

-- institution_visit_requests
DROP TABLE IF EXISTS `institution_visit_requests`;
CREATE TABLE IF NOT EXISTS `institution_visit_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_of_visit` date NOT NULL,
  `comment` text,
  `file_name` varchar(250) DEFAULT NULL,
  `file_content` longblob,
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `assignee_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to security_users.id',
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `quality_visit_type_id` int(11) NOT NULL COMMENT 'links to quality_visit_types.id',
  `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `status_id` (`status_id`),
  KEY `assignee_id` (`assignee_id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `quality_visit_type_id` (`quality_visit_type_id`),
  KEY `institution_id` (`institution_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all visit requested by the institutions';

-- workflow_models
RENAME TABLE `workflow_models` TO `z_3451_workflow_models`;

DROP TABLE IF EXISTS `workflow_models`;
CREATE TABLE IF NOT EXISTS `workflow_models` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `filter` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_school_based` int(1) NOT NULL DEFAULT '0',
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of features that are workflow-enabled';

INSERT INTO `workflow_models` (`id`, `name`, `model`, `filter`, `is_school_based`, `created_user_id`, `created`)
SELECT `id`, `name`, `model`, `filter`, `is_school_based`, `created_user_id`, NOW()
FROM `z_3451_workflow_models`;

-- workflow_models
SET @modelId := 9;
INSERT INTO `workflow_models` (`id`, `name`, `model`, `filter`, `is_school_based`, `created_user_id`, `created`) VALUES
(@modelId, 'Institutions > Visits > Requests', 'Institution.VisitRequests', NULL, 1, 1, NOW());

-- Pre-insert workflows
INSERT INTO `workflows` (`code`, `name`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('VISIT-1001', 'Institutions - Visit Requests', @modelId, 1, NOW());

SET @workflowId := 0;
SELECT `id` INTO @workflowId FROM `workflows` WHERE `code` = 'VISIT-1001';

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
SELECT `id` INTO @closedStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `category` = 3;

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
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`)
VALUES (1048, 'Visit Requests', 'Institutions', 'Institutions', 'Quality', 1000, 'VisitRequests.index|VisitRequests.view', 'VisitRequests.edit', 'VisitRequests.add', 'VisitRequests.remove', 'VisitRequests.download', 1029, 1, 1, NOW());

UPDATE `security_functions` SET `order` = 1048 WHERE `id` = 1027;

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`)
VALUES ('8077f98a-9b4f-11e6-8f28-525400b263eb', 'VisitRequests', 'file_content', 'Institutions -> VisitRequests', 'Attachment', 1, 1, NOW());


-- 3.7.2
UPDATE config_items SET value = '3.7.2' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
