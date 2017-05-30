INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3801', NOW());

-- security_functions
UPDATE `security_functions` SET `_view` = 'Students.index|Students.view' WHERE `id` = 1012;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(2033, 'Surveys', 'Students', 'Institutions', 'Students - General', 2000, 'StudentSurveys.index|StudentSurveys.view', NULL, NULL, NULL, NULL, 2033, 1, NULL, NULL, NULL, 1, NOW());
