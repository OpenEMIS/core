
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3593', NOW());

-- security_users
ALTER TABLE `security_users`
ADD COLUMN `nationality_id` INT NOT NULL DEFAULT 0 AFTER `date_of_death`,
ADD COLUMN `identity_type_id` INT NOT NULL DEFAULT 0 AFTER `nationality_id`;
