ALTER TABLE `institution_sites` ADD `network_connectivity_id` INT NOT NULL AFTER `institution_site_gender_id`;


INSERT INTO `field_options` (`id`, `plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`) VALUES (NULL, NULL, 'NetworkConnectivities', 'Network Connectivity', 'Institution', NULL, '1', '1');

SET @fieldOptionId := 0;
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'NetworkConnectivities';

INSERT INTO field_option_values (
`name`, 
`order`, 
`visible`, 
`editable`, 
`default`, 
`international_code`, 
`national_code`, 
`field_option_id`
) VALUES 
(
	'None',
	0, 
	1, 
	1, 
	1, 
	NULL, 
	NULL, 
	@fieldOptionId 
),
(
	'Internet-assisted Instruction',
	0, 
	1, 
	1, 
	0, 
	NULL, 
	NULL, 
	@fieldOptionId 
),
(
	'Fixed Broadband Internet',
	0, 
	1, 
	1, 
	0, 
	NULL, 
	NULL, 
	@fieldOptionId 
),
(
	'Wireless broadband Internet',
	0, 
	1, 
	1, 
	0, 
	NULL, 
	NULL, 
	@fieldOptionId 
),
(
	'Narrowband Internet',
	0, 
	1, 
	1, 
	0, 
	NULL, 
	NULL, 
	@fieldOptionId 
);

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`) VALUES (uuid(), 'Institutions', 'network_connectivity_id', 'Institutions', 'Network Connectivity', NULL, NULL, '1');