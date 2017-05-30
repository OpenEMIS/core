-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2614', NOW());

INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'Next grade in the Education Structure is not available in this Institution.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Next grade in the Education Structure is not available in this Institution.');

INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'No Available Grades in this Institution', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'No Available Grades in this Institution');

INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'No Available Academic Periods', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'No Available Academic Periods');
