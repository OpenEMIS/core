-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3111', NOW());

-- user_attachments_roles
DROP TABLE IF EXISTS `user_attachments_roles`;
CREATE TABLE IF NOT EXISTS `user_attachments_roles` (
  `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachment_id` int(11) NOT NULL COMMENT 'links to user_attachments.id',
  `security_role_id` int(11) NOT NULL COMMENT 'links to security_roles.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of Attachments for specific Security Roles';

ALTER TABLE `user_attachments_roles`
  ADD PRIMARY KEY (`attachment_id`,`security_role_id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `id_index` (`id`),
  ADD KEY `attachment_id` (`attachment_id`),
  ADD KEY `security_role_id` (`security_role_id`),
  ADD KEY `modified_user_id` (`modified_user_id`),
  ADD KEY `created_user_id` (`created_user_id`);

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES
('6143285a-ac8d-11e6-8bda-525400b263eb', 'Attachments', 'security_roles', 'User -> Attachments', 'Shared', NULL, NULL, 1, NULL, NULL, 1, '2016-11-17 00:00:00');