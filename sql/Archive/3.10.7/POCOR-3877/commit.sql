
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3877', NOW());

ALTER TABLE `security_role_functions`
RENAME TO  `z_3877_security_role_functions` ;

CREATE TABLE `security_role_functions` (
  `_view` int(1) DEFAULT '1',
  `_edit` int(1) DEFAULT '0',
  `_add` int(1) DEFAULT '0',
  `_delete` int(1) DEFAULT '0',
  `_execute` int(1) DEFAULT '0',
  `security_role_id` int(11) NOT NULL COMMENT 'links to security_roles.id',
  `security_function_id` int(11) NOT NULL COMMENT 'links to security_functions.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`security_role_id`,`security_function_id`),
  KEY `security_function_id` (`security_function_id`),
  KEY `security_role_id` (`security_role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of functions that can be accessed by the roles';

INSERT IGNORE INTO `security_role_functions` (`_view`, `_edit`, `_add`, `_delete`, `_execute`, `security_role_id`, `security_function_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `_view`, `_edit`, `_add`, `_delete`, `_execute`, `security_role_id`, `security_function_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3877_security_role_functions`;
