-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2028');

--
-- Table structure for table `api_authorizations`
--

CREATE TABLE IF NOT EXISTS `api_authorizations` ( 
  `id` char(36) NOT NULL,
  `name` varchar(128) NOT NULL,
  `security_token` char(40) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime NOT NULL,
  `created_user_id` int(11) DEFAULT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for table `api_authorizations`
--
ALTER TABLE `api_authorizations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY (`security_token`);

INSERT INTO `api_authorizations` (`id`, `name`, `security_token`) values 
('00e588d8-6293-42ef-a0fe-395a63adf979', 'External Application Tester', 'acd87adcas9d8cad');
