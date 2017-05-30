-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3977', NOW());

ALTER TABLE `deleted_records`
DROP INDEX `reference_key` ;

ALTER TABLE `deleted_records`
CHANGE COLUMN `reference_key` `reference_key` TEXT CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NOT NULL ;
