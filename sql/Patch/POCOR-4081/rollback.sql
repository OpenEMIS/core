DROP TABLE `deleted_records`;

ALTER TABLE `z_4081_deleted_records`
RENAME TO `deleted_records`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-4081';
