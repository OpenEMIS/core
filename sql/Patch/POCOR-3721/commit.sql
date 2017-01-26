-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3721', NOW());

-- license_classifications
DROP TABLE IF EXISTS `license_classifications`;
CREATE TABLE `license_classifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This is a field option table containing the list of user-defined classification of licences used by staff_licenses';

INSERT INTO `license_classifications` (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'Teaching License - Provisional', 1, 1, 0, 0, 'PROVISIONAL', 'PROVISIONAL', NULL, NULL, 1, NOW()),
(2, 'Teaching License - Full', 2, 1, 0, 0, 'FULL', 'FULL', NULL, NULL, 1, NOW());

-- staff_licenses_classifications
DROP TABLE IF EXISTS `staff_licenses_classifications`;
CREATE TABLE `staff_licenses_classifications` (
  `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `staff_license_id` int(11) NOT NULL COMMENT 'links to staff_licenses.id',
  `license_classification_id` int(11) NOT NULL COMMENT 'links to license_classifications.id',
  PRIMARY KEY (`staff_license_id`,`license_classification_id`),
  INDEX `staff_license_id` (`staff_license_id`),
  INDEX `license_classification_id` (`license_classification_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of licenses classifications linked to a particular staff license';

-- workflow_models
SET @modelId := 11;
INSERT INTO `workflow_models` (`id`, `name`, `model`, `filter`, `is_school_based`, `created_user_id`, `created`) VALUES
(@modelId, 'Staff > Professional Development > Licenses', 'Staff.Licenses', NULL, 0, 1, NOW());

-- Pre-insert workflows
INSERT INTO `workflows` (`code`, `name`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('LICENSE-1001', 'Staff Licenses', @modelId, 1, NOW());

SET @workflowId := 0;
SELECT `id` INTO @workflowId FROM `workflows` WHERE `code` = 'LICENSE-1001';

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

-- staff_licenses
RENAME TABLE `staff_licenses` TO `z_3721_staff_licenses`;

DROP TABLE IF EXISTS `staff_licenses`;
CREATE TABLE `staff_licenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `license_number` varchar(100) NOT NULL,
  `issue_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `issuer` varchar(100) NOT NULL,
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `assignee_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to security_users.id',
  `license_type_id` int(11) NOT NULL COMMENT 'links to license_types.id',
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `license_type_id` (`license_type_id`),
  INDEX `status_id` (`status_id`),
  INDEX `assignee_id` (`assignee_id`),
  INDEX `staff_id` (`staff_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `staff_licenses` (`id`, `license_number`, `issue_date`, `expiry_date`, `issuer`, `status_id`, `assignee_id`, `license_type_id`, `staff_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `license_number`, `issue_date`, `expiry_date`, `issuer`, @openStatusId, 0, `license_type_id`, `staff_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_3721_staff_licenses`;
