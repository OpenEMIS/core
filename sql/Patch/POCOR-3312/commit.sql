-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3312', NOW());


-- Translations
INSERT INTO `translations` (`code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `editable`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES (NULL, 'There are no shifts configured for the selected academic period, will be using system configuration timing', NULL, NULL, NULL, NULL, NULL, '1', NULL, NULL, '2', NOW());
