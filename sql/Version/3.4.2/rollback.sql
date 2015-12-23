SET @fieldOptionOrder := 0;
SELECT field_options.order INTO @fieldOptionOrder FROM field_options WHERE code = 'NetworkConnectivities';
UPDATE field_options SET field_options.order = field_options.order-1 WHERE field_options.order >= @fieldOptionOrder;
DELETE FROM field_options WHERE code = 'NetworkConnectivities';

DROP TABLE institution_network_connectivities;

ALTER TABLE `institutions` DROP `institution_network_connectivity_id`;

DELETE FROM labels WHERE field = 'institution_network_connectivity_id';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1961';
-- PHPOE-2319
UPDATE `security_functions` set `_execute` = 'ImportInstitutionSurveys.add|ImportInstitutionSurveys.template|ImportInstitutionSurveys.results' where `id`=1024;

DELETE FROM `security_functions` 
WHERE 
`id` = 1034 or
`id` = 1035 or
`id` = 1036 or
`id` = 1037 or
`id` = 7036
;

DELETE FROM `db_patches` WHERE `issue`='PHPOE-2319';

-- PHPOE-2359

DROP TABLE `import_mapping`;
ALTER TABLE `z_2359_import_mapping` RENAME `import_mapping`;

DELETE FROM `labels` WHERE `module`='Imports' AND `field`='institution_id';

DELETE FROM `db_patches` WHERE `issue`='PHPOE-2359';

-- PHPOE-2366

DROP TABLE `import_mapping`;
ALTER TABLE `z_2366_import_mapping` RENAME `import_mapping`;

DELETE FROM `db_patches` WHERE `issue`='PHPOE-2366';

UPDATE config_items SET value = '3.4.1' WHERE code = 'db_version';
