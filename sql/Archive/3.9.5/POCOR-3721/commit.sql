-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3721', NOW());

-- license_types
INSERT INTO `license_types` (`name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Teaching License - Provisional', 1, 1, 0, 0, 'TEACHING_LICENSE_PROVISIONAL', 'TEACHING_LICENSE_PROVISIONAL', NULL, NULL, 1, NOW()),
('Teaching License - Full', 2, 1, 0, 0, 'TEACHING_LICENSE_FULL', 'TEACHING_LICENSE_FULL', NULL, NULL, 1, NOW());

SET @order := 2;
UPDATE `license_types`
SET `order` = @order := @order + 1
WHERE national_code NOT IN ('TEACHING_LICENSE_FULL', 'TEACHING_LICENSE_PROVISIONAL');

-- workflow_models
SET @modelId := 11;
INSERT INTO `workflow_models` (`id`, `name`, `model`, `filter`, `is_school_based`, `created_user_id`, `created`) VALUES
(@modelId, 'Staff > Professional Development > Licenses', 'Staff.Licenses', 'FieldOption.LicenseTypes', 0, 1, NOW());

-- Pre-insert workflows - Apply To All
INSERT INTO `workflows` (`code`, `name`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('STAFF-LICENSE-1001', 'Staff Licenses - General', @modelId, 1, NOW());

SET @workflowId := 0;
SELECT `id` INTO @workflowId FROM `workflows` WHERE `code` = 'STAFF-LICENSE-1001';

INSERT INTO `workflows_filters` (`id`, `workflow_id`, `filter_id`) VALUES
('4ec31ed4-ec54-11e6-b8f2-525400b263eb', @workflowId, 0);

-- Pre-insert workflow_steps
SET @openStatusId := 0;
SET @pendingStatusId := 0;
SET @closedStatusId := 0;
SET @activeStatusId := 0;
INSERT INTO `workflow_steps` (`name`, `category`, `is_editable`, `is_removable`, `is_system_defined`, `workflow_id`, `created_user_id`, `created`) VALUES
('Open', 1, 1, 1, 1, @workflowId, 1, NOW()),
('Pending For Approval', 2, 0, 0, 1, @workflowId, 1, NOW()),
('Closed', 3, 0, 0, 1, @workflowId, 1, NOW()),
('Active', 3, 0, 0, 0, @workflowId, 1, NOW());

SELECT `id` INTO @openStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `category` = 1;
SELECT `id` INTO @pendingStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `category` = 2;
SELECT `id` INTO @closedStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `category` = 3 AND `name` = 'Closed';
SELECT `id` INTO @activeStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `category` = 3 AND `name` = 'Active';

-- Pre-insert workflow_actions
INSERT INTO `workflow_actions` (`name`, `description`, `action`, `visible`, `comment_required`, `allow_by_assignee`, `event_key`, `workflow_step_id`, `next_workflow_step_id`, `created_user_id`, `created`) VALUES
('Submit For Approval', NULL, 0, 1, 0, 1, 'Workflow.onAssignBack', @openStatusId, @activeStatusId, 1, NOW()),
('Cancel', NULL, 1, 1, 0, 1, NULL, @openStatusId, @closedStatusId, 1, NOW()),
('Approve', NULL, 0, 1, 0, 0, NULL, @pendingStatusId, @closedStatusId, 1, NOW()),
('Reject', NULL, 1, 1, 0, 0, NULL, @pendingStatusId, @openStatusId, 1, NOW()),
('Approve', NULL, 0, 0, 0, 0, NULL, @closedStatusId, 0, 1, NOW()),
('Reject', NULL, 1, 0, 0, 0, NULL, @closedStatusId, 0, 1, NOW()),
('Reopen', NULL, NULL, 1, 0, 0, NULL, @closedStatusId, @openStatusId, 1, NOW()),
('Approve', NULL, 0, 0, 0, 0, NULL, @activeStatusId, 0, 1, NOW()),
('Reject', NULL, 1, 0, 0, 0, NULL, @activeStatusId, 0, 1, NOW()),
('Reopen', NULL, NULL, 1, 0, 0, NULL, @activeStatusId, @openStatusId, 1, NOW());

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
  `license_type_id` int(11) NOT NULL COMMENT 'links to license_types.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `license_type_id` (`license_type_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This is a field option table containing the list of user-defined classification of licences used by staff_licenses';

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

-- staff_licenses
RENAME TABLE `staff_licenses` TO `z_3721_staff_licenses`;

DROP TABLE IF EXISTS `staff_licenses`;
CREATE TABLE `staff_licenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `license_number` varchar(100) DEFAULT NULL,
  `issue_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `issuer` varchar(100) NOT NULL,
  `comments` text COLLATE utf8mb4_unicode_ci,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `staff_licenses` (`id`, `license_number`, `issue_date`, `expiry_date`, `issuer`, `status_id`, `assignee_id`, `license_type_id`, `staff_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `license_number`, `issue_date`, `expiry_date`, `issuer`, @activeStatusId, `created_user_id`, `license_type_id`, `staff_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_3721_staff_licenses`;

-- Pre-insert workflow_transitions
-- Open to Active
INSERT INTO `workflow_transitions` (`prev_workflow_step_name`, `workflow_step_name`, `workflow_action_name`, `workflow_model_id`, `model_reference`, `created_user_id`, `created`)
SELECT 'Open', 'Active', 'Administration - Initial Setup', @modelId, `id`, 1, NOW() FROM `staff_licenses` ORDER BY `id`;

-- Pre-insert workflows - Default
INSERT INTO `workflows` (`code`, `name`, `workflow_model_id`, `created_user_id`, `created`) VALUES
('STAFF-LICENSE-1002', 'Staff Licenses', @modelId, 1, NOW());

SET @workflowId := 0;
SELECT `id` INTO @workflowId FROM `workflows` WHERE `code` = 'STAFF-LICENSE-1002';

INSERT INTO `workflows_filters` (`id`, `workflow_id`, `filter_id`) VALUES
('68de0aac-ec55-11e6-b8f2-525400b263eb', @workflowId, (SELECT `id` FROM `license_types` WHERE `international_code` = 'TEACHING_LICENSE_PROVISIONAL')),
('6b7a9861-ec55-11e6-b8f2-525400b263eb', @workflowId, (SELECT `id` FROM `license_types` WHERE `international_code` = 'TEACHING_LICENSE_FULL'));

-- Pre-insert workflow_steps
SET @openStatusId := 0;
SET @pendingStatusId := 0;
SET @closedStatusId := 0;
SET @pendingVerificationStatusId := 0;
SET @pendingRecommendationStatusId := 0;
SET @cancelledStatusId := 0;
SET @rejectedStatusId := 0;
SET @notRecommendedStatusId := 0;
SET @awardedStatusId := 0;
SET @notAwardedStatusId := 0;

INSERT INTO `workflow_steps` (`name`, `category`, `is_editable`, `is_removable`, `is_system_defined`, `workflow_id`, `created_user_id`, `created`) VALUES
('Open', 1, 1, 1, 1, @workflowId, 1, NOW()),
('Pending For Approval', 2, 0, 0, 1, @workflowId, 1, NOW()),
('Closed', 3, 0, 0, 1, @workflowId, 1, NOW()),
('Pending For Verification & Authentication', 2, 0, 0, 0, @workflowId, 1, NOW()),
('Pending For Recommendation', 2, 0, 0, 0, @workflowId, 1, NOW()),
('Cancelled', 3, 0, 0, 0, @workflowId, 1, NOW()),
('Application Rejected', 3, 0, 0, 0, @workflowId, 1, NOW()),
('Not Recommended', 3, 0, 0, 0, @workflowId, 1, NOW()),
('License Awarded', 3, 0, 0, 0, @workflowId, 1, NOW()),
('License Not Awarded', 3, 0, 0, 0, @workflowId, 1, NOW());

SELECT `id` INTO @openStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `category` = 1;
SELECT `id` INTO @pendingStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `category` = 2 AND `name` = 'Pending For Approval';
SELECT `id` INTO @closedStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `category` = 3 AND `name` = 'Closed';

SELECT `id` INTO @pendingVerificationStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `category` = 2 AND `name` = 'Pending For Verification & Authentication';
SELECT `id` INTO @pendingRecommendationStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `category` = 2 AND `name` = 'Pending For Recommendation';

SELECT `id` INTO @cancelledStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `category` = 3 AND `name` = 'Cancelled';
SELECT `id` INTO @rejectedStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `category` = 3 AND `name` = 'Application Rejected';
SELECT `id` INTO @notRecommendedStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `category` = 3 AND `name` = 'Not Recommended';
SELECT `id` INTO @awardedStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `category` = 3 AND `name` = 'License Awarded';
SELECT `id` INTO @notAwardedStatusId FROM `workflow_steps` WHERE `workflow_id` = @workflowId AND `category` = 3 AND `name` = 'License Not Awarded';

-- Pre-insert workflow_actions
INSERT INTO `workflow_actions` (`name`, `description`, `action`, `visible`, `comment_required`, `allow_by_assignee`, `event_key`, `workflow_step_id`, `next_workflow_step_id`, `created_user_id`, `created`) VALUES
('Submit For Verification & Authentication', NULL, 0, 1, 0, 1, NULL, @openStatusId, @pendingVerificationStatusId, 1, NOW()),
('Cancel', NULL, 1, 1, 0, 1, NULL, @openStatusId, @cancelledStatusId, 1, NOW()),
('Submit For Recommendation', NULL, 0, 1, 0, 0, NULL, @pendingVerificationStatusId, @pendingRecommendationStatusId, 1, NOW()),
('Reject', NULL, 1, 1, 0, 0, NULL, @pendingVerificationStatusId, @rejectedStatusId, 1, NOW()),
('Submit For Approval', NULL, 0, 1, 0, 0, NULL, @pendingRecommendationStatusId, @pendingStatusId, 1, NOW()),
('Reject', NULL, 1, 1, 0, 0, NULL, @pendingRecommendationStatusId, @notRecommendedStatusId, 1, NOW()),
('Approve', NULL, 0, 1, 0, 0, NULL, @pendingStatusId, @awardedStatusId, 1, NOW()),
('Reject', NULL, 1, 1, 0, 0, NULL, @pendingStatusId, @notAwardedStatusId, 1, NOW()),
('Approve', NULL, 0, 0, 0, 0, NULL, @cancelledStatusId, 0, 1, NOW()),
('Reject', NULL, 1, 0, 0, 0, NULL, @cancelledStatusId, 0, 1, NOW()),
('Reopen', NULL, NULL, 1, 0, 0, NULL, @cancelledStatusId, @openStatusId, 1, NOW()),
('Approve', NULL, 0, 0, 0, 0, NULL, @rejectedStatusId, 0, 1, NOW()),
('Reject', NULL, 1, 0, 0, 0, NULL, @rejectedStatusId, 0, 1, NOW()),
('Reopen', NULL, NULL, 1, 0, 0, NULL, @rejectedStatusId, @openStatusId, 1, NOW()),
('Approve', NULL, 0, 0, 0, 0, NULL, @notRecommendedStatusId, 0, 1, NOW()),
('Reject', NULL, 1, 0, 0, 0, NULL, @notRecommendedStatusId, 0, 1, NOW()),
('Reopen', NULL, NULL, 1, 0, 0, NULL, @notRecommendedStatusId, @openStatusId, 1, NOW()),
('Approve', NULL, 0, 0, 0, 0, NULL, @awardedStatusId, 0, 1, NOW()),
('Reject', NULL, 1, 0, 0, 0, NULL, @awardedStatusId, 0, 1, NOW()),
('Reopen', NULL, NULL, 1, 0, 0, NULL, @awardedStatusId, @openStatusId, 1, NOW()),
('Approve', NULL, 0, 0, 0, 0, NULL, @notAwardedStatusId, 0, 1, NOW()),
('Reject', NULL, 1, 0, 0, 0, NULL, @notAwardedStatusId, 0, 1, NOW()),
('Reopen', NULL, NULL, 1, 0, 0, NULL, @notAwardedStatusId, @openStatusId, 1, NOW());
