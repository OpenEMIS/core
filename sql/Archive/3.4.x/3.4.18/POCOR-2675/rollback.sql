ALTER TABLE `institution_positions` DROP `is_homeroom`;
ALTER TABLE `security_roles` DROP `code`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-2675';