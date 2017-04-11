-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3927', NOW());


-- staff_trainings
CREATE TABLE `z_3927_staff_trainings` LIKE `staff_trainings`;
INSERT INTO `z_3927_staff_trainings` SELECT * FROM `staff_trainings`;

ALTER TABLE `staff_trainings`
    ADD `title` VARCHAR(100) NOT NULL AFTER `id`,
    ADD `credit_hours` INT(5) NOT NULL DEFAULT '0' AFTER `title`;

-- alerts Table
INSERT INTO `alerts` (`name`, `process_name`, `process_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES  ('LicenseRenewal', 'AlertLicenseRenewal', NULL, NULL, NULL, '1', NOW());

