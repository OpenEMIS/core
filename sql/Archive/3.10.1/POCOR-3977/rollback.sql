-- db_patches
ALTER TABLE `deleted_records`
CHANGE COLUMN `reference_key` `reference_key` CHAR(36) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NOT NULL ;

DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3977';