-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3111', NOW());

-- user_attachments_roles
DROP TABLE IF EXISTS `user_attachments_roles`;
CREATE TABLE IF NOT EXISTS `user_attachments_roles` (
  `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_attachment_id` int(11) NOT NULL COMMENT 'links to user_attachments.id',
  `security_role_id` int(11) NOT NULL COMMENT 'links to security_roles.id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of Attachments for specific Security Roles';

ALTER TABLE `user_attachments_roles`
  ADD PRIMARY KEY (`user_attachment_id`,`security_role_id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `id_index` (`id`),
  ADD KEY `user_attachment_id` (`user_attachment_id`),
  ADD KEY `security_role_id` (`security_role_id`);

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES
('6143285a-ac8d-11e6-8bda-525400b263eb', 'Attachments', 'security_roles', 'User -> Attachments', 'Shared', NULL, NULL, 1, NULL, NULL, 1, '2016-11-17 00:00:00');