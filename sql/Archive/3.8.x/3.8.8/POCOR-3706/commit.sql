-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3706', NOW());

CREATE TABLE `system_errors` (
  `id` char(36) NOT NULL,
  `error_message` text NOT NULL,
  `request_url` text NOT NULL,
  `referrer_url` text NOT NULL,
  `client_ip` varchar(50) NOT NULL,
  `client_browser` text NOT NULL,
  `triggered_from` text NOT NULL,
  `stack_trace` longtext NOT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of errors encountered by users';

ALTER TABLE `system_errors` ADD PRIMARY KEY (`id`);
