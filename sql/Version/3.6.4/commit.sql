-- POCOR-3347
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3347', NOW());

INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT NULL, NULL, 'There are no shifts configured for the selected academic period', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual
WHERE NOT EXISTS (SELECT * FROM `translations` WHERE `en` = 'There are no shifts configured for the selected academic period');


-- 3.6.4
UPDATE config_items SET value = '3.6.4' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
