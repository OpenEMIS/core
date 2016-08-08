-- workflow_status_mapping
DROP TABLE `workflow_statuses`;

-- workflow_status_mapping
DROP TABLE `workflow_status_mappings`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2225';
