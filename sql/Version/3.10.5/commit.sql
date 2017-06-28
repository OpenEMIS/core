-- POCOR-3809
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3809', NOW());

-- security_users
RENAME TABLE `security_users` TO `z_3809_security_users`;
CREATE TABLE `security_users` LIKE `z_3809_security_users`;

INSERT `security_users` (`id`, `username`, `password`, `openemis_no`, `first_name`, `middle_name`, `third_name`, `last_name`, `preferred_name`, `email`, `address`, `postal_code`, `address_area_id`, `birthplace_area_id`, `gender_id`, `date_of_birth`, `date_of_death`, `nationality_id`, `identity_type_id`, `identity_number`, `external_reference`, `super_admin`, `status`, `last_login`, `photo_name`, `photo_content`, `preferred_language`, `is_student`, `is_staff`, `is_guardian`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `username`, `password`, `openemis_no`, TRIM(`first_name`), TRIM(`middle_name`), TRIM(`third_name`), TRIM(`last_name`), TRIM(`preferred_name`), `email`, `address`, `postal_code`, `address_area_id`, `birthplace_area_id`, `gender_id`, `date_of_birth`, `date_of_death`, `nationality_id`, `identity_type_id`, `identity_number`, `external_reference`, `super_admin`, `status`, `last_login`, `photo_name`, `photo_content`, `preferred_language`, `is_student`, `is_staff`, `is_guardian`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_3809_security_users`;


-- POCOR-3955
-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3955', NOW());

-- security_functions
CREATE TABLE `z_3955_security_functions` LIKE `security_functions`;
INSERT `z_3955_security_functions` SELECT * FROM `security_functions`;

UPDATE `security_functions`
SET `name` = 'Trainings', `_view` = 'Trainings.index', `_add` = 'Trainings.add', `_execute` = 'Trainings.download'
WHERE `security_functions`.`id` = 6011;


-- 3.10.5
UPDATE config_items SET value = '3.10.5' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
