SET @fieldOptionOrder := 0;
SELECT field_options.order INTO @fieldOptionOrder FROM field_options WHERE code = 'NetworkConnectivities';
UPDATE field_options SET field_options.order = field_options.order-1 WHERE field_options.order >= @fieldOptionOrder;
DELETE FROM field_options WHERE code = 'NetworkConnectivities';

DROP TABLE institution_network_connectivities;

ALTER TABLE `institutions` DROP `institution_network_connectivity_id`;

DELETE FROM labels WHERE field = 'institution_network_connectivity_id';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1961';