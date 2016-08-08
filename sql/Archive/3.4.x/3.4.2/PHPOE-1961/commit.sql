-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1961', NOW());

ALTER TABLE `institutions` ADD `institution_network_connectivity_id` INT NOT NULL AFTER `institution_gender_id`;
ALTER TABLE `institutions` ADD INDEX(`institution_network_connectivity_id`);

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`) VALUES (uuid(), 'Institutions', 'institution_network_connectivity_id', 'Institutions', 'Network Connectivity', NULL, NULL, '1');


SET @fieldOptionOrder := 0;
SELECT field_options.order INTO @fieldOptionOrder FROM field_options WHERE code = 'Genders';
UPDATE field_options SET field_options.order = field_options.order+1 WHERE field_options.order > @fieldOptionOrder;
INSERT INTO `field_options` (`id`, `plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `created_user_id`, `created`) VALUES (NULL, 'Institution', 'NetworkConnectivities', 'Network Connectivity', 'Institution', '{"model":"Institution.NetworkConnectivities"}', @fieldOptionOrder+1, 1, 1, NOW());



--
-- Creating table 'institution_network_connectivities'
--
CREATE TABLE `institution_network_connectivities` (
  `id` int(11) NOT NULL,
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
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `institution_network_connectivities`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `institution_network_connectivities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


INSERT INTO institution_network_connectivities (
`name`, 
`order`, 
`visible`, 
`editable`, 
`default`, 
`international_code`, 
`national_code`, 
`created_user_id`, 
`created`
) VALUES 
(
	'None',
	0, 
	1, 
	1, 
	1, 
	NULL, 
	NULL,
	1,
	NOW()
),
(
	'Internet-assisted Instruction',
	0, 
	1, 
	1, 
	0, 
	NULL, 
	NULL,
	1,
	NOW()
),
(
	'Fixed Broadband Internet',
	0, 
	1, 
	1, 
	0, 
	NULL, 
	NULL,
	1,
	NOW()
),
(
	'Wireless broadband Internet',
	0, 
	1, 
	1, 
	0, 
	NULL, 
	NULL,
	1,
	NOW()
),
(
	'Narrowband Internet',
	0, 
	1, 
	1, 
	0, 
	NULL, 
	NULL,
	1,
	NOW()
);

UPDATE institution_network_connectivities SET institution_network_connectivities.order = institution_network_connectivities.id;





