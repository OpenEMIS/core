-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3396', NOW());

DROP TABLE IF EXISTS `security_user_logins`;
CREATE TABLE `security_user_logins` (
  `id` char(36) NOT NULL,
  `security_user_id` int(11) COMMENT 'links to security_users.id',
  `login_date_time` datetime
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This table contains all user logins';

--
-- Indexes for table `security_user_logins`
--
ALTER TABLE `security_user_logins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `security_user_id` (`security_user_id`),
  ADD KEY `login_date_time` (`login_date_time`);
