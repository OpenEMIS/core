ALTER TABLE `contact_types` DROP `validation_pattern`;

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3851';
