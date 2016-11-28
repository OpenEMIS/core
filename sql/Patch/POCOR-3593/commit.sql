
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3593', NOW());

-- security_users
ALTER TABLE `security_users`
ADD COLUMN `nationality_id` INT NULL AFTER `date_of_death`,
ADD COLUMN `identity_type_id` INT NULL AFTER `nationality_id`,
ADD COLUMN `external_reference` VARCHAR(50) NULL AFTER `identity_number`;
