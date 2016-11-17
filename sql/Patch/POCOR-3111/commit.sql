-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3111', NOW());

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES
('6143285a-ac8d-11e6-8bda-525400b263eb', 'Attachments', 'security_roles', 'User -> Attachments', 'Shared', NULL, NULL, 1, NULL, NULL, 1, '2016-11-17 00:00:00');